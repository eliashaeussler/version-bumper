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

namespace EliasHaeussler\VersionBumper\Error;

use function count;
use function debug_backtrace;
use function explode;
use function is_array;
use function is_object;
use function is_string;
use function str_contains;
use function str_starts_with;
use function substr;
use function trim;

/**
 * DeprecationMessage.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final readonly class DeprecationMessage
{
    public function __construct(
        private string $message,
        private ?string $since = null,
        private ?object $origin = null,
    ) {}

    /**
     * @param array{function?: string, args?: list<mixed>} $trace
     */
    public static function fromTrace(array $trace): ?self
    {
        if ('trigger_deprecation' !== ($trace['function'] ?? null)) {
            return null;
        }

        if (!is_array($trace['args'] ?? null) || count($trace['args']) < 3) {
            return null;
        }

        [$packageName, $since, $message] = $trace['args'];

        if ('eliashaeussler/version-bumper' !== $packageName || !is_string($since) || !is_string($message)) {
            return null;
        }

        return new self($message, $since);
    }

    public static function fromMessage(string $message): ?self
    {
        $initialPhrase = 'Since eliashaeussler/version-bumper';

        if (!str_starts_with($message, $initialPhrase)) {
            return null;
        }

        if (!str_contains($message, ':')) {
            return new self($message);
        }

        [$since, $message] = explode(':', substr($message, strlen($initialPhrase)), 2);

        return new self(trim($message), trim($since));
    }

    public static function fromTraceOrMessage(string $message): ?self
    {
        $trace = debug_backtrace();
        $deprecation = null;

        foreach ($trace as $entry) {
            if (null === $deprecation) {
                if ('trigger_deprecation' === $entry['function'] && !isset($entry['class']) && isset($entry['args'])) {
                    $deprecation = self::fromTrace($entry) ?? self::fromMessage($message);
                }
            } elseif (
                is_object($entry['object'] ?? null)
                && str_starts_with($entry['class'] ?? '', 'EliasHaeussler\\VersionBumper\\')
            ) {
                return $deprecation->withOrigin($entry['object']);
            }
        }

        return $deprecation ?? self::fromMessage($message);
    }

    public function message(): string
    {
        return $this->message;
    }

    public function since(): ?string
    {
        return $this->since;
    }

    public function origin(): ?object
    {
        return $this->origin;
    }

    /**
     * @impure
     */
    public function withOrigin(object $origin): self
    {
        return new self($this->message, $this->since, $origin);
    }
}
