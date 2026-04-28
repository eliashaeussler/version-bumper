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

namespace EliasHaeussler\VersionBumper\Event;

use Composer\EventDispatcher;
use EliasHaeussler\VersionBumper\Config;
use EliasHaeussler\VersionBumper\Version;

use function array_filter;
use function array_values;
use function is_a;
use function is_string;

/**
 * VersionRangeDetectionPrepared.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class VersionRangeDetectionPrepared extends EventDispatcher\Event
{
    /**
     * @param list<Config\VersionRangeIndicator>          $indicators
     * @param list<Version\RangeDetection\RangeDetection> $detectors
     */
    public function __construct(
        private readonly string $rootPath,
        private readonly array $indicators,
        private readonly ?string $since,
        private array $detectors,
    ) {
        parent::__construct('version-range-detection-prepared');
    }

    public function rootPath(): string
    {
        return $this->rootPath;
    }

    /**
     * @return list<Config\VersionRangeIndicator>
     */
    public function indicators(): array
    {
        return $this->indicators;
    }

    public function since(): ?string
    {
        return $this->since;
    }

    /**
     * @return list<Version\RangeDetection\RangeDetection>
     */
    public function detectors(): array
    {
        return $this->detectors;
    }

    public function addDetector(Version\RangeDetection\RangeDetection $detector): self
    {
        $this->detectors[] = $detector;

        return $this;
    }

    /**
     * @param Version\RangeDetection\RangeDetection|class-string<Version\RangeDetection\RangeDetection> $detector
     */
    public function removeDetector(Version\RangeDetection\RangeDetection|string $detector): self
    {
        $this->detectors = array_values(
            array_filter(
                $this->detectors,
                static fn (
                    Version\RangeDetection\RangeDetection $existing,
                ) => $detector !== $existing && !(is_string($detector) && is_a($existing, $detector, true)),
            ),
        );

        return $this;
    }
}
