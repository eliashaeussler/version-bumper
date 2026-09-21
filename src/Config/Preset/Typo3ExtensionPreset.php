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

use Composer\Composer;
use Composer\Factory;
use Composer\IO;
use Composer\Semver;
use EliasHaeussler\VersionBumper\Config;
use EliasHaeussler\VersionBumper\Version;
use Symfony\Component\Filesystem;
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
        $composer = $this->createComposerInstance($rootConfig?->rootPath());

        $extEmConf = new Config\FileToModify(
            'ext_emconf.php',
            [
                new Config\FilePattern("'version' => '{%version%}'"),
            ],
            true,
            $this->requiresExtEmConfFile($composer),
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
            releaseOptions: $this->buildReleaseOptions($composer),
        );
    }

    private function requiresExtEmConfFile(?Composer $composer): bool
    {
        // Safety net: If we cannot lookup dependencies due to missing Composer instance,
        // we better assume legacy TYPO3 versions are still supported instead of potentially breaking
        // things by assuming we have TYPO3 v14.3 only.
        if (null === $composer) {
            return true;
        }

        $requirements = $composer->getPackage()->getRequires();
        // @todo Replace with actual patch version, which depends on
        //       https://review.typo3.org/c/Packages/TYPO3.CMS/+/95815
        //       being merged and released (wait for v14 release!)
        $typo3Constraint = new Semver\Constraint\Constraint('<', '14.3.8');

        foreach (['typo3/cms-core', 'typo3/cms', 'typo3/minimal'] as $packageName) {
            if (array_key_exists($packageName, $requirements)) {
                return $requirements[$packageName]->getConstraint()->matches($typo3Constraint);
            }
        }

        // Safety net: If we cannot determine the installed TYPO3 version from declared dependencies,
        // we better assume legacy TYPO3 versions are still supported instead of potentially breaking
        // things by assuming we have TYPO3 v14.3 only.
        return true;
    }

    private function buildReleaseOptions(?Composer $composer): Config\ReleaseOptions
    {
        $extensionKey = $this->extractExtensionKeyFromComposerJson($composer);

        if (null === $extensionKey) {
            return new Config\ReleaseOptions();
        }

        return new Config\ReleaseOptions(
            sprintf('[RELEASE] Release of EXT:%s {%%version%%}', $extensionKey),
        );
    }

    private function extractExtensionKeyFromComposerJson(?Composer $composer): ?string
    {
        if (null === $composer) {
            return null;
        }

        // Parse extension key
        $extensionKey = $composer->getPackage()->getExtra()['typo3/cms']['extension-key'] ?? null;

        if (!is_string($extensionKey)) {
            return null;
        }

        return $extensionKey;
    }

    private function createComposerInstance(?string $rootPath): ?Composer
    {
        if (null === $rootPath) {
            return null;
        }

        $composerJson = Filesystem\Path::join($rootPath, 'composer.json');

        if (!is_file($composerJson)) {
            return null;
        }

        try {
            return Factory::create(new IO\NullIO(), $composerJson, true, true);
        } catch (Throwable) {
            return null;
        }
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
