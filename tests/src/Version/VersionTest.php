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
use Generator;
use PHPUnit\Framework;

/**
 * VersionTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Version\Version::class)]
final class VersionTest extends Framework\TestCase
{
    private Src\Version\Version $subject;

    public function setUp(): void
    {
        $this->subject = new Src\Version\Version(1, 2, 3);
    }

    #[Framework\Attributes\Test]
    public function fromFullVersionThrowsExceptionIfInvalidVersionIsGiven(): void
    {
        $this->expectExceptionObject(
            new Src\Exception\VersionIsNotSupported('foo'),
        );

        Src\Version\Version::fromFullVersion('foo');
    }

    #[Framework\Attributes\Test]
    public function fromFullVersionReturnsVersionWithExtractedVersionParts(): void
    {
        self::assertEquals($this->subject, Src\Version\Version::fromFullVersion('1.2.3'));
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('increaseReturnsIncreasedStableVersionDataProvider')]
    public function increaseReturnsIncreasedStableVersion(Src\Enum\VersionRange $range, Src\Version\Version $expected): void
    {
        self::assertEquals($expected, $this->subject->increase($range));
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('increaseReturnsIncreasedUnstableVersionDataProvider')]
    public function increaseReturnsIncreasedUnstableVersion(Src\Enum\VersionRange $range, Src\Version\Version $expected): void
    {
        $subject = new Src\Version\Version(0, 1, 2);

        self::assertEquals($expected, $subject->increase($range));
    }

    #[Framework\Attributes\Test]
    public function fullReturnsFullVersion(): void
    {
        self::assertSame('1.2.3', $this->subject->full());
    }

    #[Framework\Attributes\Test]
    public function stringRepresentationReturnsFullVersion(): void
    {
        self::assertSame('1.2.3', (string) $this->subject);
    }

    /**
     * @return Generator<string, array{Src\Enum\VersionRange, Src\Version\Version}>
     */
    public static function increaseReturnsIncreasedStableVersionDataProvider(): Generator
    {
        yield 'major' => [Src\Enum\VersionRange::Major, new Src\Version\Version(2, 0, 0)];
        yield 'minor' => [Src\Enum\VersionRange::Minor, new Src\Version\Version(1, 3, 0)];
        yield 'next' => [Src\Enum\VersionRange::Next, new Src\Version\Version(1, 2, 4)];
        yield 'patch' => [Src\Enum\VersionRange::Patch, new Src\Version\Version(1, 2, 4)];
    }

    /**
     * @return Generator<string, array{Src\Enum\VersionRange, Src\Version\Version}>
     */
    public static function increaseReturnsIncreasedUnstableVersionDataProvider(): Generator
    {
        yield 'major' => [Src\Enum\VersionRange::Major, new Src\Version\Version(0, 2, 0)];
        yield 'minor' => [Src\Enum\VersionRange::Minor, new Src\Version\Version(0, 1, 3)];
        yield 'next' => [Src\Enum\VersionRange::Next, new Src\Version\Version(0, 1, 3)];
        yield 'patch' => [Src\Enum\VersionRange::Patch, new Src\Version\Version(0, 1, 3)];
    }
}
