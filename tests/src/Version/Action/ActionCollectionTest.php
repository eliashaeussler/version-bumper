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

namespace EliasHaeussler\VersionBumper\Tests\Version\Action;

use EliasHaeussler\VersionBumper as Src;
use EliasHaeussler\VersionBumper\Tests;
use PHPUnit\Framework;
use Symfony\Component\Console;

/**
 * ActionCollectionTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Version\Action\ActionCollection::class)]
final class ActionCollectionTest extends Framework\TestCase
{
    private Tests\Fixtures\Classes\DummyAction $firstAction;
    private Tests\Fixtures\Classes\DummyAction $secondAction;
    private Src\Version\Action\ActionCollection $subject;

    public function setUp(): void
    {
        $this->firstAction = new Tests\Fixtures\Classes\DummyAction();
        $this->secondAction = new Tests\Fixtures\Classes\DummyAction();
        $this->subject = new Src\Version\Action\ActionCollection([$this->firstAction, $this->secondAction]);
    }

    #[Framework\Attributes\Test]
    public function executeDoesNothingIfNoActionsAreConfigured(): void
    {
        $subject = new Src\Version\Action\ActionCollection();

        $expected = Src\Result\ActionExecutionResult::success($subject, [], '');

        self::assertEquals(
            $expected,
            $subject->execute(
                'foo',
                new Src\Config\FileToModify('bar'),
                new Console\Output\NullOutput(),
            ),
        );
    }

    #[Framework\Attributes\Test]
    public function executeFailsOnFirstErrorIfConfigured(): void
    {
        $this->firstAction->result = Src\Result\ActionExecutionResult::failure($this->firstAction, 123, 'foo');
        $this->secondAction->result = Src\Result\ActionExecutionResult::failure($this->secondAction, 123, 'foo');

        $expected = Src\Result\ActionExecutionResult::failure($this->subject, 123, 'foo'.PHP_EOL);

        self::assertEquals(
            $expected,
            $this->subject->execute(
                'foo',
                new Src\Config\FileToModify('bar'),
                new Console\Output\NullOutput(),
                true,
            ),
        );
        self::assertFalse($this->secondAction->executed);
    }

    #[Framework\Attributes\Test]
    public function executeReturnsFailureResultIfActionExecutionErrorsOccurred(): void
    {
        $this->firstAction->result = Src\Result\ActionExecutionResult::failure($this->firstAction, 123, 'foo');
        $this->secondAction->result = Src\Result\ActionExecutionResult::success(
            $this->secondAction,
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

        $expected = new Src\Result\ActionExecutionResult(
            $this->subject,
            123,
            [
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
            $this->subject->execute(
                'foo',
                new Src\Config\FileToModify('bar'),
                new Console\Output\NullOutput(),
            ),
        );
    }

    #[Framework\Attributes\Test]
    public function executeReturnsSuccessfulResultIfNoActionExecutionErrorsOccurred(): void
    {
        $this->firstAction->result = Src\Result\ActionExecutionResult::success(
            $this->firstAction,
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
        $this->secondAction->result = Src\Result\ActionExecutionResult::success(
            $this->secondAction,
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
            $this->subject,
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
            $this->subject->execute(
                'foo',
                new Src\Config\FileToModify('bar'),
                new Console\Output\NullOutput(),
            ),
        );
    }
}
