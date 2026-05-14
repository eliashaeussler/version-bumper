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

use EliasHaeussler\VersionBumper\Exception;
use EliasHaeussler\VersionBumper\Version;
use GitElephant\Objects;
use GitElephant\Repository;

/**
 * GitHelper.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class GitHelper
{
    /**
     * @throws Exception\CannotFetchGitTag
     */
    public static function fetchTag(string $tagName, Repository $repository): ?Objects\Tag
    {
        try {
            return $repository->getTag($tagName);
        } catch (\Exception $exception) {
            throw new Exception\CannotFetchGitTag($tagName, $exception);
        }
    }

    /**
     * @throws Exception\CannotFetchLatestGitTag
     * @throws Exception\VersionIsNotSupported
     */
    public static function fetchLatestVersionTag(Repository $repository): ?Objects\Tag
    {
        try {
            /** @var list<Objects\Tag> $tags */
            $tags = $repository->getTags();
        } catch (\Exception $exception) {
            throw new Exception\CannotFetchLatestGitTag($exception);
        }

        // Drop all non-version tags
        $tags = array_filter(
            $tags,
            static fn (Objects\Tag $tag) => VersionHelper::isValidVersion($tag->getName()),
        );

        // Early return if no version tags are left
        if ([] === $tags) {
            return null;
        }

        // Sort version tags by descending version number
        usort(
            $tags,
            static function (Objects\Tag $a, Objects\Tag $b) {
                $a = Version\Version::fromFullVersion($a->getName());
                $b = Version\Version::fromFullVersion($b->getName());

                return version_compare($a->full(), $b->full());
            },
        );

        return array_pop($tags);
    }
}
