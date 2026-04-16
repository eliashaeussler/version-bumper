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
use PHPUnit\Framework;

use function json_encode;

/**
 * Typo3ExtensionPresetTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Config\Preset\Typo3ExtensionPreset::class)]
final class Typo3ExtensionPresetTest extends Framework\TestCase
{
    private Src\Config\Preset\Typo3ExtensionPreset $subject;

    public function setUp(): void
    {
        $this->subject = new Src\Config\Preset\Typo3ExtensionPreset(['documentation' => true]);
    }

    #[Framework\Attributes\Test]
    public function getConfigAllowsSpecialAutoKeywordForDocumentation(): void
    {
        $subject = new Src\Config\Preset\Typo3ExtensionPreset(['documentation' => 'auto']);

        $expected = new Src\Config\VersionBumperConfig(
            filesToModify: [
                new Src\Config\FileToModify(
                    'ext_emconf.php',
                    [
                        new Src\Config\FilePattern("'version' => '{%version%}'"),
                    ],
                    true,
                ),
                new Src\Config\FileToModify(
                    'composer.json',
                    [
                        new Src\Config\FilePattern('"version": "{%version%}'),
                    ],
                    true,
                    true,
                    [],
                    [
                        new Src\Version\Action\ComposerLockAction(),
                    ],
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
        );

        self::assertEquals($expected, $subject->getConfig());
    }

    #[Framework\Attributes\Test]
    public function getConfigAllowsSpecialLegacyKeywordForDocumentation(): void
    {
        $subject = new Src\Config\Preset\Typo3ExtensionPreset(['documentation' => 'legacy']);

        $expected = new Src\Config\VersionBumperConfig(
            filesToModify: [
                new Src\Config\FileToModify(
                    'ext_emconf.php',
                    [
                        new Src\Config\FilePattern("'version' => '{%version%}'"),
                    ],
                    true,
                ),
                new Src\Config\FileToModify(
                    'composer.json',
                    [
                        new Src\Config\FilePattern('"version": "{%version%}'),
                    ],
                    true,
                    true,
                    [],
                    [
                        new Src\Version\Action\ComposerLockAction(),
                    ],
                ),
                new Src\Config\FileToModify(
                    'Documentation/Settings.cfg',
                    [
                        new Src\Config\FilePattern('release = {%version%}'),
                    ],
                    true,
                ),
            ],
        );

        self::assertEquals($expected, $subject->getConfig());
    }

    #[Framework\Attributes\Test]
    public function getConfigReturnsResolvedConfig(): void
    {
        $expected = new Src\Config\VersionBumperConfig(
            filesToModify: [
                new Src\Config\FileToModify(
                    'ext_emconf.php',
                    [
                        new Src\Config\FilePattern("'version' => '{%version%}'"),
                    ],
                    true,
                ),
                new Src\Config\FileToModify(
                    'composer.json',
                    [
                        new Src\Config\FilePattern('"version": "{%version%}'),
                    ],
                    true,
                    true,
                    [],
                    [
                        new Src\Version\Action\ComposerLockAction(),
                    ],
                ),
                new Src\Config\FileToModify(
                    'Documentation/guides.xml',
                    [
                        new Src\Config\FilePattern('release="{%version%}"'),
                    ],
                    true,
                ),
            ],
        );

        self::assertEquals($expected, $this->subject->getConfig());
    }

    #[Framework\Attributes\Test]
    public function getConfigReturnsConfigThatKeepsVersionSuffixInComposerJsonOnVersionBump(): void
    {
        $versionBumper = new Src\Version\VersionBumper();
        $config = $this->subject->getConfig();
        $composerFile = $config->filesToModify()[1];

        self::assertSame('composer.json', $composerFile->path());

        $rootPath = dirname(__DIR__, 2).'/Fixtures/RootPath';
        $contentBackup = file_get_contents($composerFile->fullPath($rootPath));

        self::assertIsString($contentBackup);

        try {
            $expected = json_encode(
                [
                    'version' => '1.1.0',
                    'extra' => [
                        'typo3/cms' => [
                            'version' => '1.1.0+obsolete',
                        ],
                    ],
                ],
                JSON_THROW_ON_ERROR,
            );

            $actual = $versionBumper->bump([$composerFile], $rootPath, Src\Enum\VersionRange::Minor);

            self::assertCount(1, $actual);
            self::assertJsonStringEqualsJsonFile($composerFile->fullPath($rootPath), $expected);
        } finally {
            file_put_contents($composerFile->fullPath($rootPath), $contentBackup);
        }
    }
}
