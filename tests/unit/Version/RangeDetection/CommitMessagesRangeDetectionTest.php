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

namespace EliasHaeussler\VersionBumper\Tests\Version\RangeDetection;

use EliasHaeussler\VersionBumper as Src;
use PHPUnit\Framework;

/**
 * CommitMessagesRangeDetectionTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Version\RangeDetection\CommitMessagesRangeDetection::class)]
final class CommitMessagesRangeDetectionTest extends Framework\TestCase
{
    private Src\Version\RangeDetection\CommitMessagesRangeDetection $subject;

    public function setUp(): void
    {
        $this->subject = new Src\Version\RangeDetection\CommitMessagesRangeDetection([
            '[!!!][TASK] Remove dashboard controller',
            '[FEATURE] Add login controller',
            '[DOCS] Mention login controller in developer docs',
        ]);
    }

    #[Framework\Attributes\Test]
    public function matchesReturnsFalseIfNoCommitMessagesAreAvailable(): void
    {
        $pattern = new Src\Config\VersionRangePattern(
            Src\Enum\VersionRangeIndicatorType::CommitMessage,
            'foo',
        );

        $subject = new Src\Version\RangeDetection\CommitMessagesRangeDetection([]);

        self::assertFalse($subject->matches($pattern));
    }

    #[Framework\Attributes\Test]
    public function matchesReturnsTrueIfAnyCommitMessageMatchesGivenPattern(): void
    {
        $pattern = new Src\Config\VersionRangePattern(
            Src\Enum\VersionRangeIndicatorType::CommitMessage,
            '/^\[FEATURE]/',
        );

        self::assertTrue($this->subject->matches($pattern));
    }

    #[Framework\Attributes\Test]
    public function matchesReturnsFalseIfNoCommitMessageMatchesGivenPattern(): void
    {
        $pattern = new Src\Config\VersionRangePattern(
            Src\Enum\VersionRangeIndicatorType::CommitMessage,
            '/^\[TASK]/',
        );

        self::assertFalse($this->subject->matches($pattern));
    }

    #[Framework\Attributes\Test]
    public function supportsReturnsTrueIfGivenPatternHasCommitMessageTypeAssociated(): void
    {
        self::assertTrue(
            $this->subject->supports(
                new Src\Config\VersionRangePattern(
                    Src\Enum\VersionRangeIndicatorType::CommitMessage,
                    'foo',
                ),
            ),
        );
    }

    #[Framework\Attributes\Test]
    public function supportsReturnsFalseIfGivenPatternHasDifferentTypeThanCommitMessageAssociated(): void
    {
        self::assertFalse(
            $this->subject->supports(
                new Src\Config\VersionRangePattern(
                    Src\Enum\VersionRangeIndicatorType::FileAdded,
                    'foo',
                ),
            ),
        );
    }
}
