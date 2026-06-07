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
use GitElephant\Objects;
use PHPUnit\Framework;

/**
 * VersionRangeIndicatorTypeTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Enum\VersionRangeIndicatorType::class)]
final class VersionRangeIndicatorTypeTest extends Framework\TestCase
{
    /**
     * @return Generator<string, array{Objects\Diff\DiffObject::MODE_*, Src\Enum\VersionRangeIndicatorType}>
     */
    public static function fromDiffModeReturnsTypeForGivenModeDataProvider(): Generator
    {
        yield 'deleted file' => [Objects\Diff\DiffObject::MODE_DELETED_FILE, Src\Enum\VersionRangeIndicatorType::FileDeleted];
        yield 'added file' => [Objects\Diff\DiffObject::MODE_NEW_FILE, Src\Enum\VersionRangeIndicatorType::FileAdded];
        yield 'modified file (mode)' => [Objects\Diff\DiffObject::MODE_MODE, Src\Enum\VersionRangeIndicatorType::FileModified];
        yield 'modified file (index)' => [Objects\Diff\DiffObject::MODE_INDEX, Src\Enum\VersionRangeIndicatorType::FileModified];
        yield 'modified file (renamed)' => [Objects\Diff\DiffObject::MODE_RENAMED, Src\Enum\VersionRangeIndicatorType::FileModified];
    }

    /**
     * @param Objects\Diff\DiffObject::MODE_* $mode
     */
    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('fromDiffModeReturnsTypeForGivenModeDataProvider')]
    public function fromDiffModeReturnsTypeForGivenMode(
        string $mode,
        Src\Enum\VersionRangeIndicatorType $expected,
    ): void {
        self::assertSame($expected, Src\Enum\VersionRangeIndicatorType::fromDiffMode($mode));
    }
}
