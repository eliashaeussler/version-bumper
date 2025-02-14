<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/version-bumper".
 *
 * Copyright (C) 2024-2025 Elias Häußler <elias@haeussler.dev>
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
use PHPUnit\Framework;

/**
 * NpmPackagePresetTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Config\Preset\NpmPackagePreset::class)]
final class NpmPackagePresetTest extends Framework\TestCase
{
    private Src\Config\Preset\NpmPackagePreset $subject;

    public function setUp(): void
    {
        $this->subject = new Src\Config\Preset\NpmPackagePreset([
            'packageName' => '@foo/baz',
            'path' => 'foo/baz',
        ]);
    }

    #[Framework\Attributes\Test]
    public function constructorThrowsExceptionIfRequiredOptionIsMissing(): void
    {
        $this->expectException(Src\Exception\PresetOptionsAreInvalid::class);

        new Src\Config\Preset\NpmPackagePreset([]);
    }

    #[Framework\Attributes\Test]
    public function getConfigReturnsResolvedConfig(): void
    {
        $expected = new Src\Config\VersionBumperConfig(
            filesToModify: [
                new Src\Config\FileToModify(
                    'foo/baz/package.json',
                    [
                        new Src\Config\FilePattern('"version": "{%version%}"'),
                    ],
                    true,
                ),
                new Src\Config\FileToModify(
                    'foo/baz/package-lock.json',
                    [
                        new Src\Config\FilePattern(
                            '"name": "@foo/baz",\s+"version": "{%version%}"',
                        ),
                    ],
                    true,
                ),
            ],
        );

        self::assertEquals($expected, $this->subject->getConfig());
    }
}
