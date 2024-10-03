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

use EliasHaeussler\VersionBumper\Exception;
use EliasHaeussler\VersionBumper\Helper;

/**
 * ReleaseOptions.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class ReleaseOptions
{
    /**
     * @throws Exception\TagNameIsInvalid
     */
    public function __construct(
        private readonly string $commitMessage = 'Release {%version%}',
        private readonly string $tagName = '{%version%}',
        private readonly bool $overwriteExistingTag = false,
        private readonly bool $signTag = false,
    ) {
        if (!Helper\VersionHelper::isValidVersionPattern($this->tagName)) {
            throw new Exception\TagNameIsInvalid($this->tagName);
        }
    }

    public function commitMessage(): string
    {
        return $this->commitMessage;
    }

    public function tagName(): string
    {
        return $this->tagName;
    }

    public function overwriteExistingTag(): bool
    {
        return $this->overwriteExistingTag;
    }

    public function signTag(): bool
    {
        return $this->signTag;
    }
}
