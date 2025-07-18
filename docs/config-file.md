# Config file

> [!TIP]
> Check out the [schema](schema.md) to learn about all available
> config options.

When using the console command, it is required to configure
the write operations which are to be performed by the version
bumper.

## Formats

The following file formats are supported currently:

* `json`
* `php`
* `yaml`, `yml`

### Configuration in PHP file

When using PHP files to provide configuration, make sure to:

1. either return an instance of [`VersionBumperConfig`](../src/Config/VersionBumperConfig.php)
2. or return a closure which returns an instance of
   [`VersionBumperConfig`](../src/Config/VersionBumperConfig.php).

Example:

```php
<?php

declare(strict_types=1);

use EliasHaeussler\VersionBumper;

return new VersionBumper\Config\VersionBumperConfig(
    [
        new VersionBumper\Config\Preset\ComposerPackagePreset(),
    ],
    [
        new VersionBumper\Config\FileToModify(
            'src/Version.php',
            [
                new VersionBumper\Config\FilePattern('const VERSION = \'{%version%}\';'),
            ],
        ),
    ],
```

## Configuration in `composer.json`

The config file path can be passed as a `-c`/`--config` command
option or, alternatively, as configuration in `composer.json`:

```json
{
    "extra": {
        "version-bumper": {
            "config-file": "path/to/version-bumper.json"
        }
    }
}
```

When configured as a relative path, the config file path is
calculated based on the location of the `composer.json` file.

## Auto-detection

If no config file is explicitly configured, the config reader
tries to auto-detect its location. The following order is taken
into account during auto-detection:

1. `version-bumper.php`
2. `version-bumper.json`
3. `version-bumper.yaml`
4. `version-bumper.yml`
