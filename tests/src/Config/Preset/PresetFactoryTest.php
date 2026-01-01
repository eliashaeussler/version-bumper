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
use PHPUnit\Framework;

/**
 * PresetFactoryTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Config\Preset\PresetFactory::class)]
final class PresetFactoryTest extends Framework\TestCase
{
    private Src\Config\Preset\PresetFactory $subject;

    public function setUp(): void
    {
        $this->subject = new Src\Config\Preset\PresetFactory();
    }

    #[Framework\Attributes\Test]
    public function getThrowsExceptionIfGivenPresetDoesNotExist(): void
    {
        $this->expectExceptionObject(
            new Src\Exception\PresetDoesNotExist('foo'),
        );

        $this->subject->get('foo');
    }

    #[Framework\Attributes\Test]
    public function getReturnsGivenPreset(): void
    {
        $expected = new Src\Config\Preset\Typo3CommitGuidelinesPreset();

        self::assertEquals($expected, $this->subject->get('typo3-commit-guidelines'));
    }

    #[Framework\Attributes\Test]
    public function getReturnsGivenConfigurablePreset(): void
    {
        $expected = new Src\Config\Preset\Typo3ExtensionPreset();

        self::assertEquals($expected, $this->subject->get('typo3-extension'));
    }

    #[Framework\Attributes\Test]
    public function getAppliesGivenConfigToConfigurablePreset(): void
    {
        $expected = new Src\Config\Preset\NpmPackagePreset(['packageName' => '@foo/baz']);

        self::assertEquals($expected, $this->subject->get('npm-package', ['packageName' => '@foo/baz']));
    }
}
