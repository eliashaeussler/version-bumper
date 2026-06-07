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
 * ActionExecutionResultTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Result\ActionExecutionResult::class)]
final class ActionExecutionResultTest extends Framework\TestCase
{
    private Src\Result\ActionExecutionResult $subject;

    public function setUp(): void
    {
        $this->subject = Src\Result\ActionExecutionResult::success(
            new Src\Version\Action\ComposerLockAction(),
            [
                new Src\Result\VersionBumpResult(
                    new Src\Config\FileToModify('composer.lock'),
                    [
                        Src\Result\WriteOperation::regenerated(),
                    ],
                ),
            ],
            'foo',
        );
    }

    #[Framework\Attributes\Test]
    public function successfulReturnsTrueIfActionExecutionWasSuccessful(): void
    {
        self::assertTrue($this->subject->successful());

        $subject = Src\Result\ActionExecutionResult::failure(new Src\Version\Action\ComposerLockAction());

        self::assertFalse($subject->successful());
    }

    #[Framework\Attributes\Test]
    public function failedReturnsTrueIfActionExecutionFailed(): void
    {
        self::assertFalse($this->subject->failed());

        $subject = Src\Result\ActionExecutionResult::failure(new Src\Version\Action\ComposerLockAction());

        self::assertTrue($subject->failed());
    }

    #[Framework\Attributes\Test]
    public function hasOutputReturnsTrueIfActionExecutionGeneratedOutput(): void
    {
        self::assertTrue($this->subject->hasOutput());

        $subject = Src\Result\ActionExecutionResult::skipped(new Src\Version\Action\ComposerLockAction());

        self::assertFalse($subject->hasOutput());
    }
}
