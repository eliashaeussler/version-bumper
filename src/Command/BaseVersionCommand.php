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

use Composer\Command;
use Composer\Composer;
use CuyZ\Valinor;
use EliasHaeussler\VersionBumper\Config;
use EliasHaeussler\VersionBumper\Error;
use EliasHaeussler\VersionBumper\Exception;
use Symfony\Component\Console;
use Symfony\Component\Filesystem;

use function dirname;
use function getcwd;
use function is_string;
use function method_exists;
use function sprintf;
use function trim;

/**
 * BaseVersionCommand.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
abstract class BaseVersionCommand extends Command\BaseCommand
{
    protected readonly Config\ConfigReader $configReader;
    protected readonly Error\DeprecationHandler $deprecationHandler;
    protected Console\Style\SymfonyStyle $io;

    public function __construct(
        string $name,
        ?Composer $composer = null,
    ) {
        if (null !== $composer) {
            $this->setComposer($composer);
        }

        parent::__construct($name);

        $this->configReader = new Config\ConfigReader();
        $this->deprecationHandler = Error\DeprecationHandler::new();
    }

    protected function configure(): void
    {
        $this->addOption(
            'config',
            'c',
            Console\Input\InputOption::VALUE_REQUIRED,
            'Path to configuration file (JSON, YAML or PHP)',
            $this->readConfigFileFromRootPackage(),
        );
    }

    protected function initialize(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): void
    {
        $this->io = new Console\Style\SymfonyStyle($input, $output);
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): int
    {
        $rootPath = (string) getcwd();
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

        // Register custom error handler to collect deprecations from config presets
        $this->deprecationHandler->enable();

        try {
            $config = $this->configReader->readFromFile($configFile);

            // Override root path from config file
            if (null !== $config->rootPath()) {
                $rootPath = $config->rootPath();
            }

            return $this->executeCommand($config, $rootPath, $input, $output);
        } catch (Valinor\Mapper\MappingError $error) {
            $this->decorateMappingError($error, $configFile);

            return self::FAILURE;
        } catch (Exception\Exception $exception) {
            $this->io->error($exception->getMessage());

            return self::FAILURE;
        } finally {
            $this->deprecationHandler->decorate($this->io);
            $this->deprecationHandler->disable();
        }
    }

    abstract protected function executeCommand(
        Config\VersionBumperConfig $config,
        string $rootPath,
        Console\Input\InputInterface $input,
        Console\Output\OutputInterface $output,
    ): int;

    protected function decorateMappingError(Valinor\Mapper\MappingError $error, string $configFile): void
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

    protected function readConfigFileFromRootPackage(): ?string
    {
        $composer = $this->getComposerInstance();

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

    protected function getComposerInstance(): ?Composer
    {
        // Composer >= 2.3
        if (method_exists($this, 'tryComposer')) {
            return $this->tryComposer();
        }

        // Composer < 2.3
        return $this->getComposer(false);
    }
}
