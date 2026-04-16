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

use EliasHaeussler\VersionBumper\Config;
use EliasHaeussler\VersionBumper\Result;
use Symfony\Component\Console;
use Symfony\Component\Process;

use function dirname;
use function implode;
use function is_file;
use function iterator_to_array;

/**
 * PackageLockAction.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final readonly class PackageLockAction implements Action
{
    public function execute(
        string $rootPath,
        Config\FileToModify $sourceFile,
        Console\Output\OutputInterface $output,
    ): Result\ActionExecutionResult {
        $npmPrefix = dirname($sourceFile->fullPath($rootPath));
        $lockFilename = $npmPrefix.'/package-lock.json';
        $resultOutput = new Console\Output\BufferedOutput();

        // Early return if lock file does not exist
        if (!is_file($lockFilename)) {
            return Result\ActionExecutionResult::skipped($this);
        }

        $process = new Process\Process([
            'npm',
            'install',
            '--prefix',
            $npmPrefix,
            '--package-lock-only',
        ]);

        $exitCode = $process->run();

        if ($process->isSuccessful()) {
            return Result\ActionExecutionResult::success($this, [
                new Result\VersionBumpResult(
                    new Config\FileToModify($lockFilename),
                    [
                        Result\WriteOperation::regenerated(),
                    ],
                ),
            ]);
        }

        // Merge stdout and stderr
        $resultOutput->writeln(
            implode('', iterator_to_array($process)),
        );

        return new Result\ActionExecutionResult($this, $exitCode, [], $resultOutput->fetch());
    }

    public static function getIdentifier(): string
    {
        return 'package-lock';
    }
}
