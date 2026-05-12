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

use Composer\Factory;
use Composer\IO;
use EliasHaeussler\VersionBumper\Config;
use EliasHaeussler\VersionBumper\Version;
use Symfony\Component\OptionsResolver;
use Throwable;

use function in_array;
use function is_file;
use function is_string;
use function sprintf;

/**
 * Typo3ExtensionPreset.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 *
 * @extends BasePreset<array{documentation: self::*_KEYWORD|bool}>
 */
final class Typo3ExtensionPreset extends BasePreset
{
    private const AUTO_KEYWORD = 'auto';
    private const LEGACY_KEYWORD = 'legacy';

    public function __construct(array $options = [])
    {
        $this->options = $this->resolveOptions($options);
    }

    public function getConfig(?Config\VersionBumperConfig $rootConfig = null): Config\VersionBumperConfig
    {
        $reportMissingDocsFile = self::AUTO_KEYWORD !== $this->options['documentation'];
        $reportUnmatchedComposerVersion = true;
        $extEmConf = new Config\FileToModify(
            'ext_emconf.php',
            [
                new Config\FilePattern("'version' => '{%version%}'"),
            ],
            true,
        );

        // Don't report missing version pattern in composer.json file if an ext_emconf.php file
        // still exists (this reflects a compatibility behavior of extensions which support
        // multiple TYPO3 LTS versions, e.g. v13 and v14)
        if (null !== $rootConfig?->rootPath() && is_file($extEmConf->fullPath($rootConfig->rootPath()))) {
            $reportUnmatchedComposerVersion = false;
        }

        // https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/14.2/Feature-108345-No-ext-em-conf-in-classic-mode.html#extension-version
        $composerJson = new Config\FileToModify(
            'composer.json',
            [
                // Missing trailing quote is intended to allow and retain version suffixes
                // (e.g. "1.0.0-dev" or "1.0.0+obsolete")
                new Config\FilePattern('"version": "{%version%}'),
            ],
            $reportUnmatchedComposerVersion,
            true,
            [],
            [
                new Version\Action\ComposerLockAction(),
            ],
        );

        $filesToModify = [$extEmConf, $composerJson];

        // New PHP-based documentation rendering
        if (in_array($this->options['documentation'], [self::AUTO_KEYWORD, true], true)) {
            $filesToModify[] = new Config\FileToModify(
                'Documentation/guides.xml',
                [
                    new Config\FilePattern('release="{%version%}"'),
                ],
                true,
                $reportMissingDocsFile,
            );
        }

        // Legacy Sphinx-based documentation rendering
        if (in_array($this->options['documentation'], [self::AUTO_KEYWORD, self::LEGACY_KEYWORD], true)) {
            $filesToModify[] = new Config\FileToModify(
                'Documentation/Settings.cfg',
                [
                    new Config\FilePattern('release = {%version%}'),
                ],
                true,
                $reportMissingDocsFile,
            );
        }

        return new Config\VersionBumperConfig(
            filesToModify: $filesToModify,
            releaseOptions: $this->buildReleaseOptions($composerJson, $rootConfig) ?? new Config\ReleaseOptions(),
        );
    }

    private function buildReleaseOptions(
        Config\FileToModify $composerJson,
        ?Config\VersionBumperConfig $rootConfig,
    ): ?Config\ReleaseOptions {
        if (null === $rootConfig || null === $rootConfig->rootPath()) {
            return null;
        }

        $extensionKey = $this->extractExtensionKeyFromComposerJson($composerJson->fullPath($rootConfig->rootPath()));

        if (null === $extensionKey) {
            return null;
        }

        return new Config\ReleaseOptions(
            sprintf('[RELEASE] Release of EXT:%s {%%version%%}', $extensionKey),
        );
    }

    private function extractExtensionKeyFromComposerJson(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        // Build Composer instance
        try {
            $composer = Factory::create(new IO\NullIO(), $path, true, true);
        } catch (Throwable) {
            return null;
        }

        // Parse extension key
        $extensionKey = $composer->getPackage()->getExtra()['typo3/cms']['extension-key'] ?? null;

        if (!is_string($extensionKey)) {
            return null;
        }

        return $extensionKey;
    }

    public static function getIdentifier(): string
    {
        return 'typo3-extension';
    }

    public static function getDescription(): string
    {
        return 'TYPO3 extension, managed by composer.json and ext_emconf.php';
    }

    protected function createOptionsResolver(): OptionsResolver\OptionsResolver
    {
        $optionsResolver = new OptionsResolver\OptionsResolver();
        $optionsResolver->define('documentation')
            ->allowedValues(self::AUTO_KEYWORD, self::LEGACY_KEYWORD, true, false)
            ->default(self::AUTO_KEYWORD)
        ;

        return $optionsResolver;
    }
}
