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

use EliasHaeussler\VersionBumper as Src;
use PHPUnit\Framework;

use function basename;
use function glob;

/**
 * VersionBumperConfigTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Config\VersionBumperConfig::class)]
final class VersionBumperConfigTest extends Framework\TestCase
{
    private Src\Config\VersionBumperConfig $subject;

    public function setUp(): void
    {
        $this->subject = new Src\Config\VersionBumperConfig(
            presets: [
                new Src\Config\Preset\Typo3ExtensionPreset(),
            ],
            filesToModify: [
                new Src\Config\FileToModify(
                    '*Test.php',
                    [
                        '{%version%}',
                    ],
                    postActions: [
                        new Src\Version\Action\ComposerLockAction(),
                    ],
                ),
                new Src\Config\FileToModify(
                    __DIR__.'/File*Test.php',
                    [
                        '{%version%}',
                    ],
                ),
            ],
            releaseOptions: new Src\Config\ReleaseOptions(signTag: false),
            versionRangeIndicators: [
                new Src\Config\VersionRangeIndicator(
                    Src\Enum\VersionRange::Minor,
                    [
                        new Src\Config\VersionRangePattern(
                            Src\Enum\VersionRangeIndicatorType::CommitMessage,
                            '/^\[FEATURE]/',
                        ),
                    ],
                ),
            ],
        );
    }

    #[Framework\Attributes\Test]
    public function constructorSkipsRelativePathsForWildcardResolutionIfRootPathIsMissing(): void
    {
        $expected = [
            new Src\Config\FileToModify(
                '*Test.php',
                [
                    '{%version%}',
                ],
                postActions: [
                    new Src\Version\Action\ComposerLockAction(),
                ],
            ),
            new Src\Config\FileToModify(
                __DIR__.'/FilePatternTest.php',
                [
                    '{%version%}',
                ],
            ),
            new Src\Config\FileToModify(
                __DIR__.'/FileToModifyTest.php',
                [
                    '{%version%}',
                ],
            ),
        ];

        self::assertEquals($expected, $this->subject->filesToModify());
    }

    #[Framework\Attributes\Test]
    public function constructorResolvesWildcardsInFiles(): void
    {
        $subject = new Src\Config\VersionBumperConfig(
            filesToModify: [
                new Src\Config\FileToModify(
                    '*Test.php',
                    [
                        '{%version%}',
                    ],
                ),
            ],
            rootPath: __DIR__,
        );

        $files = glob(__DIR__.'/*Test.php');
        $expected = [];

        self::assertIsArray($files);

        foreach ($files as $file) {
            $expected[] = new Src\Config\FileToModify(
                basename($file),
                [
                    '{%version%}',
                ],
            );
        }

        self::assertEquals($expected, $subject->filesToModify());
    }

    #[Framework\Attributes\Test]
    public function hasActionsReturnsTrueIfAnyConfiguredFileHasActionsOfGivenType(): void
    {
        self::assertTrue($this->subject->hasActions(Src\Version\Action\ActionType::PostAction));
        self::assertFalse($this->subject->hasActions(Src\Version\Action\ActionType::PreAction));

        $subject = new Src\Config\VersionBumperConfig();

        self::assertFalse($subject->hasActions(Src\Version\Action\ActionType::PreAction));
        self::assertFalse($subject->hasActions(Src\Version\Action\ActionType::PostAction));
    }

    #[Framework\Attributes\Test]
    public function setRootPathResolvesWildcardsInFiles(): void
    {
        $subject = new Src\Config\VersionBumperConfig(
            filesToModify: [
                new Src\Config\FileToModify(
                    '*Test.php',
                    [
                        '{%version%}',
                    ],
                ),
            ],
        );

        self::assertEquals(
            [
                new Src\Config\FileToModify('*Test.php', ['{%version%}']),
            ],
            $subject->filesToModify(),
        );

        $files = glob(__DIR__.'/*Test.php');
        $expected = [];

        self::assertIsArray($files);

        foreach ($files as $file) {
            $expected[] = new Src\Config\FileToModify(basename($file), ['{%version%}']);
        }

        $subject->setRootPath(__DIR__);

        self::assertEquals($expected, $subject->filesToModify());
    }

    #[Framework\Attributes\Test]
    public function mergeSkipsDefaultValuesFromOtherConfig(): void
    {
        $other = new Src\Config\VersionBumperConfig(releaseOptions: new Src\Config\ReleaseOptions());

        self::assertEquals($this->subject, $this->subject->merge($other));
    }

    #[Framework\Attributes\Test]
    public function mergeMergesArrayPropertiesFromCurrentConfigWithArrayPropertiesFromOtherConfig(): void
    {
        $other = new Src\Config\VersionBumperConfig(
            [
                new Src\Config\Preset\NpmPackagePreset(['path' => 'Build/Frontend']),
            ],
        );

        $expected = new Src\Config\VersionBumperConfig(
            presets: [
                new Src\Config\Preset\Typo3ExtensionPreset(),
                new Src\Config\Preset\NpmPackagePreset(['path' => 'Build/Frontend']),
            ],
            filesToModify: [
                new Src\Config\FileToModify(
                    '*Test.php',
                    [
                        '{%version%}',
                    ],
                    postActions: [
                        new Src\Version\Action\ComposerLockAction(),
                    ],
                ),
                new Src\Config\FileToModify(
                    __DIR__.'/FilePatternTest.php',
                    [
                        '{%version%}',
                    ],
                ),
                new Src\Config\FileToModify(
                    __DIR__.'/FileToModifyTest.php',
                    [
                        '{%version%}',
                    ],
                ),
            ],
            releaseOptions: new Src\Config\ReleaseOptions(signTag: false),
            versionRangeIndicators: [
                new Src\Config\VersionRangeIndicator(
                    Src\Enum\VersionRange::Minor,
                    [
                        new Src\Config\VersionRangePattern(
                            Src\Enum\VersionRangeIndicatorType::CommitMessage,
                            '/^\[FEATURE]/',
                        ),
                    ],
                ),
            ],
        );

        self::assertEquals($expected, $this->subject->merge($other));
    }

    #[Framework\Attributes\Test]
    public function mergeUsesNonDefaultAndNonArrayPropertyFromOtherConfig(): void
    {
        $other = new Src\Config\VersionBumperConfig(
            rootPath: '/baz',
        );

        $expected = new Src\Config\VersionBumperConfig(
            [
                new Src\Config\Preset\Typo3ExtensionPreset(),
            ],
            [
                new Src\Config\FileToModify(
                    '*Test.php',
                    [
                        '{%version%}',
                    ],
                    postActions: [
                        new Src\Version\Action\ComposerLockAction(),
                    ],
                ),
                new Src\Config\FileToModify(
                    __DIR__.'/FilePatternTest.php',
                    [
                        '{%version%}',
                    ],
                ),
                new Src\Config\FileToModify(
                    __DIR__.'/FileToModifyTest.php',
                    [
                        '{%version%}',
                    ],
                ),
            ],
            '/baz',
            new Src\Config\ReleaseOptions(signTag: false),
            [
                new Src\Config\VersionRangeIndicator(
                    Src\Enum\VersionRange::Minor,
                    [
                        new Src\Config\VersionRangePattern(
                            Src\Enum\VersionRangeIndicatorType::CommitMessage,
                            '/^\[FEATURE]/',
                        ),
                    ],
                ),
            ],
        );

        self::assertEquals($expected, $this->subject->merge($other));
    }
}
