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

use function restore_error_handler;
use function set_error_handler;
use function trigger_deprecation;

/**
 * DeprecationMessageTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Error\DeprecationMessage::class)]
final class DeprecationMessageTest extends Framework\TestCase
{
    #[Framework\Attributes\Test]
    public function fromTraceReturnsNullOnMissingFunction(): void
    {
        self::assertNull(Src\Error\DeprecationMessage::fromTrace([]));
    }

    #[Framework\Attributes\Test]
    public function fromTraceReturnsNullOnUnsupportedFunction(): void
    {
        $trace = [
            'function' => 'foo',
        ];

        self::assertNull(Src\Error\DeprecationMessage::fromTrace($trace));
    }

    #[Framework\Attributes\Test]
    public function fromTraceReturnsNullOnMissingArguments(): void
    {
        $trace = [
            'function' => 'trigger_deprecation',
        ];

        self::assertNull(Src\Error\DeprecationMessage::fromTrace($trace));
    }

    #[Framework\Attributes\Test]
    public function fromTraceReturnsNullOnInvalidNumberOfArguments(): void
    {
        $trace = [
            'function' => 'trigger_deprecation',
            'args' => [],
        ];

        self::assertNull(Src\Error\DeprecationMessage::fromTrace($trace));
    }

    #[Framework\Attributes\Test]
    public function fromTraceReturnsNullOnUnsupportedPackageNameArgument(): void
    {
        $trace = [
            'function' => 'trigger_deprecation',
            'args' => [
                'foo/bar',
                '4.0.0',
                'Option "foo" is deprecated.',
            ],
        ];

        self::assertNull(Src\Error\DeprecationMessage::fromTrace($trace));
    }

    #[Framework\Attributes\Test]
    public function fromTraceReturnsNullOnInvalidSinceArgument(): void
    {
        $trace = [
            'function' => 'trigger_deprecation',
            'args' => [
                'eliashaeussler/version-bumper',
                null,
                'Option "foo" is deprecated.',
            ],
        ];

        self::assertNull(Src\Error\DeprecationMessage::fromTrace($trace));
    }

    #[Framework\Attributes\Test]
    public function fromTraceReturnsNullOnInvalidMessageArgument(): void
    {
        $trace = [
            'function' => 'trigger_deprecation',
            'args' => [
                'eliashaeussler/version-bumper',
                '4.0.0',
                null,
            ],
        ];

        self::assertNull(Src\Error\DeprecationMessage::fromTrace($trace));
    }

    #[Framework\Attributes\Test]
    public function fromTraceReturnsDeprecationMessageObject(): void
    {
        $trace = [
            'function' => 'trigger_deprecation',
            'args' => [
                'eliashaeussler/version-bumper',
                '4.0.0',
                'Option "foo" is deprecated.',
            ],
        ];

        $expected = new Src\Error\DeprecationMessage('Option "foo" is deprecated.', '4.0.0');

        self::assertEquals($expected, Src\Error\DeprecationMessage::fromTrace($trace));
    }

    #[Framework\Attributes\Test]
    public function fromMessageReturnsNullOnInvalidMessagePrefix(): void
    {
        self::assertNull(Src\Error\DeprecationMessage::fromMessage('foo'));
    }

    #[Framework\Attributes\Test]
    public function fromMessageReturnsMessageWithoutInitialVersion(): void
    {
        $message = 'Since eliashaeussler/version-bumper, option "foo" is deprecated.';

        $expected = new Src\Error\DeprecationMessage($message);

        self::assertEquals($expected, Src\Error\DeprecationMessage::fromMessage($message));
    }

    #[Framework\Attributes\Test]
    public function fromMessageReturnsMessageAndInitialVersion(): void
    {
        $message = 'Since eliashaeussler/version-bumper 4.0.0: Option "foo" is deprecated.';

        $expected = new Src\Error\DeprecationMessage('Option "foo" is deprecated.', '4.0.0');

        self::assertEquals($expected, Src\Error\DeprecationMessage::fromMessage($message));
    }

    #[Framework\Attributes\Test]
    public function fromTraceOrMessageReturnsNullOnMissingDeprecationTriggerAndUnsupportedMessage(): void
    {
        self::assertNull(Src\Error\DeprecationMessage::fromTraceOrMessage('Option "foo" is deprecated.'));
    }

    #[Framework\Attributes\Test]
    public function fromTraceOrMessageReturnsMessageOnMissingDeprecationTriggerAndSupportedMessage(): void
    {
        $message = 'Since eliashaeussler/version-bumper 4.0.0: Option "foo" is deprecated.';

        $expected = new Src\Error\DeprecationMessage('Option "foo" is deprecated.', '4.0.0');

        self::assertEquals($expected, Src\Error\DeprecationMessage::fromTraceOrMessage($message));
    }

    #[Framework\Attributes\Test]
    public function fromTraceOrMessageReturnsMessageFromValidTrace(): void
    {
        $actual = null;

        set_error_handler(
            static function (int $errno, string $errstr) use (&$actual) {
                $actual = Src\Error\DeprecationMessage::fromTraceOrMessage($errstr);

                return true;
            },
            E_USER_DEPRECATED,
        );

        trigger_deprecation(
            'eliashaeussler/version-bumper',
            '4.0.0',
            'Option "foo" is deprecated.',
        );

        restore_error_handler();

        $expected = new Src\Error\DeprecationMessage('Option "foo" is deprecated.', '4.0.0', $this);

        self::assertEquals($expected, $actual);
    }
}
