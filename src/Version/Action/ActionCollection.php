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

namespace EliasHaeussler\VersionBumper\Version\Action;

use ArrayIterator;
use Countable;
use EliasHaeussler\VersionBumper\Config;
use EliasHaeussler\VersionBumper\Result;
use IteratorAggregate;
use Symfony\Component\Console;

use function count;

/**
 * ActionCollection.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 *
 * @internal
 *
 * @implements IteratorAggregate<Action>
 */
final readonly class ActionCollection implements Action, Countable, IteratorAggregate
{
    /**
     * @param list<Action> $actions
     */
    public function __construct(
        private array $actions = [],
    ) {}

    public function execute(
        string $rootPath,
        Config\FileToModify $sourceFile,
        Console\Output\OutputInterface $output,
        bool $failOnFirstError = false,
    ): Result\ActionExecutionResult {
        $exitCode = Console\Command\Command::SUCCESS;
        $collectedOutput = new Console\Output\BufferedOutput();
        $results = [];

        foreach ($this->actions as $action) {
            $actionExecutionResult = $action->execute($rootPath, $sourceFile, $output);

            if ($actionExecutionResult->hasOutput()) {
                $collectedOutput->writeln($actionExecutionResult->output());
            }

            foreach ($actionExecutionResult->results() as $actionResult) {
                $results[] = $actionResult;
            }

            if (!$actionExecutionResult->failed()) {
                continue;
            }

            $exitCode = $actionExecutionResult->exitCode();

            if ($failOnFirstError) {
                break;
            }
        }

        return new Result\ActionExecutionResult($this, $exitCode, $results, $collectedOutput->fetch());
    }

    /**
     * @return list<Action>
     */
    public function actions(): array
    {
        return $this->actions;
    }

    public function count(): int
    {
        return count($this->actions);
    }

    /**
     * @return ArrayIterator<int, Action>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->actions);
    }

    public static function getIdentifier(): string
    {
        return 'collection';
    }
}
