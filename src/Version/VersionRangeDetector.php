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
use GitElephant\Command;
use GitElephant\Objects;
use GitElephant\Repository;

use function array_filter;
use function array_map;
use function array_values;
use function iterator_to_array;
use function sprintf;

/**
 * VersionRangeDetector.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final readonly class VersionRangeDetector
{
    public function __construct(
        private ?Command\Caller\CallerInterface $caller = null,
    ) {}

    /**
     * @param list<Config\VersionRangeIndicator> $indicators
     *
     * @throws Exception\CannotFetchGitCommits
     * @throws Exception\CannotFetchGitTag
     * @throws Exception\CannotFetchLatestGitTag
     * @throws Exception\GitTagDoesNotExist
     * @throws Exception\NoGitTagsFound
     * @throws Exception\VersionIsNotSupported
     */
    public function detect(string $rootPath, array $indicators, ?string $since = null): ?Enum\VersionRange
    {
        $repository = new Repository($rootPath);
        $detectedRanges = [];

        // Inject custom repository caller
        if (null !== $this->caller) {
            $repository->setCaller($this->caller);
        }

        // Fetch tag used for comparison
        if (null !== $since) {
            $tag = Helper\GitHelper::fetchTag($since, $repository) ?? throw new Exception\GitTagDoesNotExist($since);
        } else {
            $tag = Helper\GitHelper::fetchLatestVersionTag($repository) ?? throw new Exception\NoGitTagsFound();
        }

        // Fetch relevant Git information
        $commitMessages = $this->fetchCommitMessages($tag, $repository);
        $diff = $repository->getDiff($tag->getName());

        $detectors = [
            new RangeDetection\CommitMessagesRangeDetection($commitMessages),
            new RangeDetection\DiffRangeDetection($diff),
        ];

        foreach ($indicators as $indicator) {
            $matchedRangeDetections = 0;
            $unmatchedRangeDetections = 0;

            // Evaluate all configured patterns
            foreach ($indicator->patterns() as $pattern) {
                foreach ($detectors as $detector) {
                    if (!$detector->supports($pattern)) {
                        continue;
                    }

                    if ($detector->matches($pattern)) {
                        ++$matchedRangeDetections;
                    } else {
                        ++$unmatchedRangeDetections;
                    }
                }
            }

            // Evaluate matched detections against configured strategy
            $indicatorMatches = match ($indicator->strategy()) {
                Enum\VersionRangeIndicatorStrategy::MatchAll => 0 === $unmatchedRangeDetections,
                Enum\VersionRangeIndicatorStrategy::MatchAny => $matchedRangeDetections > 0,
                Enum\VersionRangeIndicatorStrategy::MatchNone => 0 === $matchedRangeDetections,
            };

            // Add range if indicator matches
            if ($indicatorMatches) {
                $detectedRanges[] = $indicator->range();
            }
        }

        if ([] !== $detectedRanges) {
            return Enum\VersionRange::getHighest(...$detectedRanges);
        }

        return null;
    }

    /**
     * @return list<string>
     *
     * @throws Exception\CannotFetchGitCommits
     */
    private function fetchCommitMessages(Objects\Tag $tag, Repository $repository): array
    {
        $diff = sprintf('%s..HEAD', $tag->getName());

        try {
            $logRange = $repository->getLogRange($tag->getFullRef(), 'HEAD', null, -1, 0);
        } catch (\Exception) {
            throw new Exception\CannotFetchGitCommits($diff);
        }

        return array_values(
            array_filter(
                array_map(
                    static fn (?Objects\Commit $commit) => $commit?->getMessage()?->getShortMessage(),
                    iterator_to_array($logRange),
                ),
                static fn (?string $message) => null !== $message,
            ),
        );
    }
}
