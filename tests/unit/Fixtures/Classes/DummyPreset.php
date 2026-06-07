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

use EliasHaeussler\VersionBumper\Config;
use Symfony\Component\OptionsResolver;

/**
 * DummyPreset.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 *
 * @internal
 *
 * @extends Config\Preset\BasePreset<array<string, mixed>>
 */
final class DummyPreset extends Config\Preset\BasePreset
{
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function getConfig(?Config\VersionBumperConfig $rootConfig = null): Config\VersionBumperConfig
    {
        return new Config\VersionBumperConfig();
    }

    public static function getIdentifier(): string
    {
        return 'dummy';
    }

    public static function getDescription(): string
    {
        return 'Dummy preset';
    }

    public function resolveOptions(array $options): array
    {
        return parent::resolveOptions($options);
    }

    protected function createOptionsResolver(): OptionsResolver\OptionsResolver
    {
        $optionsResolver = new OptionsResolver\OptionsResolver();
        $optionsResolver->define('foo')
            ->allowedTypes('string')
            ->required();
        $optionsResolver->define('baz')
            ->allowedTypes('string')
            ->default('baz');

        return $optionsResolver;
    }
}
