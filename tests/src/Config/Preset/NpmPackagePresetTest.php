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

namespace EliasHaeussler\VersionBumper\Tests\Config\Preset;

use EliasHaeussler\VersionBumper as Src;
use Generator;
use PHPUnit\Framework;

use function dirname;

/**
 * NpmPackagePresetTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Config\Preset\NpmPackagePreset::class)]
final class NpmPackagePresetTest extends Framework\TestCase
{
    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('getConfigThrowsExceptionIfPackageNameCannotBeDeterminedAutomaticallyDataProvider')]
    public function getConfigThrowsExceptionIfPackageNameCannotBeDeterminedAutomatically(
        ?Src\Config\VersionBumperConfig $rootConfig,
        Src\Exception\Exception $expected,
    ): void {
        $subject = new Src\Config\Preset\NpmPackagePreset([]);

        $this->expectExceptionObject($expected);

        $subject->getConfig($rootConfig);
    }

    #[Framework\Attributes\Test]
    public function getConfigResolvesPackageNameFromManifestFile(): void
    {
        $rootPath = dirname(__DIR__, 2).'/Fixtures/NpmPackagePreset/valid';

        $expected = new Src\Config\VersionBumperConfig(
            filesToModify: [
                new Src\Config\FileToModify(
                    'package.json',
                    [
                        new Src\Config\FilePattern('"version": "{%version%}"'),
                    ],
                    true,
                ),
                new Src\Config\FileToModify(
                    'package-lock.json',
                    [
                        new Src\Config\FilePattern(
                            '"name": "@foo/baz",\s+"version": "{%version%}"',
                        ),
                    ],
                    true,
                ),
            ],
        );

        $subject = new Src\Config\Preset\NpmPackagePreset([]);

        self::assertEquals($expected, $subject->getConfig(new Src\Config\VersionBumperConfig(rootPath: $rootPath)));
    }

    #[Framework\Attributes\Test]
    public function getConfigReturnsResolvedConfig(): void
    {
        $expected = new Src\Config\VersionBumperConfig(
            filesToModify: [
                new Src\Config\FileToModify(
                    'foo/baz/package.json',
                    [
                        new Src\Config\FilePattern('"version": "{%version%}"'),
                    ],
                    true,
                ),
                new Src\Config\FileToModify(
                    'foo/baz/package-lock.json',
                    [
                        new Src\Config\FilePattern(
                            '"name": "@foo/baz",\s+"version": "{%version%}"',
                        ),
                    ],
                    true,
                ),
            ],
        );

        $subject = new Src\Config\Preset\NpmPackagePreset([
            'packageName' => '@foo/baz',
            'path' => 'foo/baz',
        ]);

        self::assertEquals($expected, $subject->getConfig());
    }

    /**
     * @return Generator<string, array{Src\Config\VersionBumperConfig|null, Src\Exception\Exception}>
     */
    public static function getConfigThrowsExceptionIfPackageNameCannotBeDeterminedAutomaticallyDataProvider(): Generator
    {
        $rootPath = static fn (string $variant) => sprintf(
            '%s/Fixtures/NpmPackagePreset/invalid--%s',
            dirname(__DIR__, 2),
            $variant,
        );

        yield 'no root config' => [
            null,
            new Src\Exception\PackageNameIsMissing('package.json'),
        ];
        yield 'root config without root path' => [
            new Src\Config\VersionBumperConfig(),
            new Src\Exception\PackageNameIsMissing('package.json'),
        ];
        yield 'root config with missing file' => [
            new Src\Config\VersionBumperConfig(rootPath: '/foo/baz'),
            new Src\Exception\FileDoesNotExist('/foo/baz/package.json'),
        ];
        yield 'root config with malformed JSON' => [
            new Src\Config\VersionBumperConfig(rootPath: $rootPath('no-json')),
            new Src\Exception\ManifestFileIsMalformed($rootPath('no-json').'/package.json'),
        ];
        yield 'root config with unexpected JSON' => [
            new Src\Config\VersionBumperConfig(rootPath: $rootPath('no-object')),
            new Src\Exception\ManifestFileIsMalformed($rootPath('no-object').'/package.json'),
        ];
        yield 'root config with missing property' => [
            new Src\Config\VersionBumperConfig(rootPath: $rootPath('no-name')),
            new Src\Exception\ManifestFileIsMalformed($rootPath('no-name').'/package.json'),
        ];
        yield 'root config with invalid property' => [
            new Src\Config\VersionBumperConfig(rootPath: $rootPath('no-string')),
            new Src\Exception\ManifestFileIsMalformed($rootPath('no-string').'/package.json'),
        ];
    }
}
