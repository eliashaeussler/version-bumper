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

use function set_error_handler;

/**
 * NpmPackagePresetTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Config\Preset\NpmPackagePreset::class)]
final class NpmPackagePresetTest extends Framework\TestCase
{
    #[Framework\Attributes\Test]
    public function constructorEmitsDeprecationNoticeIfPackageNameOptionIsConfigured(): void
    {
        $deprecations = [];

        // Due to Symfony's deprecation handling, we cannot use PHPUnit's default
        // expectUserDeprecationMessage() method and thus use a custom error handler.
        set_error_handler(
            static function (int $errno, string $errstr) use (&$deprecations): bool {
                $deprecations[] = $errstr;

                return true;
            },
        );

        $expected = [
            'Since eliashaeussler/version-bumper 3.2.0: The option "packageName" is no longer needed and should be omitted.',
        ];

        try {
            new Src\Config\Preset\NpmPackagePreset([
                'packageName' => 'foo',
            ]);
        } finally {
            restore_error_handler();
        }

        self::assertSame($expected, $deprecations);
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
                    true,
                    [],
                    [
                        new Src\Version\Action\PackageLockAction(),
                    ],
                ),
            ],
        );

        $subject = new Src\Config\Preset\NpmPackagePreset([
            'path' => 'foo/baz',
        ]);

        self::assertEquals($expected, $subject->getConfig());
    }
}
