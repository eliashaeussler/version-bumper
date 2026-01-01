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

namespace EliasHaeussler\VersionBumper\Version\RangeDetection;

use EliasHaeussler\VersionBumper\Config;
use EliasHaeussler\VersionBumper\Enum;
use GitElephant\Objects;

use function preg_match;

/**
 * DiffRangeDetection.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final readonly class DiffRangeDetection implements RangeDetection
{
    public function __construct(
        private Objects\Diff\Diff $diff,
    ) {}

    public function matches(Config\VersionRangePattern $pattern): bool
    {
        /** @var Objects\Diff\DiffObject $diffObject */
        foreach ($this->diff as $diffObject) {
            $type = $this->getTypeFromDiffObject($diffObject);
            $path = $diffObject->getOriginalPath();

            if ($type !== $pattern->type()) {
                continue;
            }

            if (1 === preg_match($pattern->pattern(), $path)) {
                return true;
            }
        }

        return false;
    }

    public function supports(Config\VersionRangePattern $pattern): bool
    {
        /** @var Objects\Diff\DiffObject $diffObject */
        foreach ($this->diff as $diffObject) {
            $type = $this->getTypeFromDiffObject($diffObject);

            if ($type === $pattern->type()) {
                return true;
            }
        }

        return false;
    }

    private function getTypeFromDiffObject(Objects\Diff\DiffObject $diffObject): Enum\VersionRangeIndicatorType
    {
        /** @var Objects\Diff\DiffObject::MODE_* $mode */
        $mode = $diffObject->getMode();

        return Enum\VersionRangeIndicatorType::fromDiffMode($mode);
    }
}
