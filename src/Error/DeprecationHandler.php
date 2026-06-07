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

namespace EliasHaeussler\VersionBumper\Error;

use EliasHaeussler\VersionBumper\Config;
use Symfony\Component\Console;

use function restore_error_handler;
use function set_error_handler;

/**
 * DeprecationHandler.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class DeprecationHandler
{
    private static ?self $instance = null;

    /**
     * @var list<DeprecationMessage>
     */
    private array $deprecations = [];
    private bool $active = false;

    private function __construct() {}

    public static function new(): self
    {
        return self::$instance ??= new self();
    }

    public function enable(): void
    {
        if (!$this->active) {
            set_error_handler($this->registerDeprecation(...), E_USER_DEPRECATED);
        }

        $this->active = true;
    }

    public function disable(): void
    {
        if ($this->active) {
            restore_error_handler();
        }

        $this->active = false;
        $this->deprecations = [];
    }

    public function decorate(Console\Style\StyleInterface $io): void
    {
        if ([] === $this->deprecations) {
            return;
        }

        $io->warning('Your config file contains deprecated options.');

        $io->listing(
            array_map(
                static fn (DeprecationMessage $deprecation) => implode('', [
                    $deprecation->origin() instanceof Config\Preset\Preset
                        ? sprintf('<fg=yellow>%s</>: ', $deprecation->origin()::getIdentifier())
                        : '',
                    $deprecation->message(),
                    null !== $deprecation->since()
                        ? sprintf(' <fg=yellow>(deprecated since v%s)</>', $deprecation->since())
                        : '',
                ]),
                $this->deprecations,
            ),
        );
    }

    /**
     * @return list<DeprecationMessage>
     */
    public function deprecations(): array
    {
        return $this->deprecations;
    }

    private function registerDeprecation(int $errno, string $errstr): bool
    {
        $deprecationMessage = DeprecationMessage::fromTraceOrMessage($errstr);
        $collected = null !== $deprecationMessage;

        if ($collected) {
            $this->deprecations[] = $deprecationMessage;
        }

        return $collected;
    }
}
