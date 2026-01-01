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

namespace EliasHaeussler\VersionBumper\Tests\Version;

use EliasHaeussler\VersionBumper as Src;
use EliasHaeussler\VersionBumper\Tests;
use Exception;
use Generator;
use PHPUnit\Framework;

/**
 * VersionRangeDetectorTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Version\VersionRangeDetector::class)]
final class VersionRangeDetectorTest extends Framework\TestCase
{
    private Tests\Fixtures\Classes\DummyCaller $caller;
    private Src\Version\VersionRangeDetector $subject;

    public function setUp(): void
    {
        $this->caller = new Tests\Fixtures\Classes\DummyCaller();
        $this->subject = new Src\Version\VersionRangeDetector($this->caller);
    }

    #[Framework\Attributes\Test]
    public function detectThrowsExceptionIfGivenVersionTagDoesNotExist(): void
    {
        $this->expectExceptionObject(
            new Src\Exception\GitTagDoesNotExist('1.2.0'),
        );

        $this->subject->detect(__DIR__, [], '1.2.0');
    }

    #[Framework\Attributes\Test]
    public function detectThrowsExceptionIfGivenVersionTagCannotBeRead(): void
    {
        $this->caller->results = [
            [new Exception('something went wrong'), 'tag'],
        ];

        $this->expectExceptionObject(
            new Src\Exception\CannotFetchGitTag('1.2.0'),
        );

        $this->subject->detect(__DIR__, [], '1.2.0');
    }

    #[Framework\Attributes\Test]
    public function detectThrowsExceptionIfLatestVersionTagCannotBeRead(): void
    {
        $this->caller->results = [
            [new Exception('something went wrong'), 'tag'],
        ];

        $this->expectExceptionObject(
            new Src\Exception\CannotFetchLatestGitTag(),
        );

        $this->subject->detect(__DIR__, []);
    }

    #[Framework\Attributes\Test]
    public function detectThrowsExceptionIfNoTagsAreAvailable(): void
    {
        $this->caller->results = [
            ['', 'tag'],
        ];

        $this->expectExceptionObject(
            new Src\Exception\NoGitTagsFound(),
        );

        $this->subject->detect(__DIR__, []);
    }

    #[Framework\Attributes\Test]
    public function detectThrowsExceptionIfNoVersionTagsAreAvailable(): void
    {
        $this->caller->results = [
            ['foo', 'tag'],
            ['foo', 'tag'],
            ['08708bc0b5c07a8233b6510c4677ad3ad112d5d4', "rev-list '-n1' 'refs/tags/foo'"],
        ];

        $this->expectExceptionObject(
            new Src\Exception\NoGitTagsFound(),
        );

        $this->subject->detect(__DIR__, []);
    }

    #[Framework\Attributes\Test]
    public function detectThrowsExceptionIfCommitMessagesCannotBeRead(): void
    {
        $this->caller->results = [
            ['1.2.0', 'tag'],
            ['1.2.0', 'tag'],
            ['08708bc0b5c07a8233b6510c4677ad3ad112d5d4', "rev-list '-n1' 'refs/tags/1.2.0'"],
            [new Exception('something went wrong'), "log '-s' '--pretty=raw' '--no-color' '--max-count=-1' '--skip=0' 'refs/tags/1.2.0..HEAD'"],
        ];

        $this->expectExceptionObject(
            new Src\Exception\CannotFetchGitCommits('1.2.0..HEAD'),
        );

        $this->subject->detect(__DIR__, [], '1.2.0');
    }

    /**
     * @param list<Src\Config\VersionRangeIndicator> $indicators
     */
    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('detectReturnsAutoDetectedVersionRangeForGivenVersionTagDataProvider')]
    public function detectReturnsAutoDetectedVersionRangeForGivenVersionTag(
        array $indicators,
        ?Src\Enum\VersionRange $expected,
    ): void {
        $commit = (string) file_get_contents(dirname(__DIR__).'/Fixtures/Git/log-commit.txt');
        $tag = (string) file_get_contents(dirname(__DIR__).'/Fixtures/Git/show-tag.txt');
        $diff = (string) file_get_contents(dirname(__DIR__).'/Fixtures/Git/diff-tag-added.txt');

        $this->caller->results = [
            ['1.2.0', 'tag'],
            ['1.2.0', 'tag'],
            ['08708bc0b5c07a8233b6510c4677ad3ad112d5d4', "rev-list '-n1' 'refs/tags/1.2.0'"],
            [$commit, "log '-s' '--pretty=raw' '--no-color' '--max-count=-1' '--skip=0' 'refs/tags/1.2.0..HEAD'"],
            [$tag, "show '-s' '--pretty=raw' '--no-color' '1.2.0'"],
            [$diff, "diff '--full-index' '--no-color' '--no-ext-diff' '-M' '--dst-prefix=DST/' '--src-prefix=SRC/' '08708bc0b5c07a8233b6510c4677ad3ad112d5d4^..08708bc0b5c07a8233b6510c4677ad3ad112d5d4'"],
        ];

        $actual = $this->subject->detect(__DIR__, $indicators, '1.2.0');

        self::assertSame($expected, $actual);
    }

    #[Framework\Attributes\Test]
    public function detectReturnsAutoDetectedVersionRangeForLatestVersionTag(): void
    {
        $indicators = [
            new Src\Config\VersionRangeIndicator(
                Src\Enum\VersionRange::Patch,
                [
                    new Src\Config\VersionRangePattern(
                        Src\Enum\VersionRangeIndicatorType::FileAdded,
                        '/^README\.md$/',
                    ),
                ],
            ),
        ];

        $tags = <<<TAGS
1.0.0
1.0.1
1.1.0
1.2.0
TAGS;

        $commit = (string) file_get_contents(dirname(__DIR__).'/Fixtures/Git/log-commit.txt');
        $tag = (string) file_get_contents(dirname(__DIR__).'/Fixtures/Git/show-tag.txt');
        $diff = (string) file_get_contents(dirname(__DIR__).'/Fixtures/Git/diff-tag-added.txt');

        $this->caller->results = [
            [$tags, 'tag'],
            [$tags, 'tag'],
            ['08708bc0b5c07a8233b6510c4677ad3ad112d5d4', "rev-list '-n1' 'refs/tags/1.0.0'"],
            [$tags, 'tag'],
            ['08708bc0b5c07a8233b6510c4677ad3ad112d5d4', "rev-list '-n1' 'refs/tags/1.0.1'"],
            [$tags, 'tag'],
            ['08708bc0b5c07a8233b6510c4677ad3ad112d5d4', "rev-list '-n1' 'refs/tags/1.1.0'"],
            [$tags, 'tag'],
            ['08708bc0b5c07a8233b6510c4677ad3ad112d5d4', "rev-list '-n1' 'refs/tags/1.2.0'"],
            [$commit, "log '-s' '--pretty=raw' '--no-color' '--max-count=-1' '--skip=0' 'refs/tags/1.2.0..HEAD'"],
            [$tag, "show '-s' '--pretty=raw' '--no-color' '1.2.0'"],
            [$diff, "diff '--full-index' '--no-color' '--no-ext-diff' '-M' '--dst-prefix=DST/' '--src-prefix=SRC/' '08708bc0b5c07a8233b6510c4677ad3ad112d5d4^..08708bc0b5c07a8233b6510c4677ad3ad112d5d4'"],
        ];

        $actual = $this->subject->detect(__DIR__, $indicators);

        self::assertSame(Src\Enum\VersionRange::Patch, $actual);
    }

    /**
     * @return Generator<string, array{list<Src\Config\VersionRangeIndicator>, Src\Enum\VersionRange|null}>
     */
    public static function detectReturnsAutoDetectedVersionRangeForGivenVersionTagDataProvider(): Generator
    {
        $matchedCommitMessage = new Src\Config\VersionRangePattern(
            Src\Enum\VersionRangeIndicatorType::CommitMessage,
            '/Hello World/',
        );
        $unmatchedCommitMessage = new Src\Config\VersionRangePattern(
            Src\Enum\VersionRangeIndicatorType::CommitMessage,
            '/foo/',
        );

        $matchedFileAdded = new Src\Config\VersionRangePattern(
            Src\Enum\VersionRangeIndicatorType::FileAdded,
            '/^README\.md$/',
        );
        $unmatchedFileAdded = new Src\Config\VersionRangePattern(
            Src\Enum\VersionRangeIndicatorType::FileAdded,
            '/foo/',
        );

        yield 'no matches (without indicators)' => [[], null];
        yield 'no matches (with matchAny strategy)' => [
            [
                new Src\Config\VersionRangeIndicator(
                    Src\Enum\VersionRange::Patch,
                    [
                        $unmatchedFileAdded,
                        $unmatchedCommitMessage,
                    ],
                ),
            ],
            null,
        ];
        yield 'no matches (with matchAll strategy)' => [
            [
                new Src\Config\VersionRangeIndicator(
                    Src\Enum\VersionRange::Patch,
                    [
                        $matchedFileAdded,
                        $unmatchedCommitMessage,
                    ],
                    Src\Enum\VersionRangeIndicatorStrategy::MatchAll,
                ),
            ],
            null,
        ];
        yield 'no matches (with matchNone strategy)' => [
            [
                new Src\Config\VersionRangeIndicator(
                    Src\Enum\VersionRange::Patch,
                    [
                        $matchedFileAdded,
                        $unmatchedCommitMessage,
                    ],
                    Src\Enum\VersionRangeIndicatorStrategy::MatchNone,
                ),
            ],
            null,
        ];

        yield 'matches (with matchAny strategy)' => [
            [
                new Src\Config\VersionRangeIndicator(
                    Src\Enum\VersionRange::Patch,
                    [
                        $unmatchedFileAdded,
                        $matchedCommitMessage,
                    ],
                ),
            ],
            Src\Enum\VersionRange::Patch,
        ];
        yield 'matches (with matchAll strategy)' => [
            [
                new Src\Config\VersionRangeIndicator(
                    Src\Enum\VersionRange::Patch,
                    [
                        $matchedCommitMessage,
                        $matchedFileAdded,
                    ],
                    Src\Enum\VersionRangeIndicatorStrategy::MatchAll,
                ),
            ],
            Src\Enum\VersionRange::Patch,
        ];
        yield 'matches (with matchNone strategy)' => [
            [
                new Src\Config\VersionRangeIndicator(
                    Src\Enum\VersionRange::Patch,
                    [
                        $unmatchedCommitMessage,
                        $unmatchedFileAdded,
                    ],
                    Src\Enum\VersionRangeIndicatorStrategy::MatchNone,
                ),
            ],
            Src\Enum\VersionRange::Patch,
        ];

        yield 'no matches (with multiple indicators)' => [
            [
                new Src\Config\VersionRangeIndicator(
                    Src\Enum\VersionRange::Patch,
                    [
                        $unmatchedCommitMessage,
                        $unmatchedFileAdded,
                    ],
                    Src\Enum\VersionRangeIndicatorStrategy::MatchAll,
                ),
                new Src\Config\VersionRangeIndicator(
                    Src\Enum\VersionRange::Minor,
                    [
                        $matchedFileAdded,
                    ],
                    Src\Enum\VersionRangeIndicatorStrategy::MatchNone,
                ),
                new Src\Config\VersionRangeIndicator(
                    Src\Enum\VersionRange::Major,
                    [
                        $unmatchedCommitMessage,
                    ],
                ),
            ],
            null,
        ];

        yield 'matches (with multiple indicators)' => [
            [
                new Src\Config\VersionRangeIndicator(
                    Src\Enum\VersionRange::Patch,
                    [
                        $matchedCommitMessage,
                        $matchedFileAdded,
                    ],
                    Src\Enum\VersionRangeIndicatorStrategy::MatchAll,
                ),
                new Src\Config\VersionRangeIndicator(
                    Src\Enum\VersionRange::Minor,
                    [
                        $unmatchedFileAdded,
                    ],
                    Src\Enum\VersionRangeIndicatorStrategy::MatchNone,
                ),
                new Src\Config\VersionRangeIndicator(
                    Src\Enum\VersionRange::Major,
                    [
                        $unmatchedCommitMessage,
                    ],
                ),
            ],
            Src\Enum\VersionRange::Minor,
        ];
    }
}
