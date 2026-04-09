# PHP API

> [!TIP]
> You can use the method argument `$dryRun` in both
> `VersionBumper` and `VersionReleaser` classes to skip any
> write operations (dry-run mode).

## Bump versions

The main entrypoint of the plugin is the
[`Version\VersionBumper`](../src/Version/VersionBumper.php) class:

```php
use EliasHaeussler\VersionBumper;

// Define files and patterns in which to bump new versions
$packageJsonFile = new VersionBumper\Config\FileToModify(
    path: 'package.json',
    patterns: [
        '"version": "{%version%}"',
    ],
    postActions: [
        new VersionBumper\Version\Action\PackageLockAction(),
    ],
);
$filesToModify = [
    $packageJsonFile,
    new VersionBumper\Config\FileToModify(
        'src/Version.php',
        [
            'public const VERSION = \'{%version%}\';',
        ],
    ),
];

// Define package root path and version range
$rootPath = dirname(__DIR__);
$versionRange = VersionBumper\Enum\VersionRange::Minor;

// Bump versions within configured files
$versionBumper = new VersionBumper\Version\VersionBumper();
$results = $versionBumper->bump(
    $filesToModify,
    $rootPath,
    $versionRange,
);

// Display results
foreach ($results as $result) {
    // File: package.json
    echo sprintf('File: %s', $result->file()->path());
    echo PHP_EOL;

    foreach ($result->groupedOperations() as $operations) {
        foreach ($operations as $operation) {
            // Modified: 1.2.3 => 1.3.0
            echo sprintf(
                '%s: %s => %s',
                $operation->state()->name,
                $operation->source(),
                $operation->target(),
            );
            echo PHP_EOL;
        }
    }
}
```

## Execute actions

Actions can be executed using the
[`Version\ActionDispatcher`](../src/Version/ActionDispatcher.php) class:

```php
use EliasHaeussler\VersionBumper;

$output = new \Symfony\Component\Console\Output\BufferedOutput();
$actionDispatcher = new VersionBumper\Version\ActionDispatcher($rootPath, $output);
$actionExecutionResult = $actionDispatcher->dispatchAll(
    $packageJsonFile->preActions(), // or ->postActions()
    $packageJsonFile,
);

if ($actionExecutionResult->failed()) {
    echo 'Something went wrong while executing pre-actions. Details:';
    echo PHP_EOL;
    echo $output->fetch();

    exit(1);
}

foreach ($actionExecutionResult->results() as $result) {
    // File: package.json
    echo sprintf('File: %s', $result->file()->path());
    echo PHP_EOL;

    foreach ($result->groupedOperations() as $operations) {
        foreach ($operations as $operation) {
            // Regenerated
            echo sprintf('Operation: %s', $operation->state()->name);
            echo PHP_EOL;
        }
    }
}
```

## Create release

A release can be created by the
[`Version\VersionReleaser`](../src/Version/VersionReleaser.php) class:

```php
use EliasHaeussler\VersionBumper;

$options = new VersionBumper\Config\ReleaseOptions(
    tagName: 'v{%version%}', // Create tag with "v" prefix
    signTag: true, // Sign new tags
);

$versionReleaser = new VersionBumper\Version\VersionReleaser();
$result = $versionReleaser->release($results, $rootPath, $options);

echo sprintf(
    'Committed "%s" and tagged "%s" with %d file(s).',
    $result->commitMessage(),
    $result->tagName(),
    count($result->committedFiles()),
);
echo PHP_EOL;
```

## Auto-detect version range

When bumping files, a respective version range or explicit version
must be provided (see above). The library provides a
[`Version\VersionRangeDetector`](../src/Version/VersionRangeDetector.php)
class to automate this step and auto-detect a version range, based
on a set of [`Config\VersionRangeIndicator`](../src/Config/VersionRangeIndicator.php)
objects:

```php
use EliasHaeussler\VersionBumper;

$indicators = [
    new VersionBumper\Config\VersionRangeIndicator(
        // Bump major version if any commit contains breaking changes
        // (commit message starts with "[!!!]")
        VersionBumper\Enum\VersionRange::Major,
        [
            new VersionBumper\Config\VersionRangePattern(
                VersionBumper\Enum\VersionRangeIndicatorType::CommitMessage,
                '/^\[!!!]/',
            ),
        ],
    ),
];

$versionRangeDetector = new VersionBumper\Version\VersionRangeDetector();
$versionRange = $versionRangeDetector->detect($rootPath, $indicators);

echo sprintf('Auto-detected version range is "%s".', $versionRange->value);
echo PHP_EOL;
```
