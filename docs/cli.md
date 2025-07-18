# Console command `bump-version`

> [!TIP]
> The `<range>` command option can be omitted if
> [version range auto-detection](version-range.md#auto-detection)
> is properly configured.

```bash
$ composer bump-version [<range>] [-c|--config CONFIG] [-r|--release] [--dry-run] [--strict]
```

Pass the following options to the console command:

## `<range>`

Version range to be bumped, can be an explicit version (e.g. `1.3.0`)
or one of:

| Keyword       | Description                        | Stable example    | Unstable example  |
|---------------|------------------------------------|-------------------|-------------------|
| `major`/`maj` | Bump version to next major version | `1.2.3` → `2.0.0` | `0.1.2` → `0.2.0` |
| `minor`/`min` | Bump version to next minor version | `1.2.3` → `1.3.0` | `0.1.2` → `0.1.3` |
| `patch`/`p`   | Bump version to next patch version | `1.2.3` → `1.2.4` | `0.1.2` → `0.1.3` |
| `next`/`n`    | Bump version to next patch version | `1.2.3` → `1.2.4` | `0.1.2` → `0.1.3` |

## `-c|--config`

Path to [config file](config-file), defaults to auto-detection in the
current working directory, can be
[configured in `composer.json`](config-file#configuration-in-composerjson)
as well.

## `-r|--release`

Create a new Git tag after versions are bumped.

## `--dry-run`

Do not perform any write operations, just calculate and display
version bumps.

## `--strict`

Fail if any unmatched file pattern is reported.
