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

namespace EliasHaeussler\VersionBumper\Config;

use EliasHaeussler\VersionBumper\Version;
use ReflectionObject;
use Symfony\Component\Filesystem;

use function array_merge;
use function is_array;

/**
 * VersionBumperConfig.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class VersionBumperConfig
{
    /**
     * @param list<Preset\Preset>         $presets
     * @param list<FileToModify>          $filesToModify
     * @param list<VersionRangeIndicator> $versionRangeIndicators
     */
    public function __construct(
        private readonly array $presets = [],
        private array $filesToModify = [],
        private ?string $rootPath = null,
        private readonly ReleaseOptions $releaseOptions = new ReleaseOptions(),
        private readonly array $versionRangeIndicators = [],
    ) {
        $this->resolveWildcardsInFiles();
    }

    /**
     * @return list<Preset\Preset>
     */
    public function presets(): array
    {
        return $this->presets;
    }

    /**
     * @return list<FileToModify>
     */
    public function filesToModify(): array
    {
        return $this->filesToModify;
    }

    public function hasActions(Version\Action\ActionType $type): bool
    {
        foreach ($this->filesToModify as $fileToModify) {
            if ([] !== $fileToModify->getActionsByType($type)) {
                return true;
            }
        }

        return false;
    }

    public function rootPath(): ?string
    {
        return $this->rootPath;
    }

    public function setRootPath(string $rootPath): self
    {
        $previousRootPath = $this->rootPath;

        $this->rootPath = $rootPath;

        if ($previousRootPath !== $rootPath) {
            $this->resolveWildcardsInFiles();
        }

        return $this;
    }

    public function releaseOptions(): ReleaseOptions
    {
        return $this->releaseOptions;
    }

    /**
     * @return list<VersionRangeIndicator>
     */
    public function versionRangeIndicators(): array
    {
        return $this->versionRangeIndicators;
    }

    /**
     * @impure
     *
     * @internal
     */
    public function merge(self $other): self
    {
        $shell = new self();
        $reflection = new ReflectionObject($other);
        $parameters = $reflection->getConstructor()?->getParameters() ?? [];
        $properties = [];

        foreach ($parameters as $parameter) {
            $property = $reflection->getProperty($parameter->getName());
            $thisValue = $property->getValue($this);
            $otherValue = $property->getValue($other);

            /* @phpstan-ignore notEqual.notAllowed (Loose comparison is intended as we compare objects) */
            if ($property->getValue($shell) != $otherValue) {
                $thisValue = is_array($thisValue) && is_array($otherValue)
                    ? array_merge($thisValue, $otherValue)
                    : $otherValue
                ;
            }

            $properties[] = $thisValue;
        }

        /* @phpstan-ignore argument.type */
        return new self(...$properties);
    }

    private function resolveWildcardsInFiles(): void
    {
        $modified = false;
        $filesToModify = [];

        foreach ($this->filesToModify as $fileToModify) {
            $path = $fileToModify->path();

            // Skip files without wildcards
            if (!str_contains($path, '*')) {
                $filesToModify[] = $fileToModify;

                continue;
            }

            $isRelative = Filesystem\Path::isRelative($path);

            if ($isRelative) {
                // We cannot process relative paths if root path is not set
                if (null === $this->rootPath) {
                    $filesToModify[] = $fileToModify;

                    continue;
                }

                $fullPath = $fileToModify->fullPath($this->rootPath);
            } else {
                $fullPath = $path;
            }

            $files = glob($fullPath);

            // Skip wildcard resolution if glob fails
            if (false === $files) {
                $filesToModify[] = $fileToModify;

                continue;
            }

            foreach ($files as $file) {
                $filesToModify[] = new FileToModify(
                    $isRelative ? Filesystem\Path::makeRelative($file, $this->rootPath) : $file,
                    $fileToModify->patterns(),
                    $fileToModify->reportUnmatched(),
                    $fileToModify->reportMissing(),
                    $fileToModify->preActions(),
                    $fileToModify->postActions(),
                );
            }

            $modified = true;
        }

        if ($modified) {
            $this->filesToModify = $filesToModify;
        }
    }
}
