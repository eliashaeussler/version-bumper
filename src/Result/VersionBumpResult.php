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

use function array_values;

/**
 * VersionBumpResult.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final readonly class VersionBumpResult
{
    /**
     * @param list<WriteOperation> $operations
     */
    public function __construct(
        private Config\FileToModify $file,
        private array $operations,
    ) {}

    public function file(): Config\FileToModify
    {
        return $this->file;
    }

    /**
     * @return list<WriteOperation>
     */
    public function operations(): array
    {
        return $this->operations;
    }

    public function hasOperations(): bool
    {
        return [] !== $this->operations;
    }

    /**
     * @return list<non-empty-list<WriteOperation>>
     */
    public function groupedOperations(): array
    {
        $operations = [];

        foreach ($this->operations as $operation) {
            $identifier = implode('_', [
                $operation->source()?->full(),
                $operation->target()?->full(),
                $operation->state()->name,
                Enum\OperationState::Unmatched === $operation->state() ? $operation->pattern()->original() : '',
            ]);

            if (!isset($operations[$identifier])) {
                $operations[$identifier] = [];
            }

            $operations[$identifier][] = $operation;
        }

        return array_values($operations);
    }

    public function hasUnmatchedReports(): bool
    {
        foreach ($this->operations as $operation) {
            if (Enum\OperationState::Unmatched === $operation->state()) {
                return true;
            }
        }

        return false;
    }
}
