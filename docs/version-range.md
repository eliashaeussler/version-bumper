# Version range

> [!IMPORTANT]
> Unstable versions (< `1.0.0`) are handled differently.
> Bumping major version increases the second version
> number (`0.1.2` → `0.2.0`) and bumping minor version
> increases the third version number (`0.1.2` → `0.1.3`).

## Bumping to the first stable release

The `major` range cannot produce `1.0.0` from an unstable version, since it
increases the second digit (`0.5.0` → `0.6.0`). Pass the target version
explicitly instead:

```bash
composer bump-version 1.0.0 --release
```

Version range auto-detection cannot produce `1.0.0` either, as it always
resolves to a version range.

## Auto-detection

Normally, an explicit version range or version is passed to
the `bump-version` command. However, it may become handy if
a version range is auto-detected, based on the Git history.
This sort of auto-detection is automatically triggered if the
`<range>` command option is omitted.

> [!IMPORTANT]
> Auto-detection is only possible if
> [`versionRangeIndicators`](schema.md#version-range-indicators)
> are configured in the config file.

To use the auto-detection feature, make sure to add version
range indicators to your config file:

```yaml
versionRangeIndicators:
  # 1️⃣ Bump major version on breaking changes, determined by commit message
  - range: major
    patterns:
      - type: commitMessage
        pattern: '/^\[!!!]/'

  # 2️⃣ Bump major version if controllers are deleted and API schema changes
  - range: major
    # All configured patterns must match to use this indicator
    strategy: matchAll
    patterns:
      - type: fileDeleted
        pattern: '/^src\/Controller\/.+Controller\.php$/'
      - type: fileModified
        pattern: '/^res\/api\.schema\.json$/'

  # 3️⃣ Bump minor version when new features are added
  - range: minor
    patterns:
      - type: commitMessage
        pattern: '/^\[FEATURE]/'

  # 4️⃣ Bump patch version if maintenance or documentation tasks were performed
  - range: patch
    patterns:
      - type: commitMessage
        pattern: '/^\[TASK]/'
      - type: commitMessage
        pattern: '/^\[BUGFIX]/'
      - type: commitMessage
        pattern: '/^\[DOCS]/'

  # 5️⃣ Bump patch version if no sources have changed
  - range: patch
    # No configured patterns must match to use this indicator
    strategy: matchNone
    patterns:
      - type: fileAdded
        pattern: '/^src\//'
      - type: fileDeleted
        pattern: '/^src\//'
      - type: fileModified
        pattern: '/^src\//'
```

> [!NOTE]
> The matching version range with the highest priority will be
> used as final version range (`major` receives the highest priority).

If no version range indicator matches, the `bump-version`
command will fail.

### Strategies

The `strategy` config option (see second indicator in the above example)
defines how matching (or non-matching) patterns are treated to
mark the whole indicator as "matching".

By default, an indicator matches if any of the configured
patterns matches (`matchAny`). If all patterns must match,
`matchAll` can be used.

In some cases, it may be useful to define a version range if
no pattern matches. This can be achieved by the `matchNone` strategy.

### Examples

Using the above example, the following version range would result
if given preconditions are met:

| Commit message                                     | File operations                                                                                  | Matching range                |
|----------------------------------------------------|--------------------------------------------------------------------------------------------------|-------------------------------|
| `[!!!][TASK] Drop support for PHP < 8.3`           | *any*                                                                                            | 1️⃣&nbsp;`major`              |
| *any*                                              | Deleted:&nbsp;`src/Controller/DashboardController.php`<br>Modified:&nbsp;`res/api.schema.json`   | 2️⃣&nbsp;`major`              |
| `[FEATURE] Add support for PHP 8.4`                | *any*                                                                                            | 3️⃣&nbsp;`minor`              |
| `[TASK] Use PHP 8.4 in CI`                         | *any*                                                                                            | 4️⃣&nbsp;`patch`              |
| `[BUGFIX] Avoid implicit nullable types`           | *any*                                                                                            | 4️⃣&nbsp;`patch`              |
| `[DOCS] Mention PHP 8.4 support in documentation`  | *any*                                                                                            | 4️⃣&nbsp;`patch`              |
| *any*                                              | Modified:&nbsp;`composer.json`<br>Added:`composer.lock`<br>Deleted:&nbsp;`composer.patches.json` | 5️⃣&nbsp;`patch`              |
| `[TASK] Remove deprecated dashboard functionality` | Deleted:&nbsp;`src/Controller/DashboardController.php`<br>Modified:&nbsp;`res/api.schema.json`   | 2️⃣&nbsp;`major`<sup>1)</sup> |
| `[TASK] Remove deprecated dashboard functionality` | Deleted:&nbsp;`src/Controller/DashboardController.php`                                           | 4️⃣&nbsp;`patch`<sup>2)</sup> |
| `[SECURITY] Avoid XSS in dashboard`                | Modified:&nbsp;`src/Controller/DashboardController.php`                                          | –<sup>3)</sup>                |

*Notes:*

<sup>1)</sup> Even if both indicators 2️⃣ and 4️⃣ match, indicator
2️⃣ takes precedence because of the higher version range.

<sup>2)</sup> Indicator 2️⃣ does not match, because only one
pattern matches, and the indicator's strategy is configured
to match all patterns (`matchAll`).

<sup>3)</sup> No indicator contains patterns for either the
commit message or modified file, hence no version range is
detected.
