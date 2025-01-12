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

namespace EliasHaeussler\VersionBumper\Config\Preset;

use EliasHaeussler\VersionBumper\Config;
use Symfony\Component\OptionsResolver;

use function ltrim;
use function sprintf;

/**
 * NpmPackagePreset.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 *
 * @extends BasePreset<array{packageName: string, path: string|null}>
 */
final class NpmPackagePreset extends BasePreset
{
    public function __construct(array $options)
    {
        $this->options = $this->resolveOptions($options);
    }

    public function getConfig(): Config\VersionBumperConfig
    {
        $filesToModify = [
            new Config\FileToModify(
                $this->resolvePath('package.json'),
                [
                    new Config\FilePattern('"version": "{%version%}"'),
                ],
                true,
            ),
            new Config\FileToModify(
                $this->resolvePath('package-lock.json'),
                [
                    new Config\FilePattern(
                        sprintf(
                            '"name": "%s",\s+"version": "{%%version%%}"',
                            $this->options['packageName'],
                        ),
                    ),
                ],
                true,
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

    private function resolvePath(string $filename): string
    {
        return ltrim($this->options['path'].'/'.$filename, '/');
    }

    protected function createOptionsResolver(): OptionsResolver\OptionsResolver
    {
        $optionsResolver = new OptionsResolver\OptionsResolver();
        $optionsResolver->define('packageName')
            ->allowedTypes('string')
            ->required()
        ;
        $optionsResolver->define('path')
            ->allowedTypes('string')
            ->default('')
        ;

        return $optionsResolver;
    }
}