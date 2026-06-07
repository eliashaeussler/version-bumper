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
use PHPUnit\Framework;
use Symfony\Component\Console;
use Symfony\Component\Filesystem;

use function chdir;
use function dirname;
use function getcwd;

/**
 * PackageLockActionTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Version\Action\PackageLockAction::class)]
final class PackageLockActionTest extends Framework\TestCase
{
    private Filesystem\Filesystem $filesystem;
    private Src\Version\Action\PackageLockAction $subject;
    private Console\Output\BufferedOutput $output;
    private string $rootPath;
    private string $cwd;

    public function setUp(): void
    {
        $this->filesystem = new Filesystem\Filesystem();
        $this->subject = new Src\Version\Action\PackageLockAction();
        $this->output = new Console\Output\BufferedOutput();
        $this->rootPath = dirname(__DIR__, 2).'/Fixtures/RootPathTemp';

        $cwd = getcwd();

        self::assertIsString($cwd);

        $this->cwd = $cwd;

        $this->prepareRootPath();
    }

    #[Framework\Attributes\Test]
    public function executeDoesNothingIfLockFileDoesNotExist(): void
    {
        $packageLock = $this->rootPath.'/package-lock.json';

        self::assertFileExists($packageLock);

        $this->filesystem->remove($packageLock);

        self::assertFileDoesNotExist($packageLock);

        $expected = Src\Result\ActionExecutionResult::skipped($this->subject);

        self::assertEquals(
            $expected,
            $this->subject->execute(
                $this->rootPath,
                new Src\Config\FileToModify('package.json'),
                $this->output,
            ),
        );
    }

    #[Framework\Attributes\Test]
    public function executeReturnsSuccessfulActionExecutionResult(): void
    {
        $this->prepareRootPath('RootPathOutdatedLockFile');

        $expected = Src\Result\ActionExecutionResult::success(
            $this->subject,
            [
                new Src\Result\VersionBumpResult(
                    new Src\Config\FileToModify($this->rootPath.'/package-lock.json'),
                    [
                        Src\Result\WriteOperation::regenerated(),
                    ],
                ),
            ],
        );

        self::assertEquals(
            $expected,
            $this->subject->execute(
                $this->rootPath,
                new Src\Config\FileToModify('composer.json'),
                $this->output,
            ),
        );
    }

    #[Framework\Attributes\Test]
    public function executeReturnsFailedActionExecutionResultOnFailure(): void
    {
        $this->prepareRootPath('RootPathMalformedManifestFile');

        $actual = $this->subject->execute(
            $this->rootPath,
            new Src\Config\FileToModify('composer.json'),
            $this->output,
        );

        self::assertTrue($actual->failed());
        self::assertSame([], $actual->results());
        self::assertStringContainsString('npm error code EJSONPARSE', (string) $actual->output());
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->rootPath);

        chdir($this->cwd);
    }

    private function prepareRootPath(string $base = 'RootPath'): void
    {
        $baseRootPath = dirname(__DIR__, 2).'/Fixtures/'.$base;

        $this->filesystem->remove($this->rootPath);
        $this->filesystem->mirror($baseRootPath, $this->rootPath);

        chdir($this->rootPath);
    }
}
