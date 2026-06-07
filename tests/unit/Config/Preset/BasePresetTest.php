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

namespace EliasHaeussler\VersionBumper\Tests\Config\Preset;

use EliasHaeussler\VersionBumper as Src;
use EliasHaeussler\VersionBumper\Tests;
use PHPUnit\Framework;

/**
 * BasePresetTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Config\Preset\BasePreset::class)]
final class BasePresetTest extends Framework\TestCase
{
    private Tests\Fixtures\Classes\DummyPreset $subject;

    public function setUp(): void
    {
        $this->subject = new Tests\Fixtures\Classes\DummyPreset();
    }

    #[Framework\Attributes\Test]
    public function resolveOptionsThrowsExceptionIfRequiredOptionsAreMissing(): void
    {
        $this->expectException(Src\Exception\PresetOptionsAreInvalid::class);

        $this->subject->resolveOptions([]);
    }

    #[Framework\Attributes\Test]
    public function resolveOptionsResolvesGivenOptionsWithOptionsResolver(): void
    {
        $expected = [
            'baz' => 'baz',
            'foo' => 'foo',
        ];

        self::assertSame($expected, $this->subject->resolveOptions(['foo' => 'foo']));
    }
}
