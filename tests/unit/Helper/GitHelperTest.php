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
use Exception;
use GitElephant\Repository;
use PHPUnit\Framework;

/**
 * GitHelperTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Helper\GitHelper::class)]
final class GitHelperTest extends Framework\TestCase
{
    private Tests\Fixtures\Classes\DummyCaller $caller;
    private Repository $repository;

    protected function setUp(): void
    {
        $this->caller = new Tests\Fixtures\Classes\DummyCaller();
        $this->repository = new Repository(__DIR__);
        $this->repository->setCaller($this->caller);
    }

    #[Framework\Attributes\Test]
    public function fetchTagReturnsNullOnMissingTag(): void
    {
        $this->caller->addResult('tag', '');

        self::assertNull(Src\Helper\GitHelper::fetchTag('1.2.0', $this->repository));
    }

    #[Framework\Attributes\Test]
    public function fetchTagThrowsExceptionIfGitTagCannotBeFetched(): void
    {
        $exception = new Exception('something went wrong');

        $this->caller->addResult('tag', $exception);

        $this->expectExceptionObject(
            new Src\Exception\CannotFetchGitTag('1.2.0', $exception),
        );

        Src\Helper\GitHelper::fetchTag('1.2.0', $this->repository);
    }

    #[Framework\Attributes\Test]
    public function fetchTagReturnsGivenVersionTag(): void
    {
        $commit = (string) file_get_contents(dirname(__DIR__).'/Fixtures/Git/log-commit.txt');
        $tag = (string) file_get_contents(dirname(__DIR__).'/Fixtures/Git/show-tag.txt');
        $diff = (string) file_get_contents(dirname(__DIR__).'/Fixtures/Git/diff-tag-added.txt');

        $this->caller
            ->addResult('tag', '1.2.0')
            ->addResult('tag', '1.2.0')
            ->addResult("rev-list '-n1' 'refs/tags/1.2.0'", '08708bc0b5c07a8233b6510c4677ad3ad112d5d4')
            ->addResult("log '-s' '--pretty=raw' '--no-color' '--max-count=-1' '--skip=0' 'refs/tags/1.2.0..HEAD'", $commit)
            ->addResult("show '-s' '--pretty=raw' '--no-color' '1.2.0'", $tag)
            ->addResult("diff '--full-index' '--no-color' '--no-ext-diff' '-M' '--dst-prefix=DST/' '--src-prefix=SRC/' '08708bc0b5c07a8233b6510c4677ad3ad112d5d4^..08708bc0b5c07a8233b6510c4677ad3ad112d5d4'", $diff)
        ;

        $actual = Src\Helper\GitHelper::fetchTag('1.2.0', $this->repository);

        self::assertSame('1.2.0', $actual?->getName());
    }

    #[Framework\Attributes\Test]
    public function fetchLatestVersionTagThrowsExceptionIfGitTagsCannotBeFetched(): void
    {
        $exception = new Exception('something went wrong');

        $this->caller->addResult('tag', $exception);

        $this->expectExceptionObject(
            new Src\Exception\CannotFetchLatestGitTag($exception),
        );

        Src\Helper\GitHelper::fetchLatestVersionTag($this->repository);
    }

    #[Framework\Attributes\Test]
    public function fetchLatestVersionTagReturnsNullIfNoTagsAreAvailable(): void
    {
        $this->caller->addResult('tag', '');

        self::assertNull(Src\Helper\GitHelper::fetchLatestVersionTag($this->repository));
    }

    #[Framework\Attributes\Test]
    public function fetchLatestVersionTagReturnsLatestVersionTag(): void
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

        self::assertSame('1.2.0', Src\Helper\GitHelper::fetchLatestVersionTag($this->repository)?->getName());
    }

    protected function tearDown(): void
    {
        $this->caller->resetOutput();
    }
}
