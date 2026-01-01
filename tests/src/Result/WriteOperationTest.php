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
 * WriteOperationTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Result\WriteOperation::class)]
final class WriteOperationTest extends Framework\TestCase
{
    private Src\Result\WriteOperation $subject;

    public function setUp(): void
    {
        $this->subject = new Src\Result\WriteOperation(
            new Src\Version\Version(1, 0, 0),
            new Src\Version\Version(2, 0, 0),
            '',
            new Src\Config\FilePattern('foo: {%version%}'),
            Src\Enum\OperationState::Skipped,
        );
    }

    #[Framework\Attributes\Test]
    public function constructorThrowsExceptionOnMissingSourceVersion(): void
    {
        $this->expectExceptionObject(
            new Src\Exception\SourceVersionIsMissing(),
        );

        new Src\Result\WriteOperation(
            null,
            new Src\Version\Version(2, 0, 0),
            '',
            new Src\Config\FilePattern('foo: {%version%}'),
            Src\Enum\OperationState::Skipped,
        );
    }

    #[Framework\Attributes\Test]
    public function constructorThrowsExceptionOnMissingTargetVersion(): void
    {
        $this->expectExceptionObject(
            new Src\Exception\TargetVersionIsMissing(),
        );

        new Src\Result\WriteOperation(
            new Src\Version\Version(1, 0, 0),
            null,
            '',
            new Src\Config\FilePattern('foo: {%version%}'),
            Src\Enum\OperationState::Skipped,
        );
    }

    #[Framework\Attributes\Test]
    public function constructorThrowsExceptionOnMissingResult(): void
    {
        $this->expectExceptionObject(
            new Src\Exception\VersionBumpResultIsMissing(),
        );

        new Src\Result\WriteOperation(
            new Src\Version\Version(1, 0, 0),
            new Src\Version\Version(2, 0, 0),
            null,
            new Src\Config\FilePattern('foo: {%version%}'),
            Src\Enum\OperationState::Skipped,
        );
    }

    #[Framework\Attributes\Test]
    public function matchedReturnsTrueIfOperationStateIsNotUnmatched(): void
    {
        self::assertTrue($this->subject->matched());

        $subject = Src\Result\WriteOperation::unmatched(
            new Src\Config\FilePattern('foo: {%version%}'),
        );

        self::assertFalse($subject->matched());
    }
}
