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

use EliasHaeussler\VersionBumper\Config;
use EliasHaeussler\VersionBumper\Enum;
use EliasHaeussler\VersionBumper\Exception;
use EliasHaeussler\VersionBumper\Result;

use function file_exists;
use function file_put_contents;
use function preg_match_all;
use function strlen;
use function substr_replace;

/**
 * VersionBumper.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class VersionBumper
{
    /**
     * @param list<Config\FileToModify> $files
     *
     * @return list<Result\VersionBumpResult>
     *
     * @throws Exception\FileCouldNotBeModified
     * @throws Exception\FileDoesNotExist
     * @throws Exception\FileIsNotReadable
     * @throws Exception\VersionIsNotSupported
     */
    public function bump(
        array $files,
        string $rootPath,
        Enum\VersionRange|string $versionRange,
    ): array {
        $results = [];

        foreach ($files as $file) {
            $results[] = $this->bumpVersionsInFile($file, $rootPath, $versionRange);
        }

        return $results;
    }

    /**
     * @throws Exception\FileCouldNotBeModified
     * @throws Exception\FileDoesNotExist
     * @throws Exception\FileIsNotReadable
     * @throws Exception\VersionIsNotSupported
     */
    private function bumpVersionsInFile(
        Config\FileToModify $file,
        string $rootPath,
        Enum\VersionRange|string $versionRange,
    ): Result\VersionBumpResult {
        $path = $file->fullPath($rootPath);

        if (!file_exists($path)) {
            throw new Exception\FileDoesNotExist($path);
        }

        $contents = file_get_contents($path);

        if (false === $contents) {
            throw new Exception\FileIsNotReadable($path);
        }

        $modified = $contents;
        $operations = [];

        foreach ($file->patterns() as $pattern) {
            if (preg_match_all($pattern, $contents, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER) > 0) {
                foreach ($matches as ['version' => [$fullVersion, $offset]]) {
                    $operation = $this->buildWriteOperation(
                        Version::fromFullVersion($fullVersion),
                        $versionRange,
                        $modified,
                        $offset,
                    );
                    $modified = $operation->result();
                    $operations[] = $operation;
                }
            }
        }

        $result = new Result\VersionBumpResult($file, $operations);

        // Don't bump unmodified file contents
        if ($modified === $contents) {
            return $result;
        }

        if (false === file_put_contents($path, $modified)) {
            throw new Exception\FileCouldNotBeModified($path);
        }

        return $result;
    }

    /**
     * @throws Exception\VersionIsNotSupported
     */
    private function buildWriteOperation(
        Version $currentVersion,
        Enum\VersionRange|string $versionRange,
        string $contents,
        int $offset,
    ): Result\WriteOperation {
        if ($versionRange instanceof Enum\VersionRange) {
            $newVersion = $currentVersion->increase($versionRange);
        } else {
            $newVersion = Version::fromFullVersion($versionRange);
        }

        $length = strlen($currentVersion->full());
        $modified = substr_replace($contents, $newVersion->full(), $offset, $length);

        if ($modified !== $contents) {
            $state = Enum\OperationState::Modified;
        } else {
            $state = Enum\OperationState::Skipped;
        }

        return new Result\WriteOperation($currentVersion, $newVersion, $modified, $state);
    }
}
