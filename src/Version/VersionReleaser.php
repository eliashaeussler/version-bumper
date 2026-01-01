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

namespace EliasHaeussler\VersionBumper\Version;

use EliasHaeussler\VersionBumper\Config;
use EliasHaeussler\VersionBumper\Enum;
use EliasHaeussler\VersionBumper\Exception;
use EliasHaeussler\VersionBumper\Helper;
use EliasHaeussler\VersionBumper\Result;
use GitElephant\Command;
use GitElephant\Repository;

use function in_array;

/**
 * VersionReleaser.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final readonly class VersionReleaser
{
    public function __construct(
        private ?Command\Caller\CallerInterface $caller = null,
    ) {}

    /**
     * @param list<Result\VersionBumpResult> $results
     *
     * @throws Exception\AmbiguousVersionsDetected
     * @throws Exception\CouldNotCreateGitTag
     * @throws Exception\NoModifiedFilesFound
     * @throws Exception\TagAlreadyExists
     * @throws Exception\TargetVersionIsMissing
     */
    public function release(
        array $results,
        string $rootPath,
        Config\ReleaseOptions $options = new Config\ReleaseOptions(),
        bool $dryRun = false,
    ): Result\VersionReleaseResult {
        $version = $this->extractVersionFromResults($results);

        if (null === $version) {
            throw new Exception\TargetVersionIsMissing();
        }

        $modifiedFiles = $this->extractModifiedFilesFromResults($results);

        if ([] === $modifiedFiles) {
            throw new Exception\NoModifiedFilesFound();
        }

        $repository = new Repository($rootPath);
        $commitMessage = Helper\VersionHelper::replaceVersionInPattern($options->commitMessage(), $version);
        $tagName = Helper\VersionHelper::replaceVersionInPattern($options->tagName(), $version);
        $commitId = null;

        // Inject custom repository caller
        if (null !== $this->caller) {
            $repository->setCaller($this->caller);
        }

        // Check if tag already exists
        if (null !== $repository->getTag($tagName)) {
            if (!$options->overwriteExistingTag()) {
                throw new Exception\TagAlreadyExists($tagName);
            }

            if (!$dryRun) {
                $repository->deleteTag($tagName);
            }
        }

        if (!$dryRun) {
            // Add and commit modified files
            foreach ($modifiedFiles as $file) {
                $repository->stage($file->path());
            }

            $repository->commit($commitMessage);

            $tagCommand = Command\TagCommand::getInstance($repository)->create($tagName, null, $tagName);

            if ($options->signTag()) {
                $tagCommand .= ' -s';
            }

            $repository->getCaller()->execute($tagCommand);

            $tag = $repository->getTag($tagName) ?? throw new Exception\CouldNotCreateGitTag($tagName);
            $commitId = $tag->getSha();
        }

        return new Result\VersionReleaseResult($modifiedFiles, $commitMessage, $tagName, $commitId);
    }

    /**
     * @param list<Result\VersionBumpResult> $results
     *
     * @throws Exception\AmbiguousVersionsDetected
     */
    private function extractVersionFromResults(array $results): ?Version
    {
        $version = null;

        foreach ($results as $result) {
            foreach ($result->operations() as $operation) {
                $targetVersion = $operation->target();

                if (null === $targetVersion) {
                    continue;
                }

                if (null === $version) {
                    $version = $targetVersion;
                }

                if ($targetVersion->full() !== $version->full()) {
                    throw new Exception\AmbiguousVersionsDetected();
                }
            }
        }

        return $version;
    }

    /**
     * @param list<Result\VersionBumpResult> $results
     *
     * @return list<Config\FileToModify>
     */
    private function extractModifiedFilesFromResults(array $results): array
    {
        $modifiedFiles = [];

        foreach ($results as $result) {
            foreach ($result->operations() as $operation) {
                if (Enum\OperationState::Modified === $operation->state()
                    && !in_array($result->file(), $modifiedFiles, true)
                ) {
                    $modifiedFiles[] = $result->file();
                }
            }
        }

        return $modifiedFiles;
    }
}
