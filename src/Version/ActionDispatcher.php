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

namespace EliasHaeussler\VersionBumper\Version;

use EliasHaeussler\VersionBumper\Config;
use EliasHaeussler\VersionBumper\Result;
use Symfony\Component\Console;

/**
 * ActionDispatcher.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final readonly class ActionDispatcher
{
    public function __construct(
        private string $rootPath,
        private Console\Output\OutputInterface $output = new Console\Output\ConsoleOutput(),
    ) {}

    /**
     * @param list<Action\Action> $actions
     */
    public function dispatchAll(array $actions, Config\FileToModify $sourceFile): Result\ActionExecutionResult
    {
        return (new Action\ActionCollection($actions))->execute($this->rootPath, $sourceFile, $this->output, true);
    }

    public function dispatch(Action\Action $action, Config\FileToModify $sourceFile): Result\ActionExecutionResult
    {
        return $action->execute($this->rootPath, $sourceFile, $this->output);
    }
}
