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

namespace EliasHaeussler\VersionBumper\Tests\Result;

use EliasHaeussler\VersionBumper as Src;
use PHPUnit\Framework;

/**
 * VersionBumpResultTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Result\VersionBumpResult::class)]
final class VersionBumpResultTest extends Framework\TestCase
{
    private Src\Result\VersionBumpResult $subject;

    public function setUp(): void
    {
        $this->subject = new Src\Result\VersionBumpResult(
            new Src\Config\FileToModify(
                'package-lock.json',
                [
                    '"name": "foo/baz",\s+"version": "{%version%}"',
                ],
            ),
            [
                new Src\Result\WriteOperation(
                    new Src\Version\Version(1, 2, 3),
                    new Src\Version\Version(2, 0, 0),
                    '"name": "foo/baz",
        "version": "2.0.0"',
                    new Src\Config\FilePattern('"version": "{%version%}"'),
                    Src\Enum\OperationState::Modified,
                ),
                new Src\Result\WriteOperation(
                    new Src\Version\Version(1, 2, 3),
                    new Src\Version\Version(2, 0, 0),
                    '"name": "foo/baz",
    "version": "2.0.0"',
                    new Src\Config\FilePattern('"version": "{%version%}"'),
                    Src\Enum\OperationState::Modified,
                ),
                new Src\Result\WriteOperation(
                    new Src\Version\Version(1, 2, 0),
                    new Src\Version\Version(2, 0, 0),
                    '"name": "foo/baz",
    "version": "2.0.0"',
                    new Src\Config\FilePattern('"version": "{%version%}"'),
                    Src\Enum\OperationState::Modified,
                ),
                Src\Result\WriteOperation::unmatched(
                    new Src\Config\FilePattern('"foo/version": "{%version%}"'),
                ),
                Src\Result\WriteOperation::unmatched(
                    new Src\Config\FilePattern('"baz/version": "{%version%}"'),
                ),
            ],
        );
    }

    #[Framework\Attributes\Test]
    public function groupedOperationsReturnsWriteOperationsGroupedByUniqueness(): void
    {
        $expected = [
            // 1.2.3 => 2.0.0
            [
                new Src\Result\WriteOperation(
                    new Src\Version\Version(1, 2, 3),
                    new Src\Version\Version(2, 0, 0),
                    '"name": "foo/baz",
        "version": "2.0.0"',
                    new Src\Config\FilePattern('"version": "{%version%}"'),
                    Src\Enum\OperationState::Modified,
                ),
                new Src\Result\WriteOperation(
                    new Src\Version\Version(1, 2, 3),
                    new Src\Version\Version(2, 0, 0),
                    '"name": "foo/baz",
    "version": "2.0.0"',
                    new Src\Config\FilePattern('"version": "{%version%}"'),
                    Src\Enum\OperationState::Modified,
                ),
            ],
            // 1.2.0 => 2.0.0
            [
                new Src\Result\WriteOperation(
                    new Src\Version\Version(1, 2, 0),
                    new Src\Version\Version(2, 0, 0),
                    '"name": "foo/baz",
    "version": "2.0.0"',
                    new Src\Config\FilePattern('"version": "{%version%}"'),
                    Src\Enum\OperationState::Modified,
                ),
            ],
            // Unmatched: "foo/version": "{%version%}"
            [
                Src\Result\WriteOperation::unmatched(
                    new Src\Config\FilePattern('"foo/version": "{%version%}"'),
                ),
            ],
            // Unmatched: "baz/version": "{%version%}"
            [
                Src\Result\WriteOperation::unmatched(
                    new Src\Config\FilePattern('"baz/version": "{%version%}"'),
                ),
            ],
        ];

        self::assertEquals($expected, $this->subject->groupedOperations());
    }

    #[Framework\Attributes\Test]
    public function hasUnmatchedReportsReturnsTrueIfAnyWriteOperationHasUnmatchedOperationState(): void
    {
        self::assertTrue($this->subject->hasUnmatchedReports());

        $subject = new Src\Result\VersionBumpResult(
            new Src\Config\FileToModify(
                'package-lock.json',
                [
                    '"name": "foo/baz",\s+"version": "{%version%}"',
                ],
            ),
            [
                new Src\Result\WriteOperation(
                    new Src\Version\Version(1, 2, 3),
                    new Src\Version\Version(2, 0, 0),
                    '"name": "foo/baz",
        "version": "2.0.0"',
                    new Src\Config\FilePattern('"version": "{%version%}"'),
                    Src\Enum\OperationState::Modified,
                ),
                new Src\Result\WriteOperation(
                    new Src\Version\Version(1, 2, 3),
                    new Src\Version\Version(2, 0, 0),
                    '"name": "foo/baz",
    "version": "2.0.0"',
                    new Src\Config\FilePattern('"version": "{%version%}"'),
                    Src\Enum\OperationState::Modified,
                ),
                new Src\Result\WriteOperation(
                    new Src\Version\Version(1, 2, 0),
                    new Src\Version\Version(2, 0, 0),
                    '"name": "foo/baz",
    "version": "2.0.0"',
                    new Src\Config\FilePattern('"version": "{%version%}"'),
                    Src\Enum\OperationState::Modified,
                ),
            ],
        );

        self::assertFalse($subject->hasUnmatchedReports());
    }
}
