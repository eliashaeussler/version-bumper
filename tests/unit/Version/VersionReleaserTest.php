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
use Generator;
use PHPUnit\Framework;

use function dirname;
use function getcwd;

/**
 * VersionReleaserTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Version\VersionReleaser::class)]
final class VersionReleaserTest extends Framework\TestCase
{
    private Tests\Fixtures\Classes\DummyCaller $caller;
    private Src\Version\VersionReleaser $subject;

    /**
     * @var list<Src\Result\VersionBumpResult>
     */
    private array $results;

    public function setUp(): void
    {
        $cwd = getcwd();

        self::assertIsString($cwd);

        $this->caller = new Tests\Fixtures\Classes\DummyCaller();
        $this->subject = new Src\Version\VersionReleaser($this->caller);
        $this->results = [
            new Src\Result\VersionBumpResult(
                new Src\Config\FileToModify('composer.json'),
                [
                    new Src\Result\WriteOperation(
                        new Src\Version\Version(1, 0, 0),
                        new Src\Version\Version(2, 0, 0),
                        '',
                        new Src\Config\FilePattern('foo: {%version%}'),
                        Src\Enum\OperationState::Modified,
                    ),
                    new Src\Result\WriteOperation(
                        new Src\Version\Version(1, 0, 0),
                        new Src\Version\Version(2, 0, 0),
                        '',
                        new Src\Config\FilePattern('foo: {%version%}'),
                        Src\Enum\OperationState::Modified,
                    ),
                ],
            ),
        ];
    }

    /**
     * @return Generator<string, array{list<Src\Result\VersionBumpResult>}>
     */
    public static function releaseThrowsExceptionIfTargetVersionIsMissingDataProvider(): Generator
    {
        yield 'no results' => [[]];
        yield 'no write operations' => [
            [
                new Src\Result\VersionBumpResult(
                    new Src\Config\FileToModify('foo'),
                    [],
                ),
            ],
        ];
        yield 'missing target version' => [
            [
                new Src\Result\VersionBumpResult(
                    new Src\Config\FileToModify('foo'),
                    [
                        Src\Result\WriteOperation::unmatched(
                            new Src\Config\FilePattern('foo: {%version%}'),
                        ),
                    ],
                ),
            ],
        ];
    }

    /**
     * @param list<Src\Result\VersionBumpResult> $results
     */
    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('releaseThrowsExceptionIfTargetVersionIsMissingDataProvider')]
    public function releaseThrowsExceptionIfTargetVersionIsMissing(array $results): void
    {
        $this->expectExceptionObject(
            new Src\Exception\TargetVersionIsMissing(),
        );

        $this->subject->release($results, dirname(__DIR__, 3));
    }

    #[Framework\Attributes\Test]
    public function releaseThrowsExceptionIfNoVersionsWereTagged(): void
    {
        $this->expectExceptionObject(
            new Src\Exception\TargetVersionIsMissing(),
        );

        $this->subject->release([], dirname(__DIR__, 3), versionRange: Src\Enum\VersionRange::Major);
    }

    #[Framework\Attributes\Test]
    public function releaseThrowsExceptionIfAmbiguousVersionsAreDetected(): void
    {
        $results = [
            new Src\Result\VersionBumpResult(
                new Src\Config\FileToModify('foo'),
                [
                    new Src\Result\WriteOperation(
                        new Src\Version\Version(1, 0, 0),
                        new Src\Version\Version(1, 1, 0),
                        '',
                        new Src\Config\FilePattern('foo: {%version%}'),
                        Src\Enum\OperationState::Modified,
                    ),
                    new Src\Result\WriteOperation(
                        new Src\Version\Version(1, 1, 0),
                        new Src\Version\Version(1, 2, 0),
                        '',
                        new Src\Config\FilePattern('foo: {%version%}'),
                        Src\Enum\OperationState::Modified,
                    ),
                ],
            ),
        ];

        $this->expectExceptionObject(
            new Src\Exception\AmbiguousVersionsDetected(),
        );

        $this->subject->release($results, dirname(__DIR__, 3));
    }

    #[Framework\Attributes\Test]
    public function releaseThrowsExceptionIfTagAlreadyExists(): void
    {
        $this->caller
            ->addResult('tag', '2.0.0')
            ->addResult('tag', '2.0.0')
            ->addResult("rev-list '-n1' 'refs/tags/2.0.0'", '')
        ;

        $this->expectExceptionObject(
            new Src\Exception\TagAlreadyExists('2.0.0'),
        );

        $this->subject->release($this->results, dirname(__DIR__, 3));
    }

    #[Framework\Attributes\Test]
    public function releaseOverwritesExistingTag(): void
    {
        $this->caller
            ->addResult('tag', '2.0.0')
            ->addResult('tag', '2.0.0')
            ->addResult("rev-list '-n1' 'refs/tags/2.0.0'", '4df7df039281b35aca23df13a7ca1f4be1b0e443')
            ->addResult('tag', '2.0.0')
            ->addResult("rev-list '-n1' 'refs/tags/2.0.0'", '4df7df039281b35aca23df13a7ca1f4be1b0e443')
            ->addResult("tag '-d' '2.0.0'", '')
            ->addResult("add '--all' 'composer.json'", '')
            ->addResult("commit '-m' 'Release 2.0.0'", '')
            ->addResult("show '-s' '--pretty=raw' '--no-color' 'HEAD'", 'commit cf79760440d4a34c85cf9ceeefbf2140fad04eb1')
            ->addResult("tag '-m' '2.0.0' '2.0.0'", '')
            ->addResult('tag', '2.0.0')
            ->addResult('tag', '2.0.0')
            ->addResult("rev-list '-n1' 'refs/tags/2.0.0'", 'cf79760440d4a34c85cf9ceeefbf2140fad04eb1')
        ;

        $expected = new Src\Result\VersionReleaseResult(
            [
                $this->results[0]->file(),
            ],
            '2.0.0',
            'Release 2.0.0',
            'cf79760440d4a34c85cf9ceeefbf2140fad04eb1',
        );

        self::assertEquals(
            $expected,
            $this->subject->release(
                $this->results,
                dirname(__DIR__, 3),
                new Src\Config\ReleaseOptions(overwriteExistingTag: true),
            ),
        );
    }

    #[Framework\Attributes\Test]
    public function releaseSignsTag(): void
    {
        $this->caller
            ->addResult('tag', '')
            ->addResult("add '--all' 'composer.json'", '')
            ->addResult("commit '-m' 'Release 2.0.0'", '')
            ->addResult("show '-s' '--pretty=raw' '--no-color' 'HEAD'", 'commit cf79760440d4a34c85cf9ceeefbf2140fad04eb1')
            ->addResult("tag '-m' '2.0.0' '2.0.0' -s", '')
            ->addResult('tag', '2.0.0')
            ->addResult('tag', '2.0.0')
            ->addResult("rev-list '-n1' 'refs/tags/2.0.0'", 'cf79760440d4a34c85cf9ceeefbf2140fad04eb1')
        ;

        $expected = new Src\Result\VersionReleaseResult(
            [
                $this->results[0]->file(),
            ],
            '2.0.0',
            'Release 2.0.0',
            'cf79760440d4a34c85cf9ceeefbf2140fad04eb1',
        );

        self::assertEquals(
            $expected,
            $this->subject->release(
                $this->results,
                dirname(__DIR__, 3),
                new Src\Config\ReleaseOptions(signTag: true),
            ),
        );
    }

    #[Framework\Attributes\Test]
    public function releaseRespectsCustomCommitMessageAndTagName(): void
    {
        $this->caller
            ->addResult('tag', '')
            ->addResult("add '--all' 'composer.json'", '')
            ->addResult("commit '-m' '[RELEASE] Release of xyz 2.0.0'", '')
            ->addResult("show '-s' '--pretty=raw' '--no-color' 'HEAD'", 'commit cf79760440d4a34c85cf9ceeefbf2140fad04eb1')
            ->addResult("tag '-m' 'v2.0.0' 'v2.0.0'", '')
            ->addResult('tag', 'v2.0.0')
            ->addResult('tag', 'v2.0.0')
            ->addResult("rev-list '-n1' 'refs/tags/v2.0.0'", 'cf79760440d4a34c85cf9ceeefbf2140fad04eb1')
        ;

        $expected = new Src\Result\VersionReleaseResult(
            [
                $this->results[0]->file(),
            ],
            'v2.0.0',
            '[RELEASE] Release of xyz 2.0.0',
            'cf79760440d4a34c85cf9ceeefbf2140fad04eb1',
        );

        self::assertEquals(
            $expected,
            $this->subject->release(
                $this->results,
                dirname(__DIR__, 3),
                new Src\Config\ReleaseOptions(
                    '[RELEASE] Release of xyz {%version%}',
                    'v{%version%}',
                ),
            ),
        );
    }

    #[Framework\Attributes\Test]
    public function releaseThrowsExceptionIfTaggingFails(): void
    {
        $this->caller
            ->addResult('tag', '')
            ->addResult("add '--all' 'composer.json'", '')
            ->addResult("commit '-m' 'Release 2.0.0'", '')
            ->addResult("show '-s' '--pretty=raw' '--no-color' 'HEAD'", 'commit cf79760440d4a34c85cf9ceeefbf2140fad04eb1')
            ->addResult("tag '-m' '2.0.0' '2.0.0'", '')
            ->addResult('tag', '')
        ;

        $this->expectExceptionObject(
            new Src\Exception\CouldNotCreateGitTag('2.0.0'),
        );

        $this->subject->release(
            $this->results,
            dirname(__DIR__, 3),
            new Src\Config\ReleaseOptions(),
        );
    }

    #[Framework\Attributes\Test]
    public function releaseCreatesCommitAndTag(): void
    {
        $this->caller
            ->addResult('tag', '')
            ->addResult("add '--all' 'composer.json'", '')
            ->addResult("add '--all' 'composer-lock.json'", '')
            ->addResult("commit '-m' 'Release 2.0.0'", '')
            ->addResult("show '-s' '--pretty=raw' '--no-color' 'HEAD'", 'commit cf79760440d4a34c85cf9ceeefbf2140fad04eb1')
            ->addResult("tag '-m' '2.0.0' '2.0.0'", '')
            ->addResult('tag', '2.0.0')
            ->addResult('tag', '2.0.0')
            ->addResult("rev-list '-n1' 'refs/tags/2.0.0'", 'cf79760440d4a34c85cf9ceeefbf2140fad04eb1')
        ;

        $this->results[] = new Src\Result\VersionBumpResult(
            new Src\Config\FileToModify('composer-lock.json'),
            [
                Src\Result\WriteOperation::regenerated(),
            ],
        );

        $expected = new Src\Result\VersionReleaseResult(
            [
                $this->results[0]->file(),
                $this->results[1]->file(),
            ],
            '2.0.0',
            'Release 2.0.0',
            'cf79760440d4a34c85cf9ceeefbf2140fad04eb1',
        );

        self::assertEquals(
            $expected,
            $this->subject->release(
                $this->results,
                dirname(__DIR__, 3),
                new Src\Config\ReleaseOptions(),
            ),
        );
    }

    #[Framework\Attributes\Test]
    public function releaseCreatesTagForGivenVersionWithoutCommit(): void
    {
        $this->caller
            ->addResult('tag', '')
            ->addResult("tag '-m' '2.0.0' '2.0.0'", '')
            ->addResult('tag', '2.0.0')
            ->addResult('tag', '2.0.0')
            ->addResult("rev-list '-n1' 'refs/tags/2.0.0'", 'cf79760440d4a34c85cf9ceeefbf2140fad04eb1')
        ;

        $expected = new Src\Result\VersionReleaseResult([], '2.0.0', null, null);

        self::assertEquals(
            $expected,
            $this->subject->release(
                [],
                dirname(__DIR__, 3),
                new Src\Config\ReleaseOptions(),
                '2.0.0',
            ),
        );
    }

    #[Framework\Attributes\Test]
    public function releaseCreatesTagForGivenVersionRangeWithoutCommit(): void
    {
        $tags = <<<TAGS
1.0.0
1.0.1
1.1.0
1.2.0
TAGS;

        $commit = (string) file_get_contents(dirname(__DIR__).'/Fixtures/Git/log-commit.txt');
        $tag = (string) file_get_contents(dirname(__DIR__).'/Fixtures/Git/show-tag.txt');
        $diff = (string) file_get_contents(dirname(__DIR__).'/Fixtures/Git/diff-tag-added.txt');

        $this->caller
            ->addResult('tag', $tags)
            ->addResult('tag', $tags)
            ->addResult("rev-list '-n1' 'refs/tags/1.0.0'", '08708bc0b5c07a8233b6510c4677ad3ad112d5d4')
            ->addResult('tag', $tags)
            ->addResult("rev-list '-n1' 'refs/tags/1.0.1'", '08708bc0b5c07a8233b6510c4677ad3ad112d5d4')
            ->addResult('tag', $tags)
            ->addResult("rev-list '-n1' 'refs/tags/1.1.0'", '08708bc0b5c07a8233b6510c4677ad3ad112d5d4')
            ->addResult('tag', $tags)
            ->addResult("rev-list '-n1' 'refs/tags/1.2.0'", '08708bc0b5c07a8233b6510c4677ad3ad112d5d4')
            ->addResult('tag', $tags)
            ->addResult("tag '-m' '2.0.0' '2.0.0'", '')
            ->addResult('tag', $tags.PHP_EOL.'2.0.0')
            ->addResult('tag', $tags.PHP_EOL.'2.0.0')
            ->addResult("rev-list '-n1' 'refs/tags/2.0.0'", 'cf79760440d4a34c85cf9ceeefbf2140fad04eb1')
        ;

        $expected = new Src\Result\VersionReleaseResult([], '2.0.0', null, null);

        self::assertEquals(
            $expected,
            $this->subject->release(
                [],
                dirname(__DIR__, 3),
                new Src\Config\ReleaseOptions(),
                Src\Enum\VersionRange::Major,
            ),
        );
    }

    #[Framework\Attributes\Test]
    public function releaseDoesNotPerformAnyWriteOperationsInDryRunMode(): void
    {
        $this->caller->addResult('tag', '');

        $expected = new Src\Result\VersionReleaseResult(
            [
                $this->results[0]->file(),
            ],
            '2.0.0',
            'Release 2.0.0',
            null,
        );

        self::assertEquals(
            $expected,
            $this->subject->release(
                $this->results,
                dirname(__DIR__, 3),
                new Src\Config\ReleaseOptions(),
                null,
                true,
            ),
        );
    }

    protected function tearDown(): void
    {
        $this->caller->resetOutput();
    }
}
