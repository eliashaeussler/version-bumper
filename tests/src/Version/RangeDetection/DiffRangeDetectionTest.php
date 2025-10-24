<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/version-bumper".
 *
 * Copyright (C) 2024-2025 Elias Häußler <elias@haeussler.dev>
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

namespace EliasHaeussler\VersionBumper\Tests\Version\RangeDetection;

use EliasHaeussler\VersionBumper as Src;
use GitElephant\Objects;
use GitElephant\Repository;
use PHPUnit\Framework;

use function dirname;
use function file_get_contents;

/**
 * DiffRangeDetectionTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Version\RangeDetection\DiffRangeDetection::class)]
final class DiffRangeDetectionTest extends Framework\TestCase
{
    private Objects\Diff\Diff $diff;
    private Src\Version\RangeDetection\DiffRangeDetection $subject;

    public function setUp(): void
    {
        $this->diff = new Objects\Diff\Diff(new Repository(__DIR__));
        $this->subject = new Src\Version\RangeDetection\DiffRangeDetection($this->diff);
    }

    #[Framework\Attributes\Test]
    public function matchesReturnsFalseIfNoDiffObjectsAreStoredInDiff(): void
    {
        $pattern = new Src\Config\VersionRangePattern(
            Src\Enum\VersionRangeIndicatorType::FileAdded,
            '/foo/',
        );

        self::assertFalse($this->subject->matches($pattern));
    }

    #[Framework\Attributes\Test]
    public function matchesReturnsFalseIfNoDiffObjectMatchesGivenTypeOfPattern(): void
    {
        $pattern = new Src\Config\VersionRangePattern(
            Src\Enum\VersionRangeIndicatorType::FileAdded,
            '/foo/',
        );

        $diff = (string) file_get_contents(dirname(__DIR__, 2).'/Fixtures/Git/diff-tag-deleted.txt');
        $lines = array_map(rtrim(...), explode(PHP_EOL, $diff));

        $this->diff[] = new Objects\Diff\DiffObject($lines);

        self::assertFalse($this->subject->matches($pattern));
    }

    #[Framework\Attributes\Test]
    public function matchesReturnsFalseIfNoDiffObjectMatchesGivenPattern(): void
    {
        $pattern = new Src\Config\VersionRangePattern(
            Src\Enum\VersionRangeIndicatorType::FileAdded,
            '/foo/',
        );

        $diff = (string) file_get_contents(dirname(__DIR__, 2).'/Fixtures/Git/diff-tag-added.txt');
        $lines = array_map(rtrim(...), explode(PHP_EOL, $diff));

        $this->diff[] = new Objects\Diff\DiffObject($lines);

        self::assertFalse($this->subject->matches($pattern));
    }

    #[Framework\Attributes\Test]
    public function matchesReturnsTrueIfAnyDiffObjectMatchesGivenPattern(): void
    {
        $pattern = new Src\Config\VersionRangePattern(
            Src\Enum\VersionRangeIndicatorType::FileAdded,
            '/^README\.md$/',
        );

        $diff = (string) file_get_contents(dirname(__DIR__, 2).'/Fixtures/Git/diff-tag-added.txt');
        $lines = array_map(rtrim(...), explode(PHP_EOL, $diff));

        $this->diff[] = new Objects\Diff\DiffObject($lines);

        self::assertTrue($this->subject->matches($pattern));
    }

    #[Framework\Attributes\Test]
    public function supportsReturnsFalseIfNoDiffObjectsAreStoredInDiff(): void
    {
        $pattern = new Src\Config\VersionRangePattern(
            Src\Enum\VersionRangeIndicatorType::FileAdded,
            '/foo/',
        );

        self::assertFalse($this->subject->supports($pattern));
    }

    #[Framework\Attributes\Test]
    public function supportsReturnsFalseIfNoDiffObjectMatchesGivenTypeOfPattern(): void
    {
        $pattern = new Src\Config\VersionRangePattern(
            Src\Enum\VersionRangeIndicatorType::FileAdded,
            '/foo/',
        );

        $diff = (string) file_get_contents(dirname(__DIR__, 2).'/Fixtures/Git/diff-tag-deleted.txt');
        $lines = array_map(rtrim(...), explode(PHP_EOL, $diff));

        $this->diff[] = new Objects\Diff\DiffObject($lines);

        self::assertFalse($this->subject->supports($pattern));
    }

    #[Framework\Attributes\Test]
    public function supportsReturnsTrueIfAnyDiffObjectMatchesGivenTypeOfPattern(): void
    {
        $pattern = new Src\Config\VersionRangePattern(
            Src\Enum\VersionRangeIndicatorType::FileAdded,
            '/foo/',
        );

        $diff = (string) file_get_contents(dirname(__DIR__, 2).'/Fixtures/Git/diff-tag-added.txt');
        $lines = array_map(rtrim(...), explode(PHP_EOL, $diff));

        $this->diff[] = new Objects\Diff\DiffObject($lines);

        self::assertTrue($this->subject->supports($pattern));
    }
}
