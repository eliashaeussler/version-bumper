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

use Composer\EventDispatcher;
use EliasHaeussler\VersionBumper\Config;
use EliasHaeussler\VersionBumper\Enum;
use EliasHaeussler\VersionBumper\Event;
use EliasHaeussler\VersionBumper\Exception;
use EliasHaeussler\VersionBumper\Helper;
use EliasHaeussler\VersionBumper\Result;
use GitElephant\Command;
use GitElephant\Repository;

use function in_array;
use function is_string;

/**
 * VersionReleaser.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final readonly class VersionReleaser
{
    use Event\Dispatchable;

    public function __construct(
        private ?Command\Caller\CallerInterface $caller = null,
        private ?EventDispatcher\EventDispatcher $eventDispatcher = null,
    ) {}

    /**
     * @param list<Result\VersionBumpResult> $results
     *
     * @throws Exception\AmbiguousVersionsDetected
     * @throws Exception\CannotFetchLatestGitTag
     * @throws Exception\CouldNotCreateGitTag
     * @throws Exception\TagAlreadyExists
     * @throws Exception\TargetVersionIsMissing
     * @throws Exception\VersionIsNotSupported
     */
    public function release(
        array $results,
        string $rootPath,
        Config\ReleaseOptions $options = new Config\ReleaseOptions(),
        Enum\VersionRange|string|null $versionRange = null,
        bool $dryRun = false,
    ): Result\VersionReleaseResult {
        $repository = new Repository($rootPath);

        // Inject custom repository caller
        if (null !== $this->caller) {
            $repository->setCaller($this->caller);
        }

        $version = $this->extractVersionFromResults($results) ?? $this->detectVersionFromVersionRange($versionRange, $repository);

        if (null === $version) {
            throw new Exception\TargetVersionIsMissing();
        }

        $modifiedFiles = $this->extractModifiedFilesFromResults($results);
        $tagName = Helper\VersionHelper::replaceVersionInPattern($options->tagName(), $version);

        $this->dispatchEvent(
            new Event\VersionReleasePrepared($results, $modifiedFiles, $options, $version),
        );

        // Check if tag already exists
        if (null !== $repository->getTag($tagName)) {
            if (!$options->overwriteExistingTag()) {
                throw new Exception\TagAlreadyExists($tagName);
            }

            if (!$dryRun) {
                $repository->deleteTag($tagName);
            }
        }

        [$commitMessage, $commitId] = $this->commitModifiedFiles($modifiedFiles, $repository, $options, $version, $dryRun);

        if (!$dryRun) {
            $tagCommand = Command\TagCommand::getInstance($repository)->create($tagName, null, $tagName);

            if ($options->signTag()) {
                $tagCommand .= ' -s';
            }

            $repository->getCaller()->execute($tagCommand);

            if (null === $repository->getTag($tagName)) {
                throw new Exception\CouldNotCreateGitTag($tagName);
            }
        }

        $result = new Result\VersionReleaseResult($modifiedFiles, $tagName, $commitMessage, $commitId);

        $this->dispatchEvent(
            new Event\VersionReleaseCreated($result),
        );

        return $result;
    }

    /**
     * @param list<Config\FileToModify> $modifiedFiles
     *
     * @return array{string|null, string|null}
     */
    private function commitModifiedFiles(
        array $modifiedFiles,
        Repository $repository,
        Config\ReleaseOptions $options,
        Version $version,
        bool $dryRun,
    ): array {
        if ([] === $modifiedFiles) {
            return [null, null];
        }

        $commitMessage = Helper\VersionHelper::replaceVersionInPattern($options->commitMessage(), $version);

        if ($dryRun) {
            return [$commitMessage, null];
        }

        // Add and commit modified files
        foreach ($modifiedFiles as $file) {
            $repository->stage($file->path());
        }

        $repository->commit($commitMessage);
        $commitId = $repository->getCommit()->getSha();

        return [$commitMessage, $commitId];
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
     * @throws Exception\CannotFetchLatestGitTag
     * @throws Exception\VersionIsNotSupported
     */
    private function detectVersionFromVersionRange(Enum\VersionRange|string|null $versionRange, Repository $repository): ?Version
    {
        if (null === $versionRange) {
            return null;
        }

        if (is_string($versionRange)) {
            return Version::fromFullVersion($versionRange);
        }

        $tag = Helper\GitHelper::fetchLatestVersionTag($repository);

        if (null === $tag) {
            return null;
        }

        return Version::fromFullVersion($tag->getName())->increase($versionRange);
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
                if ($operation->state()->modified() && !in_array($result->file(), $modifiedFiles, true)) {
                    $modifiedFiles[] = $result->file();
                }
            }
        }

        return $modifiedFiles;
    }
}
