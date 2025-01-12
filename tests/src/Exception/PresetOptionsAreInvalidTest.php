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

namespace EliasHaeussler\VersionBumper\Tests\Exception;

use EliasHaeussler\VersionBumper as Src;
use Exception;
use PHPUnit\Framework;

/**
 * PresetOptionsAreInvalidTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Exception\PresetOptionsAreInvalid::class)]
final class PresetOptionsAreInvalidTest extends Framework\TestCase
{
    #[Framework\Attributes\Test]
    public function constructorCreatesExceptionForGivenPresetAndException(): void
    {
        $previous = new Exception('foo baz is missing');
        $actual = new Src\Exception\PresetOptionsAreInvalid(new Src\Config\Preset\Typo3ExtensionPreset(), $previous);

        self::assertSame(
            'Options of preset "typo3-extension" are invalid: foo baz is missing',
            $actual->getMessage(),
        );
        self::assertSame(1736677999, $actual->getCode());
        self::assertSame($previous, $actual->getPrevious());
    }
}
