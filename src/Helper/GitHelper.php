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
use EliasHaeussler\VersionBumper\Git\DescribeCommand;
use GitElephant\Objects;
use GitElephant\Repository;

use function trim;

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
     * @throws Exception\CannotFetchLastGitTag
     */
    public static function fetchLastVersionTag(Repository $repository): ?Objects\Tag
    {
        $ref = 'HEAD';

        while (true) {
            try {
                $repository->getCaller()->execute(DescribeCommand::getInstance($repository)->lastTag($ref));
            } catch (\Exception $exception) {
                throw new Exception\CannotFetchLastGitTag($exception);
            }

            $tag = trim($repository->getCaller()->getOutput());

            if ('' === $tag) {
                break;
            }

            if (VersionHelper::isValidVersion($tag)) {
                /* @phpstan-ignore method.internal (for the time being, resolved by https://github.com/matteosister/GitElephant/pull/191) */
                return new Objects\Tag($repository, $tag);
            }

            // Test with previous tag
            $ref = $tag.'^';
        }

        return null;
    }
}
