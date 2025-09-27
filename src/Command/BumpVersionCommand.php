<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/version-bumper".
 *
 * Copyright (C) 2024-2025 Elias Häußler <elias@haeussler.dev>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace EliasHaeussler\VersionBumper\Command;

use Composer\Command;
use Composer\Composer;
use CuyZ\Valinor;
use EliasHaeussler\VersionBumper\Config;
use EliasHaeussler\VersionBumper\Enum;
use EliasHaeussler\VersionBumper\Exception;
use EliasHaeussler\VersionBumper\Result;
use EliasHaeussler\VersionBumper\Version;
use GitElephant\Command\Caller;
use Symfony\Component\Console;
use Symfony\Component\Filesystem;

use function array_filter;
use function array_map;
use function count;
use function dirname;
use function getcwd;
use function implode;
use function is_string;
use function method_exists;
use function reset;
use function sprintf;
use function trim;

/**
 * BumpVersionCommand.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class BumpVersionCommand extends Command\BaseCommand
{
    private readonly Version\VersionBumper $bumper;
    private readonly Config\ConfigReader $configReader;
    private readonly Version\VersionRangeDetector $versionRangeDetector;
    private readonly Version\VersionReleaser $releaser;
    private Console\Style\SymfonyStyle $io;

    public function __construct(
        ?Composer $composer = null,
        ?Caller\CallerInterface $caller = null,
    ) {
        if (null !== $composer) {
            $this->setComposer($composer);
        }

        parent::__construct('bump-version');

        $this->bumper = new Version\VersionBumper();
        $this->configReader = new Config\ConfigReader();
        $this->versionRangeDetector = new Version\VersionRangeDetector($caller);
        $this->releaser = new Version\VersionReleaser($caller);
    }

    protected function configure(): void
    {
        $this->setAliases(['bv']);
        $this->setDescription('Bump package version in specific files during release preparations');

        $this->addArgument(
            'range',
            Console\Input\InputArgument::OPTIONAL,
            sprintf(
                'Version range (one of "%s") or explicit version to bump in configured files',
                implode('", "', Enum\VersionRange::all()),
            ),
        );

        $this->addOption(
            'config',
            'c',
            Console\Input\InputOption::VALUE_REQUIRED,
            'Path to configuration file (JSON, YAML or PHP) with files in which to bump new versions',
            $this->readConfigFileFromRootPackage(),
        );
        $this->addOption(
            'release',
            'r',
            Console\Input\InputOption::VALUE_NONE,
            'Create a new Git tag after versions are bumped',
        );
        $this->addOption(
            'dry-run',
            null,
            Console\Input\InputOption::VALUE_NONE,
            'Do not perform any write operations, just calculate version bumps',
        );
        $this->addOption(
            'strict',
            null,
            Console\Input\InputOption::VALUE_NONE,
            'Fail if any unmatched file pattern is reported',
        );
    }

    protected function initialize(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): void
    {
        $this->io = new Console\Style\SymfonyStyle($input, $output);
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): int
    {
        $rootPath = (string) getcwd();
        $rangeOrVersion = $input->getArgument('range');
        $configFile = $input->getOption('config') ?? $this->configReader->detectFile($rootPath);
        $release = $input->getOption('release');
        $dryRun = $input->getOption('dry-run');
        $strict = $input->getOption('strict');

        if (null === $configFile) {
            $this->io->error('Please provide a config file path using the --config option.');

            return self::INVALID;
        }

        if (Filesystem\Path::isRelative($configFile)) {
            $configFile = Filesystem\Path::makeAbsolute($configFile, $rootPath);
        } else {
            $rootPath = dirname($configFile);
        }

        try {
            $result = $this->bumpVersions($configFile, $rangeOrVersion, $rootPath, $dryRun);

            if (null === $result) {
                return self::FAILURE;
            }

            [$config, $results] = $result;

            if ($release && !$this->releaseVersion($results, $rootPath, $config->releaseOptions(), $dryRun)) {
                return self::FAILURE;
            }
        } finally {
            if ($dryRun) {
                $this->io->note('No write operations were performed (dry-run mode).');
            }
        }

        if ($strict) {
            foreach ($results as $versionBumpResult) {
                if ($versionBumpResult->hasUnmatchedReports()) {
                    return self::FAILURE;
                }
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return array{Config\VersionBumperConfig, list<Result\VersionBumpResult>}|null
     */
    private function bumpVersions(string $configFile, ?string $rangeOrVersion, string $rootPath, bool $dryRun): ?array
    {
        try {
            $config = $this->configReader->readFromFile($configFile);
            $finalRootPath = $config->rootPath() ?? $rootPath;

            // Auto-detect version range from indicators
            if (null !== $rangeOrVersion) {
                $versionRange = Enum\VersionRange::tryFromInput($rangeOrVersion) ?? $rangeOrVersion;
            } elseif ([] !== $config->versionRangeIndicators()) {
                $versionRange = $this->versionRangeDetector->detect($finalRootPath, $config->versionRangeIndicators());
            } else {
                $this->io->error('Please provide a version range or explicit version to bump in configured files.');
                $this->io->block(
                    'You can also enable auto-detection by adding version range indicators to your configuration file.',
                    null,
                    'fg=cyan',
                    '💡 ',
                );

                return null;
            }

            // Exit early if version range detection fails
            if (null === $versionRange) {
                $this->io->error('Unable to auto-detect version range. Please provide a version range or explicit version instead.');

                return null;
            }

            $this->decorateAppliedPresets($config->presets());

            $results = $this->bumper->bump($config->filesToModify(), $finalRootPath, $versionRange, $dryRun);

            $this->decorateVersionBumpResults($results, $rootPath);

            return [$config, $results];
        } catch (Valinor\Mapper\MappingError $error) {
            $this->decorateMappingError($error, $configFile);
        } catch (Exception\Exception $exception) {
            $this->io->error($exception->getMessage());
        }

        return null;
    }

    /**
     * @param list<Result\VersionBumpResult> $results
     */
    private function releaseVersion(array $results, string $rootPath, Config\ReleaseOptions $options, bool $dryRun): bool
    {
        $this->io->title('Release');

        try {
            $releaseResult = $this->releaser->release($results, $rootPath, $options, $dryRun);

            $this->decorateVersionReleaseResult($releaseResult);

            return true;
        } catch (Exception\Exception $exception) {
            $this->io->error($exception->getMessage());
        } catch (\Exception $exception) {
            $this->io->error('Git error during release: '.$exception->getMessage());
        }

        return false;
    }

    /**
     * @param list<Config\Preset\Preset> $presets
     */
    private function decorateAppliedPresets(array $presets): void
    {
        if ([] === $presets || !$this->io->isVerbose()) {
            return;
        }

        $this->io->title('Applied presets');

        $this->io->listing(
            array_map(
                static fn (Config\Preset\Preset $preset) => sprintf(
                    '%s <fg=gray>(%s)</>',
                    $preset::getDescription(),
                    $preset::getIdentifier(),
                ),
                $presets,
            ),
        );
    }

    /**
     * @param list<Result\VersionBumpResult> $results
     */
    private function decorateVersionBumpResults(array $results, string $rootPath): void
    {
        $titleDisplayed = false;

        foreach ($results as $result) {
            if (!$result->hasOperations()) {
                continue;
            }

            $path = $result->file()->path();
            $groupedOperations = $result->groupedOperations();
            $hasOnlySkippedOperations = [] === array_filter(
                $groupedOperations,
                static fn (array $operations) => Enum\OperationState::Skipped !== reset($operations)->state(),
            );

            if (Filesystem\Path::isAbsolute($path)) {
                $path = Filesystem\Path::makeRelative($path, $rootPath);
            }

            if (!$titleDisplayed) {
                $this->io->title('Bumped versions');
                $titleDisplayed = true;
            }

            $this->io->section($path);

            foreach ($groupedOperations as $operations) {
                $operation = reset($operations);
                $numberOfOperations = count($operations);
                $state = $operation->state();

                if (Enum\OperationState::Skipped === $state && !$hasOnlySkippedOperations) {
                    continue;
                }

                $message = match ($state) {
                    Enum\OperationState::Modified => sprintf(
                        '✅ Bumped version from "%s" to "%s"',
                        $operation->source()?->full() ?? '',
                        $operation->target()?->full() ?? '',
                    ),
                    Enum\OperationState::Skipped => '⏩ Skipped file due to unmodified contents',
                    Enum\OperationState::Unmatched => '❓ Unmatched file pattern: '.$operation->pattern()->original(),
                };

                if ($numberOfOperations > 1) {
                    $message .= sprintf(' (%dx)', $numberOfOperations);
                }

                $this->io->writeln($message);
            }
        }
    }

    private function decorateVersionReleaseResult(Result\VersionReleaseResult $result): void
    {
        $numberOfCommittedFiles = count($result->committedFiles());
        $releaseInformation = [
            sprintf('Added %d file%s.', $numberOfCommittedFiles, 1 !== $numberOfCommittedFiles ? 's' : ''),
            sprintf('Committed: <info>%s</info>', $result->commitMessage()),
        ];

        if (null !== $result->commitId()) {
            $releaseInformation[] = sprintf('Commit hash: %s', $result->commitId());
        }

        $releaseInformation[] = sprintf('Tagged: <info>%s</info>', $result->tagName());

        $this->io->listing($releaseInformation);
    }

    private function decorateMappingError(Valinor\Mapper\MappingError $error, string $configFile): void
    {
        $errorMessages = [];
        $errors = $error->messages()->errors();

        $this->io->error(
            sprintf('The config file "%s" is invalid.', $configFile),
        );

        foreach ($errors as $propertyError) {
            $errorMessages[] = sprintf('%s: %s', $propertyError->path(), $propertyError->toString());
        }

        $this->io->listing($errorMessages);
    }

    private function readConfigFileFromRootPackage(): ?string
    {
        if (method_exists($this, 'tryComposer')) {
            // Composer >= 2.3
            $composer = $this->tryComposer();
        } else {
            // Composer < 2.3
            $composer = $this->getComposer(false);
        }

        if (null === $composer) {
            return null;
        }

        $extra = $composer->getPackage()->getExtra();
        /* @phpstan-ignore offsetAccess.nonOffsetAccessible */
        $configFile = $extra['version-bumper']['config-file'] ?? null;

        if (is_string($configFile) && '' !== trim($configFile)) {
            return $configFile;
        }

        return null;
    }
}
