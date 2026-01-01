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

use EliasHaeussler\VersionBumper\Config;
use EliasHaeussler\VersionBumper\Enum;
use EliasHaeussler\VersionBumper\Exception;
use EliasHaeussler\VersionBumper\Version;

/**
 * WriteOperation.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final readonly class WriteOperation
{
    /**
     * @throws Exception\SourceVersionIsMissing
     * @throws Exception\TargetVersionIsMissing
     * @throws Exception\VersionBumpResultIsMissing
     */
    public function __construct(
        private ?Version\Version $source,
        private ?Version\Version $target,
        private ?string $result,
        private Config\FilePattern $pattern,
        private Enum\OperationState $state,
    ) {
        $this->validate();
    }

    public static function unmatched(Config\FilePattern $pattern): self
    {
        return new self(null, null, null, $pattern, Enum\OperationState::Unmatched);
    }

    public function source(): ?Version\Version
    {
        return $this->source;
    }

    public function target(): ?Version\Version
    {
        return $this->target;
    }

    public function result(): ?string
    {
        return $this->result;
    }

    public function pattern(): Config\FilePattern
    {
        return $this->pattern;
    }

    /**
     * @phpstan-assert-if-true !null $this->source()
     * @phpstan-assert-if-true !null $this->target()
     * @phpstan-assert-if-true !null $this->result()
     */
    public function matched(): bool
    {
        return Enum\OperationState::Unmatched !== $this->state;
    }

    public function state(): Enum\OperationState
    {
        return $this->state;
    }

    /**
     * @throws Exception\SourceVersionIsMissing
     * @throws Exception\TargetVersionIsMissing
     * @throws Exception\VersionBumpResultIsMissing
     */
    private function validate(): void
    {
        if (Enum\OperationState::Unmatched === $this->state) {
            return;
        }

        if (null === $this->source) {
            throw new Exception\SourceVersionIsMissing();
        }
        if (null === $this->target) {
            throw new Exception\TargetVersionIsMissing();
        }
        if (null === $this->result) {
            throw new Exception\VersionBumpResultIsMissing();
        }
    }
}
