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
use Generator;
use PHPUnit\Framework;

/**
 * VersionHelperTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Helper\VersionHelper::class)]
final class VersionHelperTest extends Framework\TestCase
{
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
     * @return Generator<string, array{string, bool}>
     */
    public static function isValidVersionReturnsTrueIfGivenVersionIsValidDataProvider(): Generator
    {
        yield 'valid version' => ['1.2.3', true];
        yield 'valid version with prefix' => ['v1.2.3', true];
        yield 'invalid version' => ['1.2.3-beta.1', false];
    }
}
