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

namespace EliasHaeussler\VersionBumper\Config;

use EliasHaeussler\VersionBumper\Exception;
use Symfony\Component\Filesystem;

use function is_string;

/**
 * FileToModify.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class FileToModify
{
    /**
     * @var list<FilePattern>
     */
    private array $patterns = [];

    /**
     * @param list<string|FilePattern> $patterns
     *
     * @throws Exception\FilePatternIsInvalid
     */
    public function __construct(
        private readonly string $path,
        array $patterns = [],
        private readonly bool $reportUnmatched = false,
        private readonly bool $reportMissing = true,
    ) {
        foreach ($patterns as $pattern) {
            $this->add($pattern);
        }
    }

    public function path(): string
    {
        return $this->path;
    }

    public function fullPath(string $rootPath): string
    {
        if (Filesystem\Path::isAbsolute($this->path)) {
            return $this->path;
        }

        return Filesystem\Path::join($rootPath, $this->path);
    }

    /**
     * @return list<FilePattern>
     */
    public function patterns(): array
    {
        return $this->patterns;
    }

    /**
     * @throws Exception\FilePatternIsInvalid
     */
    public function add(string|FilePattern $pattern): self
    {
        if (is_string($pattern)) {
            $pattern = new FilePattern($pattern);
        }

        $this->patterns[] = $pattern;

        return $this;
    }

    public function reportUnmatched(): bool
    {
        return $this->reportUnmatched;
    }

    public function reportMissing(): bool
    {
        return $this->reportMissing;
    }
}
