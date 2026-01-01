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

namespace EliasHaeussler\VersionBumper\Tests\Config;

use CuyZ\Valinor;
use EliasHaeussler\VersionBumper as Src;
use Generator;
use PHPUnit\Framework;
use Symfony\Component\Filesystem;

use function dirname;

/**
 * ConfigReaderTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Config\ConfigReader::class)]
final class ConfigReaderTest extends Framework\TestCase
{
    private Src\Config\ConfigReader $subject;

    public function setUp(): void
    {
        $this->subject = new Src\Config\ConfigReader();
    }

    #[Framework\Attributes\Test]
    public function readFromFileThrowsExceptionIfFileDoesNotExist(): void
    {
        $this->expectExceptionObject(
            new Src\Exception\FileDoesNotExist('foo'),
        );

        $this->subject->readFromFile('foo');
    }

    #[Framework\Attributes\Test]
    public function readFromFileThrowsExceptionOnUnsupportedConfigFile(): void
    {
        $file = dirname(__DIR__, 3).'/phpunit.xml';

        $this->expectExceptionObject(
            new Src\Exception\ConfigFileIsNotSupported($file),
        );

        $this->subject->readFromFile($file);
    }

    #[Framework\Attributes\Test]
    public function readFromFileThrowsExceptionOnInvalidPhpFile(): void
    {
        $file = dirname(__DIR__).'/Fixtures/ConfigFiles/invalid-config.php';

        $this->expectException(Src\Exception\ConfigFileIsInvalid::class);

        $this->subject->readFromFile($file);
    }

    #[Framework\Attributes\Test]
    public function readFromFileReturnsConfigFromPhpFile(): void
    {
        $rootPath = dirname(__DIR__).'/Fixtures';
        $file = $rootPath.'/ConfigFiles/valid-config.php';

        $expected = new Src\Config\VersionBumperConfig(
            filesToModify: [
                new Src\Config\FileToModify(
                    'foo',
                    [
                        'baz: {%version%}',
                    ],
                ),
            ],
            rootPath: $rootPath,
        );

        self::assertEquals($expected, $this->subject->readFromFile($file));
    }

    #[Framework\Attributes\Test]
    public function readFromFileReturnsConfigFromClosureInPhpFile(): void
    {
        $rootPath = dirname(__DIR__).'/Fixtures';
        $file = $rootPath.'/ConfigFiles/valid-config-with-closure.php';

        $expected = new Src\Config\VersionBumperConfig(
            filesToModify: [
                new Src\Config\FileToModify(
                    'foo',
                    [
                        'baz: {%version%}',
                    ],
                ),
            ],
            rootPath: $rootPath,
        );

        self::assertEquals($expected, $this->subject->readFromFile($file));
    }

    #[Framework\Attributes\Test]
    public function readFromFileThrowsExceptionOnInvalidJsonFile(): void
    {
        $file = dirname(__DIR__).'/Fixtures/ConfigFiles/invalid-config.json';

        $this->expectException(Valinor\Mapper\MappingError::class);

        $this->subject->readFromFile($file);
    }

    #[Framework\Attributes\Test]
    public function readFromFileReturnsMappedConfigFromJsonFile(): void
    {
        $rootPath = dirname(__DIR__).'/Fixtures';
        $file = $rootPath.'/ConfigFiles/valid-config.json';

        $expected = new Src\Config\VersionBumperConfig(
            filesToModify: [
                new Src\Config\FileToModify(
                    'foo',
                    [
                        'baz: {%version%}',
                    ],
                ),
            ],
            rootPath: $rootPath,
        );

        self::assertEquals($expected, $this->subject->readFromFile($file));
    }

    #[Framework\Attributes\Test]
    public function readFromFileThrowsExceptionOnInvalidYamlFile(): void
    {
        $file = dirname(__DIR__).'/Fixtures/ConfigFiles/invalid-config.yaml';

        $this->expectExceptionObject(
            new Src\Exception\ConfigFileIsInvalid($file),
        );

        $this->subject->readFromFile($file);
    }

    #[Framework\Attributes\Test]
    public function readFromFileReturnsMappedConfigFromYamlFile(): void
    {
        $rootPath = dirname(__DIR__).'/Fixtures';
        $file = $rootPath.'/ConfigFiles/valid-config.yaml';

        $expected = new Src\Config\VersionBumperConfig(
            filesToModify: [
                new Src\Config\FileToModify(
                    'foo',
                    [
                        'baz: {%version%}',
                    ],
                ),
            ],
            rootPath: $rootPath,
        );

        self::assertEquals($expected, $this->subject->readFromFile($file));
    }

    #[Framework\Attributes\Test]
    public function readFromFileCalculatesRootPathBasedOnConfigFileLocation(): void
    {
        $rootPath = dirname(__DIR__).'/Fixtures/ConfigFiles';
        $file = $rootPath.'/valid-config-without-root-path.json';

        $expected = new Src\Config\VersionBumperConfig(
            filesToModify: [
                new Src\Config\FileToModify(
                    'foo',
                    [
                        'baz: {%version%}',
                    ],
                ),
            ],
            rootPath: $rootPath,
        );

        self::assertEquals($expected, $this->subject->readFromFile($file));
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('readFromFileAppliesConfiguredPresetsDataProvider')]
    public function readFromFileAppliesConfiguredPresets(string $preset, Src\Config\VersionBumperConfig $expected): void
    {
        $rootPath = dirname(__DIR__).'/Fixtures/ConfigFiles';
        $file = $rootPath.'/valid-config-with-'.$preset.'-preset.json';

        self::assertEquals($expected, $this->subject->readFromFile($file));
    }

    #[Framework\Attributes\Test]
    public function detectFileReturnsNullIfNoConfigFilesAreAvailableInGivenRootPath(): void
    {
        self::assertNull($this->subject->detectFile(__DIR__));
    }

    #[Framework\Attributes\Test]
    public function detectFileReturnsAutoDetectedFileWithinGivenRootPath(): void
    {
        $rootPath = dirname(__DIR__).'/Fixtures/ConfigFiles';
        $expected = Filesystem\Path::join($rootPath, 'version-bumper.php');

        self::assertSame($expected, $this->subject->detectFile($rootPath));
    }

    /**
     * @return Generator<string, array{string, Src\Config\VersionBumperConfig}>
     */
    public static function readFromFileAppliesConfiguredPresetsDataProvider(): Generator
    {
        $fileToModify = new Src\Config\FileToModify(
            'baz',
            [
                new Src\Config\FilePattern('foo: {%version%}'),
            ],
        );
        $rootPath = dirname(__DIR__).'/Fixtures/RootPath';

        yield 'Composer package' => [
            'composer-package',
            new Src\Config\VersionBumperConfig(
                [
                    new Src\Config\Preset\ComposerPackagePreset([
                        'path' => 'foo',
                    ]),
                ],
                [
                    $fileToModify,
                    new Src\Config\FileToModify(
                        'foo/composer.json',
                        [
                            new Src\Config\FilePattern('"version": "{%version%}"'),
                        ],
                        true,
                    ),
                ],
                $rootPath,
            ),
        ];

        yield 'Composer package (short syntax)' => [
            'composer-package-short',
            new Src\Config\VersionBumperConfig(
                [
                    new Src\Config\Preset\ComposerPackagePreset(),
                ],
                [
                    $fileToModify,
                    new Src\Config\FileToModify(
                        'composer.json',
                        [
                            new Src\Config\FilePattern('"version": "{%version%}"'),
                        ],
                        true,
                    ),
                ],
                $rootPath,
            ),
        ];

        yield 'NPM package' => [
            'npm-package',
            new Src\Config\VersionBumperConfig(
                [
                    new Src\Config\Preset\NpmPackagePreset([
                        'packageName' => '@foo/baz',
                        'path' => 'foo',
                    ]),
                ],
                [
                    $fileToModify,
                    new Src\Config\FileToModify(
                        'foo/package.json',
                        [
                            new Src\Config\FilePattern('"version": "{%version%}"'),
                        ],
                        true,
                    ),
                    new Src\Config\FileToModify(
                        'foo/package-lock.json',
                        [
                            new Src\Config\FilePattern('"name": "@foo/baz",\s+"version": "{%version%}"'),
                        ],
                        true,
                    ),
                ],
                $rootPath,
            ),
        ];

        yield 'TYPO3 extension' => [
            'typo3-extension',
            new Src\Config\VersionBumperConfig(
                [
                    new Src\Config\Preset\Typo3ExtensionPreset(['documentation' => true]),
                ],
                [
                    $fileToModify,
                    new Src\Config\FileToModify(
                        'ext_emconf.php',
                        [
                            new Src\Config\FilePattern("'version' => '{%version%}'"),
                        ],
                        true,
                    ),
                    new Src\Config\FileToModify(
                        'Documentation/guides.xml',
                        [
                            new Src\Config\FilePattern('release="{%version%}"'),
                        ],
                        true,
                    ),
                ],
                $rootPath,
            ),
        ];

        yield 'TYPO3 extension (short syntax)' => [
            'typo3-extension-short',
            new Src\Config\VersionBumperConfig(
                [
                    new Src\Config\Preset\Typo3ExtensionPreset(),
                ],
                [
                    $fileToModify,
                    new Src\Config\FileToModify(
                        'ext_emconf.php',
                        [
                            new Src\Config\FilePattern("'version' => '{%version%}'"),
                        ],
                        true,
                    ),
                    new Src\Config\FileToModify(
                        'Documentation/guides.xml',
                        [
                            new Src\Config\FilePattern('release="{%version%}"'),
                        ],
                        true,
                        false,
                    ),
                    new Src\Config\FileToModify(
                        'Documentation/Settings.cfg',
                        [
                            new Src\Config\FilePattern('release = {%version%}'),
                        ],
                        true,
                        false,
                    ),
                ],
                $rootPath,
            ),
        ];
    }
}
