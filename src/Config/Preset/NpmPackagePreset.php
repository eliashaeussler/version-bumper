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
use JsonException;
use stdClass;
use Symfony\Component\Filesystem;
use Symfony\Component\OptionsResolver;

use function is_string;
use function ltrim;
use function property_exists;
use function sprintf;

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
    private readonly Filesystem\Filesystem $filesystem;

    public function __construct(array $options)
    {
        $this->filesystem = new Filesystem\Filesystem();
        $this->options = $this->resolveOptions($options);
    }

    /**
     * @throws Exception\FileDoesNotExist
     * @throws Exception\FileIsNotReadable
     * @throws Exception\FilePatternIsInvalid
     * @throws Exception\ManifestFileIsMalformed
     * @throws Exception\PackageNameIsMissing
     */
    public function getConfig(?Config\VersionBumperConfig $rootConfig = null): Config\VersionBumperConfig
    {
        $packageJsonFile = new Config\FileToModify(
            $this->resolvePath('package.json'),
            [
                new Config\FilePattern('"version": "{%version%}"'),
            ],
            true,
        );

        if (null !== $this->options['packageName']) {
            $packageName = $this->options['packageName'];
        } elseif (null !== $rootConfig && null !== $rootConfig->rootPath()) {
            $packageName = $this->resolvePackageName($packageJsonFile->fullPath($rootConfig->rootPath()));
        } else {
            throw new Exception\PackageNameIsMissing($packageJsonFile->path());
        }

        $filesToModify = [
            $packageJsonFile,
            new Config\FileToModify(
                $this->resolvePath('package-lock.json'),
                [
                    new Config\FilePattern(
                        sprintf(
                            '"name": "%s",\s+"version": "{%%version%%}"',
                            $packageName,
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

    /**
     * @throws Exception\FileDoesNotExist
     * @throws Exception\FileIsNotReadable
     * @throws Exception\ManifestFileIsMalformed
     */
    private function resolvePackageName(string $path): string
    {
        if (!$this->filesystem->exists($path)) {
            throw new Exception\FileDoesNotExist($path);
        }

        $contents = file_get_contents($path);

        if (false === $contents) {
            throw new Exception\FileIsNotReadable($path);
        }

        try {
            $packageJson = json_decode($contents, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new Exception\ManifestFileIsMalformed($path, $exception);
        }

        if (!($packageJson instanceof stdClass)
            || !property_exists($packageJson, 'name')
            || !is_string($packageJson->name)
        ) {
            throw new Exception\ManifestFileIsMalformed($path);
        }

        return $packageJson->name;
    }

    protected function createOptionsResolver(): OptionsResolver\OptionsResolver
    {
        $optionsResolver = new OptionsResolver\OptionsResolver();
        $optionsResolver->define('packageName')
            ->allowedTypes('string', 'null')
            ->default(null)
        ;
        $optionsResolver->define('path')
            ->allowedTypes('string')
            ->default('')
        ;

        return $optionsResolver;
    }
}
