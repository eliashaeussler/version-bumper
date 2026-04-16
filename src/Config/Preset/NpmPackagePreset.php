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

namespace EliasHaeussler\VersionBumper\Config\Preset;

use EliasHaeussler\VersionBumper\Config;
use EliasHaeussler\VersionBumper\Exception;
use EliasHaeussler\VersionBumper\Version;
use Symfony\Component\Filesystem;
use Symfony\Component\OptionsResolver;

/**
 * NpmPackagePreset.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 *
 * @extends BasePreset<array{packageName: string|null, path: string}>
 */
final class NpmPackagePreset extends BasePreset
{
    public function __construct(array $options)
    {
        $this->options = $this->resolveOptions($options);
    }

    /**
     * @throws Exception\FilePatternIsInvalid
     */
    public function getConfig(?Config\VersionBumperConfig $rootConfig = null): Config\VersionBumperConfig
    {
        $filesToModify = [
            new Config\FileToModify(
                Filesystem\Path::join($this->options['path'], 'package.json'),
                [
                    new Config\FilePattern('"version": "{%version%}"'),
                ],
                true,
                true,
                [],
                [
                    new Version\Action\PackageLockAction(),
                ],
            ),
        ];

        return new Config\VersionBumperConfig(filesToModify: $filesToModify);
    }

    public static function getIdentifier(): string
    {
        return 'npm-package';
    }

    public static function getDescription(): string
    {
        return 'NPM package, managed by package.json and package-lock.json';
    }

    protected function createOptionsResolver(): OptionsResolver\OptionsResolver
    {
        $optionsResolver = new OptionsResolver\OptionsResolver();
        $optionsResolver->define('packageName')
            ->allowedTypes('string', 'null')
            ->default(null)
            // @todo Remove with v5 of the library
            ->deprecated(
                'eliashaeussler/version-bumper',
                '3.2.0',
                'The option "%name%" is no longer needed and should be omitted.',
            )
        ;
        $optionsResolver->define('path')
            ->allowedTypes('string')
            ->default('')
        ;

        return $optionsResolver;
    }
}
