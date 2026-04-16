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

namespace EliasHaeussler\VersionBumper\Event;

use Composer\EventDispatcher;
use EliasHaeussler\VersionBumper\Config;
use EliasHaeussler\VersionBumper\Enum;
use EliasHaeussler\VersionBumper\Result;

/**
 * VersionBumped.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class VersionBumped extends EventDispatcher\Event
{
    public function __construct(
        private readonly Config\FileToModify $fileToModify,
        private readonly Enum\VersionRange|string $versionRange,
        private readonly Result\VersionBumpResult $result,
        private readonly bool $dryRun,
    ) {
        parent::__construct('version-bumped');
    }

    public function fileToModify(): Config\FileToModify
    {
        return $this->fileToModify;
    }

    public function versionRange(): string|Enum\VersionRange
    {
        return $this->versionRange;
    }

    public function result(): Result\VersionBumpResult
    {
        return $this->result;
    }

    public function isDryRun(): bool
    {
        return $this->dryRun;
    }
}
