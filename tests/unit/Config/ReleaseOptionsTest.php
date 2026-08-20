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

namespace EliasHaeussler\VersionBumper\Tests\Config;

use EliasHaeussler\VersionBumper as Src;
use PHPUnit\Framework;

/**
 * ReleaseOptionsTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Config\ReleaseOptions::class)]
final class ReleaseOptionsTest extends Framework\TestCase
{
    #[Framework\Attributes\Test]
    public function constructorThrowsExceptionIfVersionPlaceholderIsMissingInTagName(): void
    {
        $this->expectExceptionObject(
            new Src\Exception\TagNameIsInvalid('foo'),
        );

        new Src\Config\ReleaseOptions(tagName: 'foo');
    }
}
