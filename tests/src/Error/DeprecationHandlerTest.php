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

namespace EliasHaeussler\VersionBumper\Tests\Error;

use EliasHaeussler\VersionBumper as Src;
use PHPUnit\Framework;
use Symfony\Component\Console;

use function trigger_deprecation;

/**
 * DeprecationHandlerTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Error\DeprecationHandler::class)]
final class DeprecationHandlerTest extends Framework\TestCase
{
    private Src\Error\DeprecationHandler $subject;

    public function setUp(): void
    {
        $this->subject = Src\Error\DeprecationHandler::new();
    }

    #[Framework\Attributes\Test]
    public function newReusesExistingInstance(): void
    {
        self::assertSame($this->subject, Src\Error\DeprecationHandler::new());
    }

    #[Framework\Attributes\Test]
    public function enableRegisterErrorHandler(): void
    {
        $this->subject->enable();

        $this->triggerDeprecation();

        $expected = [
            new Src\Error\DeprecationMessage('Option "foo" is deprecated.', '4.0.0', $this),
        ];

        self::assertEquals($expected, $this->subject->deprecations());
    }

    #[Framework\Attributes\Test]
    public function disableRemovesCollectedDeprecations(): void
    {
        $this->subject->enable();

        $this->triggerDeprecation();

        self::assertNotSame([], $this->subject->deprecations());

        $this->subject->disable();

        self::assertSame([], $this->subject->deprecations());
    }

    #[Framework\Attributes\Test]
    public function decorateDoesNothingIfNoDeprecationsHaveBeenCollected(): void
    {
        $output = new Console\Output\BufferedOutput();
        $io = new Console\Style\SymfonyStyle(new Console\Input\StringInput(''), $output);

        $this->subject->decorate($io);

        self::assertSame('', $output->fetch());
    }

    #[Framework\Attributes\Test]
    public function decorateWritesDeprecationMessagesToOutput(): void
    {
        $output = new Console\Output\BufferedOutput();
        $io = new Console\Style\SymfonyStyle(new Console\Input\StringInput(''), $output);

        $this->subject->enable();

        $this->triggerDeprecation();

        $this->subject->decorate($io);

        $actual = $output->fetch();

        self::assertStringContainsString('Your config file contains deprecated options.', $actual);
        self::assertStringContainsString('Option "foo" is deprecated. (deprecated since v4.0.0)', $actual);
    }

    protected function tearDown(): void
    {
        $this->subject->disable();
    }

    private function triggerDeprecation(): void
    {
        trigger_deprecation(
            'eliashaeussler/version-bumper',
            '4.0.0',
            'Option "foo" is deprecated.',
        );
    }
}
