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

namespace EliasHaeussler\VersionBumper;

use Composer\Composer;
use Composer\IO;
use Composer\Plugin;

use function is_string;

/**
 * VersionBumperPlugin.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 *
 * @codeCoverageIgnore
 */
final class VersionBumperPlugin implements Plugin\PluginInterface, Plugin\Capable, Plugin\Capability\CommandProvider
{
    private static ?string $configFile = null;

    public function getCapabilities(): array
    {
        return [
            Plugin\Capability\CommandProvider::class => self::class,
        ];
    }

    public function getCommands(): array
    {
        return [
            new Command\BumpVersionCommand(self::$configFile),
        ];
    }

    public function activate(Composer $composer, IO\IOInterface $io): void
    {
        /* @phpstan-ignore offsetAccess.nonOffsetAccessible */
        $configFile = $composer->getPackage()->getExtra()['version-bumper']['config-file'] ?? null;

        if (is_string($configFile)) {
            self::$configFile = $configFile;
        }
    }

    public function deactivate(Composer $composer, IO\IOInterface $io): void
    {
        // Intentionally left blank.
    }

    public function uninstall(Composer $composer, IO\IOInterface $io): void
    {
        // Intentionally left blank.
    }
}
