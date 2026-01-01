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

namespace EliasHaeussler\VersionBumper\Helper;

use EliasHaeussler\VersionBumper\Version;

use function addcslashes;
use function preg_match;
use function str_contains;
use function str_replace;

/**
 * VersionHelper.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class VersionHelper
{
    private const VERSION_PLACEHOLDER = '{%version%}';
    private const VERSION_REGEX = '(?P<version>v?\\d+\\.\\d+\\.\\d+)';

    public static function isValidVersion(string $version): bool
    {
        return 1 === preg_match('/^'.self::VERSION_REGEX.'$/', $version);
    }

    public static function isValidVersionPattern(string $pattern): bool
    {
        return str_contains($pattern, self::VERSION_PLACEHOLDER);
    }

    public static function convertPatternToRegularExpression(string $pattern): string
    {
        return '/'.str_replace(self::VERSION_PLACEHOLDER, self::VERSION_REGEX, addcslashes($pattern, '/')).'/';
    }

    public static function replaceVersionInPattern(string $pattern, Version\Version $version): string
    {
        return str_replace(self::VERSION_PLACEHOLDER, $version->full(), $pattern);
    }
}
