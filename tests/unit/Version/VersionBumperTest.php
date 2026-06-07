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

namespace EliasHaeussler\VersionBumper\Tests\Version;

use EliasHaeussler\VersionBumper as Src;
use PHPUnit\Framework;

use function dirname;
use function file_get_contents;
use function file_put_contents;

/**
 * VersionBumperTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Version\VersionBumper::class)]
final class VersionBumperTest extends Framework\TestCase
{
    private Src\Version\VersionBumper $subject;

    /**
     * @var list<Src\Config\FileToModify>
     */
    private array $filesToModify;

    public function setUp(): void
    {
        $this->subject = new Src\Version\VersionBumper();
        $this->filesToModify = [
            new Src\Config\FileToModify(
                'foo',
                [
                    'foo: {%version%}',
                    'baz: {%version%}',
                ],
            ),
            new Src\Config\FileToModify(
                'baz',
                [
                    'foo: {%version%}',
                    'baz: {%version%}',
                ],
            ),
        ];
    }

    #[Framework\Attributes\Test]
    public function bumpThrowsExceptionIfFileToModifyDoesNotExist(): void
    {
        $this->expectExceptionObject(
            new Src\Exception\FileDoesNotExist('/baz/foo'),
        );

        $this->subject->bump($this->filesToModify, '/baz', Src\Enum\VersionRange::Next);
    }

    #[Framework\Attributes\Test]
    public function bumpReturnsEmptyResultIfFileToModifyDoesNotExistAndReportMissingIsDisabled(): void
    {
        $fileToModify = new Src\Config\FileToModify(
            'foo',
            [
                'foo: {%version%}',
                'baz: {%version%}',
            ],
            reportMissing: false,
        );

        self::assertEquals(
            [
                new Src\Result\VersionBumpResult($fileToModify, []),
            ],
            $this->subject->bump([$fileToModify], '/baz', Src\Enum\VersionRange::Next),
        );
    }

    #[Framework\Attributes\Test]
    public function bumpDoesNothingIfFileContentsWereNotModified(): void
    {
        $fooFile = $this->filesToModify[0];
        $files = [$fooFile];
        $rootPath = dirname(__DIR__).'/Fixtures/RootPath';

        $expected = [
            new Src\Result\WriteOperation(
                new Src\Version\Version(1, 0, 0),
                new Src\Version\Version(1, 0, 0),
                <<<FOO
baz: 1.0.0
baz: 1.0.0

FOO,
                new Src\Config\FilePattern('baz: {%version%}'),
                Src\Enum\OperationState::Skipped,
            ),
            new Src\Result\WriteOperation(
                new Src\Version\Version(1, 0, 0),
                new Src\Version\Version(1, 0, 0),
                <<<FOO
baz: 1.0.0
baz: 1.0.0

FOO,
                new Src\Config\FilePattern('baz: {%version%}'),
                Src\Enum\OperationState::Skipped,
            ),
        ];

        $actual = $this->subject->bump($files, $rootPath, '1.0.0');

        self::assertCount(1, $actual);
        self::assertSame($fooFile, $actual[0]->file());
        self::assertEquals($expected, $actual[0]->operations());
    }

    #[Framework\Attributes\Test]
    public function bumpReportsUnmatchedPattern(): void
    {
        $fooFile = new Src\Config\FileToModify(
            'foo',
            [
                'foo: {%version%}',
                'baz: {%version%}',
            ],
            true,
        );
        $files = [$fooFile];
        $rootPath = dirname(__DIR__).'/Fixtures/RootPath';

        $expected = [
            Src\Result\WriteOperation::unmatched(
                new Src\Config\FilePattern('foo: {%version%}'),
            ),
            new Src\Result\WriteOperation(
                new Src\Version\Version(1, 0, 0),
                new Src\Version\Version(1, 0, 0),
                <<<FOO
baz: 1.0.0
baz: 1.0.0

FOO,
                new Src\Config\FilePattern('baz: {%version%}'),
                Src\Enum\OperationState::Skipped,
            ),
            new Src\Result\WriteOperation(
                new Src\Version\Version(1, 0, 0),
                new Src\Version\Version(1, 0, 0),
                <<<FOO
baz: 1.0.0
baz: 1.0.0

FOO,
                new Src\Config\FilePattern('baz: {%version%}'),
                Src\Enum\OperationState::Skipped,
            ),
        ];

        $actual = $this->subject->bump($files, $rootPath, '1.0.0');

        self::assertCount(1, $actual);
        self::assertSame($fooFile, $actual[0]->file());
        self::assertEquals($expected, $actual[0]->operations());
    }

    #[Framework\Attributes\Test]
    public function bumpIncreasesVersionRange(): void
    {
        $fooFile = $this->filesToModify[0];
        $files = [$fooFile];
        $rootPath = dirname(__DIR__).'/Fixtures/RootPath';

        $expected = [
            new Src\Result\WriteOperation(
                new Src\Version\Version(1, 0, 0),
                new Src\Version\Version(1, 1, 0),
                <<<FOO
baz: 1.1.0
baz: 1.0.0

FOO,
                new Src\Config\FilePattern('baz: {%version%}'),
                Src\Enum\OperationState::Modified,
            ),
            new Src\Result\WriteOperation(
                new Src\Version\Version(1, 0, 0),
                new Src\Version\Version(1, 1, 0),
                <<<FOO
baz: 1.1.0
baz: 1.1.0

FOO,
                new Src\Config\FilePattern('baz: {%version%}'),
                Src\Enum\OperationState::Modified,
            ),
        ];

        $actual = $this->subject->bump($files, $rootPath, Src\Enum\VersionRange::Minor, true);

        self::assertCount(1, $actual);
        self::assertSame($fooFile, $actual[0]->file());
        self::assertEquals($expected, $actual[0]->operations());
    }

    #[Framework\Attributes\Test]
    public function bumpIncreasesVersionRangeAndWritesModifiedFiles(): void
    {
        $bazFile = $this->filesToModify[1];
        $files = [$bazFile];
        $rootPath = dirname(__DIR__).'/Fixtures/RootPath';
        $contentBackup = file_get_contents($bazFile->fullPath($rootPath));

        self::assertIsString($contentBackup);

        $expected = [
            new Src\Result\WriteOperation(
                new Src\Version\Version(2, 0, 0),
                new Src\Version\Version(2, 1, 0),
                <<<BAZ
foo: 2.1.0
baz: 3.0.0

BAZ,
                new Src\Config\FilePattern('foo: {%version%}'),
                Src\Enum\OperationState::Modified,
            ),
            new Src\Result\WriteOperation(
                new Src\Version\Version(3, 0, 0),
                new Src\Version\Version(3, 1, 0),
                <<<BAZ
foo: 2.1.0
baz: 3.1.0

BAZ,
                new Src\Config\FilePattern('baz: {%version%}'),
                Src\Enum\OperationState::Modified,
            ),
        ];

        try {
            $actual = $this->subject->bump($files, $rootPath, Src\Enum\VersionRange::Minor);

            self::assertCount(1, $actual);
            self::assertSame($bazFile, $actual[0]->file());
            self::assertEquals($expected, $actual[0]->operations());
            self::assertStringNotEqualsFile($bazFile->fullPath($rootPath), $contentBackup);
        } finally {
            file_put_contents($bazFile->fullPath($rootPath), $contentBackup);
        }
    }
}
