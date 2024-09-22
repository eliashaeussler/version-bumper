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

namespace EliasHaeussler\VersionBumper\Version;

use EliasHaeussler\VersionBumper\Enum;
use EliasHaeussler\VersionBumper\Exception;
use Stringable;

use function preg_match;

/**
 * Version.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class Version implements Stringable
{
    private const VERSION_PATTERN = '/^(?P<major>\d+)\.(?P<minor>\d+)\.(?P<patch>\d+)$/';

    public function __construct(
        private readonly int $major,
        private readonly int $minor,
        private readonly int $patch,
    ) {}

    /**
     * @throws Exception\VersionIsNotSupported
     */
    public static function fromFullVersion(string $fullVersion): self
    {
        if (1 !== preg_match(self::VERSION_PATTERN, $fullVersion, $matches)) {
            throw new Exception\VersionIsNotSupported($fullVersion);
        }

        return new self((int) $matches['major'], (int) $matches['minor'], (int) $matches['patch']);
    }

    public function increase(Enum\VersionRange $range): self
    {
        [$major, $minor, $patch] = match ($range) {
            Enum\VersionRange::Major => [$this->major + 1, 0, 0],
            Enum\VersionRange::Minor => [$this->major, $this->minor + 1, 0],
            Enum\VersionRange::Next, Enum\VersionRange::Patch => [$this->major, $this->minor, $this->patch + 1],
        };

        return new self($major, $minor, $patch);
    }

    public function major(): int
    {
        return $this->major;
    }

    public function minor(): int
    {
        return $this->minor;
    }

    public function patch(): int
    {
        return $this->patch;
    }

    public function full(): string
    {
        return $this->major.'.'.$this->minor.'.'.$this->patch;
    }

    public function __toString(): string
    {
        return $this->full();
    }
}
