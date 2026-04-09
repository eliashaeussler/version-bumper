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
use EliasHaeussler\VersionBumper\Tests;
use PHPUnit\Framework;

/**
 * ActionDispatcherTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Version\ActionDispatcher::class)]
final class ActionDispatcherTest extends Framework\TestCase
{
    private Src\Version\ActionDispatcher $subject;

    public function setUp(): void
    {
        $this->subject = new Src\Version\ActionDispatcher('/foo');
    }

    #[Framework\Attributes\Test]
    public function dispatchAllReturnsResultForAllDispatchedActions(): void
    {
        $firstAction = new Tests\Fixtures\Classes\DummyAction();
        $firstAction->result = Src\Result\ActionExecutionResult::success(
            $firstAction,
            [
                new Src\Result\VersionBumpResult(
                    new Src\Config\FileToModify('foo'),
                    [
                        Src\Result\WriteOperation::regenerated(),
                    ],
                ),
            ],
            'foo',
        );

        $secondAction = new Tests\Fixtures\Classes\DummyAction();
        $secondAction->result = Src\Result\ActionExecutionResult::success(
            $secondAction,
            [
                new Src\Result\VersionBumpResult(
                    new Src\Config\FileToModify('bar'),
                    [
                        Src\Result\WriteOperation::regenerated(),
                    ],
                ),
            ],
            'bar',
        );

        $expected = Src\Result\ActionExecutionResult::success(
            new Src\Version\Action\ActionCollection([$firstAction, $secondAction]),
            [
                new Src\Result\VersionBumpResult(
                    new Src\Config\FileToModify('foo'),
                    [
                        Src\Result\WriteOperation::regenerated(),
                    ],
                ),
                new Src\Result\VersionBumpResult(
                    new Src\Config\FileToModify('bar'),
                    [
                        Src\Result\WriteOperation::regenerated(),
                    ],
                ),
            ],
            'foo'.PHP_EOL.'bar'.PHP_EOL,
        );

        self::assertEquals(
            $expected,
            $this->subject->dispatchAll([$firstAction, $secondAction], new Src\Config\FileToModify('foo')),
        );
        self::assertTrue($firstAction->executed);
        self::assertTrue($secondAction->executed);
    }

    #[Framework\Attributes\Test]
    public function dispatchReturnsResultForDispatchedAction(): void
    {
        $action = new Tests\Fixtures\Classes\DummyAction();
        $action->result = Src\Result\ActionExecutionResult::success(
            $action,
            [
                new Src\Result\VersionBumpResult(
                    new Src\Config\FileToModify('foo'),
                    [
                        Src\Result\WriteOperation::regenerated(),
                    ],
                ),
            ],
            'foo',
        );

        self::assertSame(
            $action->result,
            $this->subject->dispatch($action, new Src\Config\FileToModify('foo')),
        );
        self::assertTrue($action->executed);
    }
}
