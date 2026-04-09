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

namespace EliasHaeussler\VersionBumper\Result;

use EliasHaeussler\VersionBumper\Version;
use Symfony\Component\Console;

/**
 * ActionExecutionResult.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final readonly class ActionExecutionResult
{
    /**
     * @param list<VersionBumpResult> $results
     */
    public function __construct(
        private Version\Action\Action $action,
        private int $exitCode,
        private array $results,
        private ?string $output = null,
    ) {}

    /**
     * @param list<VersionBumpResult> $results
     */
    public static function success(Version\Action\Action $action, array $results, ?string $output = null): self
    {
        return new self($action, Console\Command\Command::SUCCESS, $results, $output);
    }

    public static function failure(
        Version\Action\Action $action,
        int $exitCode = Console\Command\Command::FAILURE,
        ?string $output = null,
    ): self {
        return new self($action, $exitCode, [], $output);
    }

    public static function skipped(Version\Action\Action $action): self
    {
        return new self($action, 0, []);
    }

    public function action(): Version\Action\Action
    {
        return $this->action;
    }

    public function exitCode(): int
    {
        return $this->exitCode;
    }

    public function successful(): bool
    {
        return Console\Command\Command::SUCCESS === $this->exitCode;
    }

    public function failed(): bool
    {
        return !$this->successful();
    }

    /**
     * @return list<VersionBumpResult>
     */
    public function results(): array
    {
        return $this->results;
    }

    public function output(): ?string
    {
        return $this->output;
    }

    /**
     * @phpstan-assert-if-true !null $this->output()
     */
    public function hasOutput(): bool
    {
        return null !== $this->output && '' !== $this->output;
    }
}
