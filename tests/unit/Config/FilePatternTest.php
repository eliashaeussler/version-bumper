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
 * FilePatternTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Config\FilePattern::class)]
final class FilePatternTest extends Framework\TestCase
{
    private Src\Config\FilePattern $subject;

    public function setUp(): void
    {
        $this->subject = new Src\Config\FilePattern('foo/foo: {%version%}');
    }

    #[Framework\Attributes\Test]
    public function constructorThrowsExceptionIfVersionPlaceholderIsMissing(): void
    {
        $this->expectExceptionObject(
            new Src\Exception\FilePatternIsInvalid('foo'),
        );

        new Src\Config\FilePattern('foo');
    }

    #[Framework\Attributes\Test]
    public function constructorConvertsPatternToRegularExpression(): void
    {
        self::assertSame('foo/foo: {%version%}', $this->subject->original());
        self::assertSame('/foo\/foo: (?P<version>v?\d+\.\d+\.\d+)/', $this->subject->regularExpression());
    }
}
