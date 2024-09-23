<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/version-bumper".
 *
 * Copyright (C) 2024 Elias Häußler <elias@haeussler.dev>
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
use PHPUnit\Framework;

/**
 * ConfigFileIsNotSupportedTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Exception\ConfigFileIsNotSupported::class)]
final class ConfigFileIsNotSupportedTest extends Framework\TestCase
{
    #[Framework\Attributes\Test]
    public function constructorCreatesExceptionForUnsupportedConfigFile(): void
    {
        $actual = new Src\Exception\ConfigFileIsNotSupported('foo');

        self::assertSame('File "foo" is not a supported config file.', $actual->getMessage());
        self::assertSame(1727022709, $actual->getCode());
    }
}
