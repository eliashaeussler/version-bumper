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

namespace EliasHaeussler\VersionBumper\Tests\Command;

use Composer\Composer;
use Composer\Console\Application;
use Composer\Factory;
use Composer\IO\NullIO;
use EliasHaeussler\VersionBumper as Src;
use EliasHaeussler\VersionBumper\Tests;
use PHPUnit\Framework;
use Symfony\Component\Console;

use function chdir;
use function dirname;
use function file_get_contents;
use function getcwd;

/**
 * BumpVersionCommandTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Command\BumpVersionCommand::class)]
final class BumpVersionCommandTest extends Framework\TestCase
{
    private Tests\Fixtures\Classes\DummyCaller $caller;
    private Console\Tester\CommandTester $commandTester;

    public function setUp(): void
    {
        $this->caller = new Tests\Fixtures\Classes\DummyCaller();
        $this->commandTester = $this->createCommandTester();
    }

    #[Framework\Attributes\Test]
    public function executeFailsIfConfigFileIsNotConfigured(): void
    {
        $this->commandTester->execute([
            'range' => 'next',
        ]);

        self::assertSame(Console\Command\Command::INVALID, $this->commandTester->getStatusCode());
        self::assertStringContainsString(
            'Please provide a config file path using the --config option.',
            $this->commandTester->getDisplay(),
        );
    }

    #[Framework\Attributes\Test]
    public function executeUsesConfigFileFromComposerManifest(): void
    {
        $rootPath = dirname(__DIR__).'/Fixtures';

        $composer = Factory::create(new NullIO(), dirname(__DIR__, 3).'/composer.json');
        $composer->getPackage()->setExtra([
            'version-bumper' => [
                'config-file' => $rootPath.'/ConfigFiles/valid-config.json',
            ],
        ]);

        $commandTester = $this->createCommandTester($composer);
        $commandTester->execute([
            'range' => 'next',
            '--dry-run' => true,
        ]);

        self::assertSame(Console\Command\Command::FAILURE, $commandTester->getStatusCode());
        self::assertStringContainsString(
            'File "'.$rootPath.'/foo" does not exist.',
            $commandTester->getDisplay(),
        );
    }

    #[Framework\Attributes\Test]
    public function executeUsesAutoDetectedConfigFile(): void
    {
        $cwd = getcwd();
        $rootPath = dirname(__DIR__).'/Fixtures';

        self::assertIsString($cwd);

        chdir($rootPath.'/ConfigFiles');

        try {
            $this->commandTester->execute([
                'range' => 'next',
            ]);

            self::assertSame(Console\Command\Command::FAILURE, $this->commandTester->getStatusCode());
            self::assertStringContainsString(
                'File "'.$rootPath.'/foo" does not exist.',
                $this->commandTester->getDisplay(),
            );
        } finally {
            chdir($cwd);
        }
    }

    #[Framework\Attributes\Test]
    public function executeConvertsRelativeConfigFilePathToAbsolutePath(): void
    {
        $cwd = getcwd();
        $rootPath = dirname(__DIR__).'/Fixtures';

        self::assertIsString($cwd);

        chdir($rootPath.'/ConfigFiles');

        try {
            $this->commandTester->execute([
                'range' => 'next',
                '--config' => 'valid-config.json',
            ]);

            self::assertSame(Console\Command\Command::FAILURE, $this->commandTester->getStatusCode());
            self::assertStringContainsString(
                'File "'.$rootPath.'/foo" does not exist.',
                $this->commandTester->getDisplay(),
            );
        } finally {
            chdir($cwd);
        }
    }

    #[Framework\Attributes\Test]
    public function executeFailsIfInvalidConfigFileIsGiven(): void
    {
        $configFile = dirname(__DIR__).'/Fixtures/ConfigFiles/invalid-config.json';

        $this->commandTester->execute([
            'range' => 'next',
            '--config' => $configFile,
        ]);

        self::assertSame(Console\Command\Command::FAILURE, $this->commandTester->getStatusCode());
        self::assertMatchesRegularExpression('/Unexpected key(\(s\))? `foo`/', $this->commandTester->getDisplay());
    }

    #[Framework\Attributes\Test]
    public function executeFailsIfVersionRangeIsOmitted(): void
    {
        $configFile = dirname(__DIR__).'/Fixtures/ConfigFiles/valid-config-with-root-path.json';

        $this->commandTester->execute([
            '--config' => $configFile,
        ]);

        self::assertSame(Console\Command\Command::FAILURE, $this->commandTester->getStatusCode());
        self::assertStringContainsString(
            'Please provide a version range or explicit version to bump in configured files.',
            $this->commandTester->getDisplay(),
        );
    }

    #[Framework\Attributes\Test]
    public function executeAutoDetectsVersionRangeIfVersionRangeIsOmitted(): void
    {
        $configFile = dirname(__DIR__).'/Fixtures/ConfigFiles/valid-config-with-indicators.json';

        $commit = (string) file_get_contents(dirname(__DIR__).'/Fixtures/Git/log-commit.txt');
        $tag = (string) file_get_contents(dirname(__DIR__).'/Fixtures/Git/show-tag.txt');
        $diff = (string) file_get_contents(dirname(__DIR__).'/Fixtures/Git/diff-tag-added.txt');

        $this->caller->results = [
            ['1.2.0', 'tag'],
            ['1.2.0', 'tag'],
            ['08708bc0b5c07a8233b6510c4677ad3ad112d5d4', "rev-list '-n1' 'refs/tags/1.2.0'"],
            [$commit, "log '-s' '--pretty=raw' '--no-color' '--max-count=-1' '--skip=0' 'refs/tags/1.2.0..HEAD'"],
            [$tag, "show '-s' '--pretty=raw' '--no-color' '1.2.0'"],
            [$diff, "diff '--full-index' '--no-color' '--no-ext-diff' '-M' '--dst-prefix=DST/' '--src-prefix=SRC/' '08708bc0b5c07a8233b6510c4677ad3ad112d5d4^..08708bc0b5c07a8233b6510c4677ad3ad112d5d4'"],
        ];

        $this->commandTester->execute([
            '--config' => $configFile,
            '--dry-run' => true,
        ]);

        self::assertSame(Console\Command\Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Bumped version from "1.0.0" to "2.0.0"', $this->commandTester->getDisplay());
    }

    #[Framework\Attributes\Test]
    public function executeFailsIfVersionRangeAutoDetectionCouldNotFindTags(): void
    {
        $configFile = dirname(__DIR__).'/Fixtures/ConfigFiles/valid-config-with-indicators.json';

        $this->caller->results = [
            ['', 'tag'],
        ];

        $this->commandTester->execute([
            '--config' => $configFile,
        ]);

        self::assertSame(Console\Command\Command::FAILURE, $this->commandTester->getStatusCode());
        self::assertStringContainsString(
            'Could not find any Git tags in the repository.',
            $this->commandTester->getDisplay(),
        );
    }

    #[Framework\Attributes\Test]
    public function executeFailsIfNoIndicatorsForVersionRangeAutoDetectionMatch(): void
    {
        $configFile = dirname(__DIR__).'/Fixtures/ConfigFiles/valid-config-with-indicators.json';

        $commit = (string) file_get_contents(dirname(__DIR__).'/Fixtures/Git/log-commit.txt');
        $tag = (string) file_get_contents(dirname(__DIR__).'/Fixtures/Git/show-tag.txt');
        $diff = (string) file_get_contents(dirname(__DIR__).'/Fixtures/Git/diff-tag-deleted.txt');

        $this->caller->results = [
            ['1.2.0', 'tag'],
            ['1.2.0', 'tag'],
            ['08708bc0b5c07a8233b6510c4677ad3ad112d5d4', "rev-list '-n1' 'refs/tags/1.2.0'"],
            [$commit, "log '-s' '--pretty=raw' '--no-color' '--max-count=-1' '--skip=0' 'refs/tags/1.2.0..HEAD'"],
            [$tag, "show '-s' '--pretty=raw' '--no-color' '1.2.0'"],
            [$diff, "diff '--full-index' '--no-color' '--no-ext-diff' '-M' '--dst-prefix=DST/' '--src-prefix=SRC/' '08708bc0b5c07a8233b6510c4677ad3ad112d5d4^..08708bc0b5c07a8233b6510c4677ad3ad112d5d4'"],
        ];

        $this->commandTester->execute([
            '--config' => $configFile,
        ]);

        self::assertSame(Console\Command\Command::FAILURE, $this->commandTester->getStatusCode());
        self::assertStringContainsString(
            'Unable to auto-detect version range.',
            $this->commandTester->getDisplay(),
        );
    }

    #[Framework\Attributes\Test]
    public function executeDecoratesAppliedPresets(): void
    {
        $configFile = dirname(__DIR__).'/Fixtures/ConfigFiles/valid-config-with-typo3-extension-preset.json';

        $this->commandTester->execute(
            [
                'range' => '2.0.0',
                '--config' => $configFile,
                '--dry-run' => true,
            ],
            [
                'verbosity' => Console\Output\OutputInterface::VERBOSITY_VERBOSE,
            ],
        );

        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Applied presets', $output);
        self::assertStringContainsString('* TYPO3 extension, managed by ext_emconf.php (typo3-extension)', $output);
    }

    #[Framework\Attributes\Test]
    public function executeDecoratesVersionBumpResult(): void
    {
        $configFile = dirname(__DIR__).'/Fixtures/ConfigFiles/valid-config-with-root-path.json';

        $this->commandTester->execute([
            'range' => '2.0.0',
            '--config' => $configFile,
            '--dry-run' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        self::assertSame(Console\Command\Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Bumped version from "1.0.0" to "2.0.0" (2x)', $output);
        self::assertStringContainsString('Unmatched file pattern: foo: {%version%}', $output);
        self::assertStringContainsString('No write operations were performed (dry-run mode).', $output);
        self::assertStringNotContainsString('Skipped file due to unmodified contents', $output);
        self::assertStringNotContainsString('foobaz', $output);
    }

    #[Framework\Attributes\Test]
    public function executeFailsIfNoVersionToReleaseIsGiven(): void
    {
        $configFile = dirname(__DIR__).'/Fixtures/ConfigFiles/valid-config-with-root-path.json';

        $this->caller->results = [
            ['1.0.0', 'tag'],
            ['1.0.0', 'tag'],
            ['08708bc0b5c07a8233b6510c4677ad3ad112d5d4', "rev-list '-n1' 'refs/tags/1.0.0'"],
        ];

        $this->commandTester->execute([
            'range' => '1.0.0',
            '--config' => $configFile,
            '--dry-run' => true,
            '--release' => true,
        ]);

        self::assertSame(Console\Command\Command::FAILURE, $this->commandTester->getStatusCode());
        self::assertStringContainsString(
            'A tag "1.0.0" already exists in the repository.',
            $this->commandTester->getDisplay(),
        );
    }

    #[Framework\Attributes\Test]
    public function executeDecoratesVersionReleaseResult(): void
    {
        $configFile = dirname(__DIR__).'/Fixtures/ConfigFiles/valid-config-with-root-path.json';

        $this->commandTester->execute([
            'range' => '0.1.0',
            '--config' => $configFile,
            '--dry-run' => true,
            '--release' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        self::assertSame(Console\Command\Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Added 2 files.', $output);
        self::assertStringContainsString('Committed: Release 0.1.0', $output);
        self::assertStringContainsString('Tagged: 0.1.0', $output);
    }

    #[Framework\Attributes\Test]
    public function executeFailsIfUnmatchedPatternIsReportedInStrictMode(): void
    {
        $configFile = dirname(__DIR__).'/Fixtures/ConfigFiles/valid-config-with-root-path.json';

        $this->commandTester->execute([
            'range' => '1.0.0',
            '--config' => $configFile,
            '--dry-run' => true,
            '--strict' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        self::assertSame(Console\Command\Command::FAILURE, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Unmatched file pattern: foo: {%version%}', $output);
        self::assertStringContainsString('Bumped version from "2.0.0" to "1.0.0"', $output);
    }

    protected function tearDown(): void
    {
        $this->caller->results = [];
    }

    private function createCommandTester(?Composer $composer = null): Console\Tester\CommandTester
    {
        $application = new Application();
        $command = new Src\Command\BumpVersionCommand($composer, $this->caller);
        $command->setApplication($application);

        return new Console\Tester\CommandTester($command);
    }
}
