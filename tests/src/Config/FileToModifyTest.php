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

/**
 * FileToModifyTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Config\FileToModify::class)]
final class FileToModifyTest extends Framework\TestCase
{
    private Src\Config\FileToModify $subject;

    public function setUp(): void
    {
        $this->subject = new Src\Config\FileToModify('foo');
    }

    #[Framework\Attributes\Test]
    public function constructorAddsGivenPatterns(): void
    {
        $patterns = [
            'foo: {%version%}',
            'baz: {%version%}',
        ];

        $subject = new Src\Config\FileToModify('foo', $patterns);

        self::assertCount(2, $subject->patterns());
    }

    #[Framework\Attributes\Test]
    public function fullPathReturnsJoinedPath(): void
    {
        self::assertSame('/baz/foo', $this->subject->fullPath('/baz'));
    }

    #[Framework\Attributes\Test]
    public function fullPathReturnsConfiguredAbsolutePath(): void
    {
        $subject = new Src\Config\FileToModify('/foo/baz');

        self::assertSame('/foo/baz', $subject->fullPath(__DIR__));
    }

    #[Framework\Attributes\Test]
    public function addAcceptsFilePatternString(): void
    {
        $this->subject->add('foo/foo: {%version%}');

        $expected = new Src\Config\FilePattern('foo/foo: {%version%}');

        self::assertCount(1, $this->subject->patterns());
        self::assertEquals($expected, $this->subject->patterns()[0]);
    }

    #[Framework\Attributes\Test]
    public function addAcceptsFilePatternObject(): void
    {
        $pattern = new Src\Config\FilePattern('foo/foo: {%version%}');

        $this->subject->add($pattern);

        self::assertCount(1, $this->subject->patterns());
        self::assertSame($pattern, $this->subject->patterns()[0]);
    }
}
