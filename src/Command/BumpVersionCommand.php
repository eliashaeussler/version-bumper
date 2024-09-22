<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/version-bumper".
 *
 * Copyright (C) 2024 Elias Häußler <elias@haeussler.dev>
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
use CuyZ\Valinor;
use EliasHaeussler\VersionBumper\Config;
use EliasHaeussler\VersionBumper\Enum;
use EliasHaeussler\VersionBumper\Exception;
use EliasHaeussler\VersionBumper\Result;
use EliasHaeussler\VersionBumper\Version;
use Symfony\Component\Console;
use Symfony\Component\Filesystem;

use function count;
use function dirname;
use function getcwd;
use function implode;
use function reset;
use function sprintf;

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
    private Console\Style\SymfonyStyle $io;

    public function __construct(
        private readonly ?string $defaultConfigFile = null,
    ) {
        parent::__construct('bump-version');

        $this->bumper = new Version\VersionBumper();
        $this->configReader = new Config\ConfigReader();
    }

    protected function configure(): void
    {
        $this->setAliases(['bv']);
        $this->setDescription('Bump package version in specific files during release preparations');

        $this->addArgument(
            'range',
            Console\Input\InputArgument::REQUIRED,
            sprintf(
                'Version range (one of "%s") or explicit version to bump in configured files',
                implode('", "', Enum\VersionRange::all()),
            ),
        );

        $this->addOption(
            'config',
            'c',
            Console\Input\InputOption::VALUE_REQUIRED,
            'Path to configuration file (JSON or YAML) with files in which to bump new versions',
            $this->defaultConfigFile,
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
            $config = $this->configReader->readFromFile($configFile);
            $versionRange = Enum\VersionRange::tryFromInput($rangeOrVersion);
            $results = $this->bumper->bump(
                $config->filesToModify(),
                $config->rootPath() ?? $rootPath,
                $versionRange ?? $rangeOrVersion,
            );
        } catch (Valinor\Mapper\MappingError $error) {
            $this->decorateMappingError($error, $configFile);

            return self::FAILURE;
        } catch (Exception\Exception $exception) {
            $this->io->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->decorateResults($results, $rootPath);

        return self::SUCCESS;
    }

    /**
     * @param list<Result\VersionBumpResult> $results
     */
    private function decorateResults(array $results, string $rootPath): void
    {
        foreach ($results as $result) {
            $path = $result->file()->path();

            if (Filesystem\Path::isAbsolute($path)) {
                $path = Filesystem\Path::makeRelative($path, $rootPath);
            }

            $this->io->section($path);

            foreach ($result->groupedOperations() as $operations) {
                $operation = reset($operations);
                $numberOfOperations = count($operations);
                $message = match ($operation->state()) {
                    Enum\OperationState::Modified => sprintf(
                        '✅ Bumped version from "%s" to "%s"',
                        $operation->source()->full(),
                        $operation->target()->full(),
                    ),
                    Enum\OperationState::Skipped => '⏩ Skipped file due to unmodified contents',
                };

                if ($numberOfOperations > 1) {
                    $message .= sprintf(' (%dx)', $numberOfOperations);
                }

                $this->io->writeln($message);
            }
        }
    }

    private function decorateMappingError(Valinor\Mapper\MappingError $error, string $configFile): void
    {
        $errorMessages = [];
        $errors = Valinor\Mapper\Tree\Message\Messages::flattenFromNode($error->node())->errors();

        $this->io->error(
            sprintf('The config file "%s" is invalid.', $configFile),
        );

        foreach ($errors as $propertyError) {
            $errorMessages[] = sprintf('%s: %s', $propertyError->node()->path(), $propertyError->toString());
        }

        $this->io->listing($errorMessages);
    }
}
