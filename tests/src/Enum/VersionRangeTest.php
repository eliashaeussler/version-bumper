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

namespace EliasHaeussler\VersionBumper\Tests\Enum;

use EliasHaeussler\VersionBumper as Src;
use Generator;
use PHPUnit\Framework;

/**
 * VersionRangeTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Enum\VersionRange::class)]
final class VersionRangeTest extends Framework\TestCase
{
    #[Framework\Attributes\Test]
    public function fromInputThrowsInputOnInvalidVersionRangeInput(): void
    {
        $this->expectExceptionObject(
            new Src\Exception\VersionRangeIsNotSupported('foo'),
        );

        Src\Enum\VersionRange::fromInput('foo');
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('fromInputSupportsShortRangesDataProvider')]
    public function fromInputSupportsShortRanges(string $input, Src\Enum\VersionRange $expected): void
    {
        self::assertSame($expected, Src\Enum\VersionRange::fromInput($input));
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('fromInputSupportedDefaultRangesDataProvider')]
    public function fromInputSupportedDefaultRanges(string $input, Src\Enum\VersionRange $expected): void
    {
        self::assertSame($expected, Src\Enum\VersionRange::fromInput($input));
    }

    #[Framework\Attributes\Test]
    public function tryFromInputReturnsResolvedVersionRange(): void
    {
        self::assertSame(Src\Enum\VersionRange::Major, Src\Enum\VersionRange::tryFromInput('major'));
    }

    #[Framework\Attributes\Test]
    public function tryFromInputReturnsNullIfVersionRangeIsInvalid(): void
    {
        self::assertNull(Src\Enum\VersionRange::tryFromInput('foo'));
    }

    #[Framework\Attributes\Test]
    public function allReturnsShortAndDefaultRanges(): void
    {
        $expected = [
            'major',
            'maj',
            'minor',
            'min',
            'next',
            'n',
            'patch',
            'p',
        ];

        self::assertSame($expected, Src\Enum\VersionRange::all());
    }

    #[Framework\Attributes\Test]
    public function getHighesReturnsEmptyArrayIfNoVersionRangesAreGiven(): void
    {
        self::assertNull(Src\Enum\VersionRange::getHighest());
    }

    /**
     * @param list<Src\Enum\VersionRange> $ranges
     */
    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('getHighestReturnsVersionRangeWithHighestPriorityDataProvider')]
    public function getHighestReturnsVersionRangeWithHighestPriority(
        array $ranges,
        Src\Enum\VersionRange $expected,
    ): void {
        self::assertSame($expected, Src\Enum\VersionRange::getHighest(...$ranges));
    }

    /**
     * @return Generator<string, array{string, Src\Enum\VersionRange}>
     */
    public static function fromInputSupportsShortRangesDataProvider(): Generator
    {
        yield 'major as maj' => ['maj', Src\Enum\VersionRange::Major];
        yield 'minor as min' => ['min', Src\Enum\VersionRange::Minor];
        yield 'next as n' => ['n', Src\Enum\VersionRange::Next];
        yield 'patch as p' => ['p', Src\Enum\VersionRange::Patch];
    }

    /**
     * @return Generator<string, array{string, Src\Enum\VersionRange}>
     */
    public static function fromInputSupportedDefaultRangesDataProvider(): Generator
    {
        yield 'major' => ['major', Src\Enum\VersionRange::Major];
        yield 'minor' => ['minor', Src\Enum\VersionRange::Minor];
        yield 'next' => ['next', Src\Enum\VersionRange::Next];
        yield 'patch' => ['patch', Src\Enum\VersionRange::Patch];
    }

    /**
     * @return Generator<string, array{list<Src\Enum\VersionRange>, Src\Enum\VersionRange}>
     */
    public static function getHighestReturnsVersionRangeWithHighestPriorityDataProvider(): Generator
    {
        $major = Src\Enum\VersionRange::Major;
        $minor = Src\Enum\VersionRange::Minor;
        $next = Src\Enum\VersionRange::Next;
        $patch = Src\Enum\VersionRange::Patch;

        yield 'major' => [[$next, $major, $patch, $minor], $major];
        yield 'minor' => [[$next, $minor, $patch], $minor];
        yield 'next' => [[$next, $patch, $next], $next];
        yield 'patch' => [[$next, $next, $patch], $patch];
    }
}
