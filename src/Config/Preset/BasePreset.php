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

namespace EliasHaeussler\VersionBumper\Config\Preset;

use EliasHaeussler\VersionBumper\Exception;
use Symfony\Component\OptionsResolver;

/**
 * BasePreset.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 *
 * @template TOptions of array<string, mixed>
 */
abstract class BasePreset implements ConfigurablePreset
{
    /**
     * @var TOptions
     */
    protected array $options;

    /**
     * @param array<string, mixed> $options
     *
     * @return TOptions
     *
     * @throws Exception\PresetOptionsAreInvalid
     */
    protected function resolveOptions(array $options): array
    {
        $optionsResolver = $this->createOptionsResolver();

        try {
            /* @phpstan-ignore return.type */
            return $optionsResolver->resolve($options);
        } catch (OptionsResolver\Exception\MissingOptionsException $exception) {
            throw new Exception\PresetOptionsAreInvalid($this, $exception);
        }
    }

    abstract protected function createOptionsResolver(): OptionsResolver\OptionsResolver;
}
