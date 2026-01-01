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
use EliasHaeussler\VersionBumper\Tests;
use Generator;
use PHPUnit\Framework;

/**
 * Typo3CommitGuidelinesPresetTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Config\Preset\Typo3CommitGuidelinesPreset::class)]
final class Typo3CommitGuidelinesPresetTest extends Framework\TestCase
{
    private Src\Config\Preset\Typo3CommitGuidelinesPreset $subject;

    public function setUp(): void
    {
        $this->subject = new Src\Config\Preset\Typo3CommitGuidelinesPreset();
    }

    #[Framework\Attributes\Test]
    public function getConfigReturnsResolvedConfig(): void
    {
        $expected = new Src\Config\VersionBumperConfig(
            versionRangeIndicators: [
                new Src\Config\VersionRangeIndicator(
                    Src\Enum\VersionRange::Major,
                    [
                        new Src\Config\VersionRangePattern(
                            Src\Enum\VersionRangeIndicatorType::CommitMessage,
                            '/^\[!!!]/',
                        ),
                    ],
                ),
                new Src\Config\VersionRangeIndicator(
                    Src\Enum\VersionRange::Minor,
                    [
                        new Src\Config\VersionRangePattern(
                            Src\Enum\VersionRangeIndicatorType::CommitMessage,
                            '/^\[FEATURE]/',
                        ),
                    ],
                ),
                new Src\Config\VersionRangeIndicator(
                    Src\Enum\VersionRange::Patch,
                    [
                        new Src\Config\VersionRangePattern(
                            Src\Enum\VersionRangeIndicatorType::CommitMessage,
                            '/^\[(BUGFIX|DOCS|TASK)]/',
                        ),
                    ],
                ),
            ],
        );

        self::assertEquals($expected, $this->subject->getConfig());
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('getConfigReturnsConfigWithVersionRangeIndicatorsDataProvider')]
    public function getConfigReturnsConfigWithVersionRangeIndicators(
        string $commitMessage,
        Src\Enum\VersionRange $expected,
    ): void {
        $caller = new Tests\Fixtures\Classes\DummyCaller();
        $versionRangeDetector = new Src\Version\VersionRangeDetector($caller);
        $indicators = $this->subject->getConfig()->versionRangeIndicators();

        $commit = str_replace(
            'Hello World!',
            $commitMessage,
            (string) file_get_contents(dirname(__DIR__, 2).'/Fixtures/Git/log-commit.txt'),
        );
        $tag = str_replace(
            'Hello World!',
            $commitMessage,
            (string) file_get_contents(dirname(__DIR__, 2).'/Fixtures/Git/show-tag.txt'),
        );
        $diff = (string) file_get_contents(dirname(__DIR__, 2).'/Fixtures/Git/diff-tag-added.txt');

        $caller->results = [
            ['1.2.0', 'tag'],
            ['1.2.0', 'tag'],
            ['08708bc0b5c07a8233b6510c4677ad3ad112d5d4', "rev-list '-n1' 'refs/tags/1.2.0'"],
            [$commit, "log '-s' '--pretty=raw' '--no-color' '--max-count=-1' '--skip=0' 'refs/tags/1.2.0..HEAD'"],
            [$tag, "show '-s' '--pretty=raw' '--no-color' '1.2.0'"],
            [$diff, "diff '--full-index' '--no-color' '--no-ext-diff' '-M' '--dst-prefix=DST/' '--src-prefix=SRC/' '08708bc0b5c07a8233b6510c4677ad3ad112d5d4^..08708bc0b5c07a8233b6510c4677ad3ad112d5d4'"],
        ];

        $actual = $versionRangeDetector->detect(__DIR__, $indicators, '1.2.0');

        self::assertSame($expected, $actual);
    }

    /**
     * @return Generator<string, array{string, Src\Enum\VersionRange}>
     */
    public static function getConfigReturnsConfigWithVersionRangeIndicatorsDataProvider(): Generator
    {
        yield 'breaking change' => ['[!!!][FEATURE] Add breaking feature', Src\Enum\VersionRange::Major];
        yield 'feature' => ['[FEATURE] Add non-breaking feature', Src\Enum\VersionRange::Minor];
        yield 'bugfix' => ['[BUGFIX] Fix something', Src\Enum\VersionRange::Patch];
        yield 'documentation' => ['[DOCS] Document something', Src\Enum\VersionRange::Patch];
        yield 'task' => ['[TASK] Do something', Src\Enum\VersionRange::Patch];
    }
}
