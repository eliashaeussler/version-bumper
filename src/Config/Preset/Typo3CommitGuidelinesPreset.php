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

namespace EliasHaeussler\VersionBumper\Config\Preset;

use EliasHaeussler\VersionBumper\Config;
use EliasHaeussler\VersionBumper\Enum;

/**
 * Typo3CommitGuidelinesPreset.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://docs.typo3.org/m/typo3/guide-contributionworkflow/main/en-us/Appendix/CommitMessage.html
 */
final class Typo3CommitGuidelinesPreset implements Preset
{
    public function getConfig(?Config\VersionBumperConfig $rootConfig = null): Config\VersionBumperConfig
    {
        $versionRangeIndicators = [
            // Major
            new Config\VersionRangeIndicator(
                Enum\VersionRange::Major,
                [
                    new Config\VersionRangePattern(
                        Enum\VersionRangeIndicatorType::CommitMessage,
                        '/^\[!!!]/',
                    ),
                ],
            ),

            // Minor
            new Config\VersionRangeIndicator(
                Enum\VersionRange::Minor,
                [
                    new Config\VersionRangePattern(
                        Enum\VersionRangeIndicatorType::CommitMessage,
                        '/^\[FEATURE]/',
                    ),
                ],
            ),

            // Patch
            new Config\VersionRangeIndicator(
                Enum\VersionRange::Patch,
                [
                    new Config\VersionRangePattern(
                        Enum\VersionRangeIndicatorType::CommitMessage,
                        '/^\[(BUGFIX|DOCS|TASK)]/',
                    ),
                ],
            ),
        ];

        return new Config\VersionBumperConfig(versionRangeIndicators: $versionRangeIndicators);
    }

    public static function getIdentifier(): string
    {
        return 'typo3-commit-guidelines';
    }

    public static function getDescription(): string
    {
        return 'TYPO3 guidelines for commit messages';
    }
}
