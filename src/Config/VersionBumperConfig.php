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

namespace EliasHaeussler\VersionBumper\Config;

/**
 * VersionBumperConfig.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class VersionBumperConfig
{
    /**
     * @param list<FileToModify> $filesToModify
     */
    public function __construct(
        private readonly array $filesToModify = [],
        private ?string $rootPath = null,
    ) {}

    /**
     * @return list<FileToModify>
     */
    public function filesToModify(): array
    {
        return $this->filesToModify;
    }

    public function performDryRun(bool $dryRun = true): self
    {
        foreach ($this->filesToModify as $file) {
            $file->performDryRun($dryRun);
        }

        return $this;
    }

    public function rootPath(): ?string
    {
        return $this->rootPath;
    }

    public function setRootPath(string $rootPath): self
    {
        $this->rootPath = $rootPath;

        return $this;
    }
}
