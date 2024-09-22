<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/version-bumper".
 *
 * Copyright (C) 2024 Elias Häußler <elias@haeussler.dev>
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

use EliasHaeussler\VersionBumper\Enum;
use EliasHaeussler\VersionBumper\Version;

/**
 * WriteOperation.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class WriteOperation
{
    public function __construct(
        private readonly Version\Version $source,
        private readonly Version\Version $target,
        private readonly string $result,
        private readonly Enum\OperationState $state,
    ) {}

    public function source(): Version\Version
    {
        return $this->source;
    }

    public function target(): Version\Version
    {
        return $this->target;
    }

    public function result(): string
    {
        return $this->result;
    }

    public function state(): Enum\OperationState
    {
        return $this->state;
    }
}
