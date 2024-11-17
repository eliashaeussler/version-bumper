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

namespace EliasHaeussler\VersionBumper\Version\RangeDetection;

use EliasHaeussler\VersionBumper\Config;
use EliasHaeussler\VersionBumper\Enum;

use function preg_match;

/**
 * CommitMessagesRangeDetection.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class CommitMessagesRangeDetection implements RangeDetection
{
    /**
     * @param list<string> $commitMessages
     */
    public function __construct(
        private readonly array $commitMessages,
    ) {}

    public function matches(Config\VersionRangePattern $pattern): bool
    {
        foreach ($this->commitMessages as $commitMessage) {
            if (1 === preg_match($pattern->pattern(), $commitMessage)) {
                return true;
            }
        }

        return false;
    }

    public function supports(Config\VersionRangePattern $pattern): bool
    {
        return Enum\VersionRangeIndicatorType::CommitMessage === $pattern->type();
    }
}
