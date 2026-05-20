<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/version-bumper".
 *
 * Copyright (C) 2024-2026 Elias Häußler <elias@haeussler.dev>
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

use Composer\Composer;
use EliasHaeussler\VersionBumper\Config;
use EliasHaeussler\VersionBumper\Enum;
use EliasHaeussler\VersionBumper\Exception;
use EliasHaeussler\VersionBumper\Helper;
use EliasHaeussler\VersionBumper\Result;
use EliasHaeussler\VersionBumper\Version;
use GitElephant\Command\Caller;
use GitElephant\Repository;
use Symfony\Component\Console;

use function implode;
use function sprintf;

/**
 * NextVersionCommand.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class NextVersionCommand extends BaseVersionCommand
{
    private readonly Version\VersionBumper $bumper;
    private readonly Version\VersionRangeDetector $versionRangeDetector;

    public function __construct(
        ?Composer $composer = null,
        private readonly ?Caller\CallerInterface $caller = null,
    ) {
        parent::__construct('next-version', $composer);

        $this->bumper = new Version\VersionBumper();
        $this->versionRangeDetector = new Version\VersionRangeDetector($caller);
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setAliases(['nv', 'next']);
        $this->setDescription('Calculate next package version');

        $this->addArgument(
            'range',
            Console\Input\InputArgument::OPTIONAL,
            sprintf(
                'Version range (one of "%s") for the next package version',
                implode('", "', Enum\VersionRange::all()),
            ),
        );
    }

    protected function executeCommand(
        Config\VersionBumperConfig $config,
        string $rootPath,
        Console\Input\InputInterface $input,
        Console\Output\OutputInterface $output,
    ): int {
        $versionRange = $this->resolveVersionRange($config, $input->getArgument('range'), $rootPath);

        if (null === $versionRange) {
            return self::FAILURE;
        }

        $results = $this->bumper->bump($config->filesToModify(), $rootPath, $versionRange, true);
        $version = $this->resolveVersionFromResultsOrRange($results, $versionRange, $rootPath);

        if (null === $version) {
            $this->io->error('Unable to calculate next package version.');

            return self::FAILURE;
        }

        $this->io->writeln($version->full());

        return self::SUCCESS;
    }

    private function resolveVersionRange(
        Config\VersionBumperConfig $config,
        ?string $rangeOrVersion,
        string $rootPath,
    ): ?Enum\VersionRange {
        if (null !== $rangeOrVersion) {
            $versionRange = Enum\VersionRange::tryFromInput($rangeOrVersion);
        } elseif ([] !== $config->versionRangeIndicators()) {
            $versionRange = $this->versionRangeDetector->detect($rootPath, $config->versionRangeIndicators());
        } else {
            $this->io->error(
                sprintf(
                    'Please provide a valid version range, must be one of "%s".',
                    implode('", "', Enum\VersionRange::all()),
                ),
            );
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
            $this->io->error('Unable to auto-detect version range. Please provide a version range instead.');

            return null;
        }

        return $versionRange;
    }

    /**
     * @param list<Result\VersionBumpResult> $results
     *
     * @throws Exception\AmbiguousVersionsDetected
     * @throws Exception\CannotFetchLatestGitTag
     * @throws Exception\VersionIsNotSupported
     */
    private function resolveVersionFromResultsOrRange(
        array $results,
        ?Enum\VersionRange $versionRange,
        string $rootPath,
    ): ?Version\Version {
        $version = Helper\VersionHelper::extractVersionFromResults($results);

        if (null !== $version || null === $versionRange) {
            return $version;
        }

        $repository = new Repository($rootPath);

        if (null !== $this->caller) {
            $repository->setCaller($this->caller);
        }

        return Helper\VersionHelper::detectVersionFromVersionRange($versionRange, $repository);
    }
}
