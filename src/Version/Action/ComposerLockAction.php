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

use Composer\Composer;
use Composer\Console\Application;
use Composer\Factory;
use Composer\Util;
use EliasHaeussler\VersionBumper\Config;
use EliasHaeussler\VersionBumper\Exception;
use EliasHaeussler\VersionBumper\Result;
use Symfony\Component\Console;
use Throwable;

use function dirname;
use function is_file;
use function is_string;

/**
 * ComposerLockAction.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class ComposerLockAction implements Action
{
    /**
     * @throws Exception\FilePatternIsInvalid
     */
    public function execute(
        string $rootPath,
        Config\FileToModify $sourceFile,
        Console\Output\OutputInterface $output,
    ): Result\ActionExecutionResult {
        $composerJson = $sourceFile->fullPath($rootPath);
        $composerLock = Factory::getLockFile($composerJson);
        $composerEnv = Util\Platform::getEnv('COMPOSER');

        // Temporarily override COMPOSER env
        Util\Platform::putEnv('COMPOSER', $composerJson);

        try {
            $application = new Application();
            $application->setAutoExit(false);

            /** @var Composer $composer */
            $composer = $application->getComposer(true, true, true);
            $locker = $composer->getLocker();

            // Early return if lock file does not exist or is up-to-date
            if (!$locker->isLocked() || $locker->isFresh() || !is_file($composerLock)) {
                return Result\ActionExecutionResult::skipped($this);
            }
        } catch (Throwable $exception) {
            $exitCode = $exception->getCode() > 0 ? $exception->getCode() : Console\Command\Command::FAILURE;

            return Result\ActionExecutionResult::failure($this, $exitCode, $exception->getMessage());
        } finally {
            // Restore original COMPOSER env
            if (is_string($composerEnv)) {
                Util\Platform::putEnv('COMPOSER', $composerEnv);
            } else {
                Util\Platform::clearEnv('COMPOSER');
            }
        }

        $commandOutput = new Console\Output\BufferedOutput(
            $output->getVerbosity(),
            $output->isDecorated(),
            $output->getFormatter(),
        );

        $parameters = [
            'command' => 'update',
            '--ignore-platform-reqs' => true,
            '--lock' => true,
            '--no-autoloader' => true,
            '--no-plugins' => true,
            '--no-scripts' => true,
            '--working-dir' => dirname($composerJson),
        ];

        try {
            $exitCode = $application->run(new Console\Input\ArrayInput($parameters), $commandOutput);
        } catch (\Exception) {
            return Result\ActionExecutionResult::failure($this, output: $commandOutput->fetch());
        }

        if (Console\Command\Command::SUCCESS === $exitCode) {
            return Result\ActionExecutionResult::success($this, [
                new Result\VersionBumpResult(
                    new Config\FileToModify($composerLock),
                    [
                        Result\WriteOperation::regenerated(),
                    ],
                ),
            ]);
        }

        return new Result\ActionExecutionResult($this, $exitCode, [], $commandOutput->fetch());
    }

    public static function getIdentifier(): string
    {
        return 'composer-lock';
    }
}
