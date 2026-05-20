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

namespace EliasHaeussler\VersionBumper\Tests\Helper;

use EliasHaeussler\VersionBumper as Src;
use EliasHaeussler\VersionBumper\Tests;
use Generator;
use GitElephant\Repository;
use PHPUnit\Framework;

use function dirname;

/**
 * VersionHelperTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Helper\VersionHelper::class)]
final class VersionHelperTest extends Framework\TestCase
{
    private Tests\Fixtures\Classes\DummyCaller $caller;
    private Repository $repository;

    protected function setUp(): void
    {
        $this->caller = new Tests\Fixtures\Classes\DummyCaller();
        $this->repository = new Repository(dirname(__DIR__, 3));
        $this->repository->setCaller($this->caller);
    }

    /**
     * @return Generator<string, array{string, bool}>
     */
    public static function isValidVersionReturnsTrueIfGivenVersionIsValidDataProvider(): Generator
    {
        yield 'valid version' => ['1.2.3', true];
        yield 'valid version with prefix' => ['v1.2.3', true];
        yield 'invalid version' => ['1.2.3-beta.1', false];
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('isValidVersionReturnsTrueIfGivenVersionIsValidDataProvider')]
    public function isValidVersionReturnsTrueIfGivenVersionIsValid(string $version, bool $expected): void
    {
        self::assertSame($expected, Src\Helper\VersionHelper::isValidVersion($version));
    }

    #[Framework\Attributes\Test]
    public function isValidVersionPatternReturnsTrueIfPatternContainsVersionPlaceholder(): void
    {
        self::assertTrue(Src\Helper\VersionHelper::isValidVersionPattern('foo/foo: {%version%}'));
        self::assertFalse(Src\Helper\VersionHelper::isValidVersionPattern('foo'));
    }

    #[Framework\Attributes\Test]
    public function convertPatternToRegularExpressionConvertsPatternToRegularExpression(): void
    {
        self::assertSame(
            '/foo\/foo: (?P<version>v?\d+\.\d+\.\d+)/',
            Src\Helper\VersionHelper::convertPatternToRegularExpression('foo/foo: {%version%}'),
        );
    }

    #[Framework\Attributes\Test]
    public function replaceVersionInPatternReplacesVersionPlaceholderWithGivenVersion(): void
    {
        $version = new Src\Version\Version(1, 2, 3);

        self::assertSame(
            'foo/foo: 1.2.3',
            Src\Helper\VersionHelper::replaceVersionInPattern('foo/foo: {%version%}', $version),
        );
    }

    /**
     * @return Generator<string, array{list<Src\Result\VersionBumpResult>}>
     */
    public static function extractVersionFromResultsReturnsNullIfVersionCannotBeDeterminedDataProvider(): Generator
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
    #[Framework\Attributes\DataProvider('extractVersionFromResultsReturnsNullIfVersionCannotBeDeterminedDataProvider')]
    public function extractVersionFromResultsReturnsNullIfVersionCannotBeDetermined(array $results): void
    {
        self::assertNull(Src\Helper\VersionHelper::extractVersionFromResults($results));
    }

    #[Framework\Attributes\Test]
    public function extractVersionFromResultsThrowsExceptionIfAmbiguousVersionsAreDetected(): void
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

        Src\Helper\VersionHelper::extractVersionFromResults($results);
    }

    #[Framework\Attributes\Test]
    public function detectVersionFromVersionRangeReturnsNullOnMissingVersionRange(): void
    {
        self::assertNull(
            Src\Helper\VersionHelper::detectVersionFromVersionRange(null, $this->repository),
        );
    }

    #[Framework\Attributes\Test]
    public function detectVersionFromVersionRangeReturnsGivenVersion(): void
    {
        $expected = Src\Version\Version::fromFullVersion('1.0.0');

        self::assertEquals(
            $expected,
            Src\Helper\VersionHelper::detectVersionFromVersionRange('1.0.0', $this->repository),
        );
    }

    #[Framework\Attributes\Test]
    public function detectVersionFromVersionRangeReturnsNullIfLatestVersionTagCannotBeDetermined(): void
    {
        $this->caller->addResult('tag', '');

        self::assertNull(
            Src\Helper\VersionHelper::detectVersionFromVersionRange(
                Src\Enum\VersionRange::Major,
                $this->repository,
            ),
        );
    }

    #[Framework\Attributes\Test]
    public function detectVersionFromVersionRangeReturnsIncreasedVersion(): void
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
            ->addResult("log '-s' '--pretty=raw' '--no-color' '--max-count=-1' '--skip=0' 'refs/tags/1.2.0..HEAD'", $commit)
            ->addResult("show '-s' '--pretty=raw' '--no-color' '1.2.0'", $tag)
            ->addResult("diff '--full-index' '--no-color' '--no-ext-diff' '-M' '--dst-prefix=DST/' '--src-prefix=SRC/' '08708bc0b5c07a8233b6510c4677ad3ad112d5d4^..08708bc0b5c07a8233b6510c4677ad3ad112d5d4'", $diff)
        ;

        $expected = Src\Version\Version::fromFullVersion('2.0.0');

        self::assertEquals(
            $expected,
            Src\Helper\VersionHelper::detectVersionFromVersionRange(
                Src\Enum\VersionRange::Major,
                $this->repository,
            ),
        );
    }
}
