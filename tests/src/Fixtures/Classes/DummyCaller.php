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

namespace EliasHaeussler\VersionBumper\Tests\Fixtures\Classes;

use GitElephant\Command;
use PHPUnit\Framework\Assert;
use Throwable;

use function array_map;
use function array_shift;
use function explode;
use function sprintf;

/**
 * DummyCaller.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class DummyCaller extends Command\Caller\AbstractCaller
{
    /**
     * @var list<array{string|Throwable, string}>
     */
    public array $results = [];

    public function execute(string $cmd, bool $git = true, ?string $cwd = null): Command\Caller\CallerInterface
    {
        $result = array_shift($this->results);

        if (null === $result) {
            $this->resetOutput();

            return $this;
        }

        [$outputOrException, $expectedCommand] = $result;

        if ($expectedCommand !== $cmd) {
            Assert::fail(
                sprintf('Command "%s" does not match expected command "%s".', $cmd, $expectedCommand),
            );
        }

        if ($outputOrException instanceof Throwable) {
            throw $outputOrException;
        }

        $this->rawOutput = $outputOrException;
        $this->outputLines = array_map(rtrim(...), explode(PHP_EOL, $outputOrException));

        return $this;
    }

    public function resetOutput(): void
    {
        $this->rawOutput = '';
        $this->outputLines = [];
    }
}
