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

namespace EliasHaeussler\VersionBumper\Enum;

use EliasHaeussler\VersionBumper\Exception;

use function array_search;
use function in_array;
use function strtolower;

/**
 * VersionRange.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
enum VersionRange: string
{
    private const SHORT_RANGES = [
        'maj' => self::Major,
        'min' => self::Minor,
        'n' => self::Next,
        'p' => self::Patch,
    ];

    case Major = 'major';
    case Minor = 'minor';
    case Next = 'next';
    case Patch = 'patch';

    /**
     * @throws Exception\VersionRangeIsNotSupported
     */
    public static function fromInput(string $input): self
    {
        $input = strtolower($input);

        if (!in_array($input, self::all(), true)) {
            throw new Exception\VersionRangeIsNotSupported($input);
        }

        if (isset(self::SHORT_RANGES[$input])) {
            return self::SHORT_RANGES[$input];
        }

        return self::from($input);
    }

    public static function tryFromInput(string $input): ?self
    {
        try {
            return self::fromInput($input);
        } catch (Exception\VersionRangeIsNotSupported) {
            return null;
        }
    }

    /**
     * @return list<non-empty-string>
     */
    public static function all(): array
    {
        $cases = self::cases();
        $all = [];

        foreach ($cases as $case) {
            $all[] = $case->value;

            if (false !== ($short = array_search($case, self::SHORT_RANGES, true))) {
                $all[] = $short;
            }
        }

        return $all;
    }
}
