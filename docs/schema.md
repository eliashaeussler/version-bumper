# Schema

The config file must follow a given schema:

```yaml
presets:
  - composer-package
  - name: npm-package
    options:
      packageName: '@vendor/my-fancy-library'

filesToModify:
  - path: relative/or/absolute/path/to/file
    patterns:
      # Each pattern must contain a {%version%} placeholder
      - '"version": "{%version%}"'
    reportUnmatched: true

releaseOptions:
  commitMessage: '[RELEASE] Release of my-fancy-library {%version%}'
  overwriteExistingTag: true
  signTag: true
  tagName: 'v{%version%}'

# Relative (to config file) or absolute path to project root
rootPath: ../

versionRangeIndicators:
  - range: major
    strategy: matchAll
    patterns:
      - type: fileDeleted
        pattern: '/^src\/Controller\/.+Controller\.php$/'
      - type: fileModified
        pattern: '/^res\/api\.schema\.json$/'
      - type: commitMessage
        pattern: '/^\[!!!]/'
```

> [!TIP]
> Have a look at the shipped [JSON schema](../res/version-bumper.schema.json).

## Presets

> [!TIP]
> Read more about [presets](presets.md).

| Property            | Type                     | Required       | Description                                                                                                                  |
|---------------------|--------------------------|----------------|------------------------------------------------------------------------------------------------------------------------------|
| `presets`           | Array of objects/strings | –              | List of config presets to apply (read more at [Presets](presets.md)).                                                        |
| `presets.*`         | String                   | ✅<sup>1)</sup> | Preset identifier, can be used when no additional options are to be configured. Otherwise, take a look at the next property. |
| `presets.*.name`    | String                   | ✅<sup>1)</sup> | Preset identifier, can be used when additional options are to be configured (see next property).                             |
| `presets.*.options` | Object                   | ✅<sup>1)</sup> | Additional preset options. The available options differ from preset to preset.                                               |

<sup>1)</sup> You can either configure presets using string syntax (provide the
preset identifier only) or using object syntax (provide identifier and options).

## Files to modify

| Property                          | Type             | Required | Description                                                                                                                                                                                                                                                                       |
|-----------------------------------|------------------|----------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `filesToModify`                   | Array of objects | ✅        | List of files that contain versions which are to be bumped.                                                                                                                                                                                                                       |
| `filesToModify.*.path`            | String           | ✅        | Relative or absolute path to the file. Relative paths are calculated from the configured (or calculated) project root.                                                                                                                                                            |
| `filesToModify.*.patterns`        | Array of strings | ✅        | List of version patterns to be searched and replaced in the configured file. Each pattern must contain a `{%version%}` placeholder that is replaced by the new version. Patterns are internally converted to regular expressions, so feel free to use regex syntax such as `\s+`. |
| `filesToModify.*.reportUnmatched` | Boolean          | –        | Show warning if a configured pattern does not match file contents. Useful in combination with the `--strict` command option.                                                                                                                                                      |
| `filesToModify.*.reportMissing`   | Boolean          | –        | Fail if file to modify does not exist (defaults to `true`).                                                                                                                                                                                                                       |

## Release options

| Property                              | Type    | Required | Description                                                                                                                         |
|---------------------------------------|---------|----------|-------------------------------------------------------------------------------------------------------------------------------------|
| `releaseOptions`                      | Object  | –        | Set of configuration options to respect when a new release is created (using the `--release` command option).                       |
| `releaseOptions.commitMessage`        | String  | –        | Commit message pattern to use for new releases. May contain a `{%version%}` placeholder that is replaced by the version to release. |
| `releaseOptions.overwriteExistingTag` | Boolean | –        | Overwrite an existing tag by deleting it before a new tag is created.                                                               |
| `releaseOptions.signTag`              | Boolean | –        | Use Git's `-s` command option to sign the new tag using the Git-configured signing key.                                             |
| `releaseOptions.tagName`              | String  | –        | Tag name pattern to use for new releases. Must contain a `{%version%}` placeholder that is replaced by the version to release.      |

## Root path

| Property   | Type   | Required | Description                                                                                                                                                                                                                                         |
|------------|--------|----------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `rootPath` | String | –        | Relative or absolute path to project root. This path will be used to calculate paths to configured files if they are configured as relative paths. If the root path is configured as relative path, it is calculated based on the config file path. |

## Version range indicators

| Property                                      | Type             | Required | Description                                                                                         |
|-----------------------------------------------|------------------|----------|-----------------------------------------------------------------------------------------------------|
| `versionRangeIndicators`                      | Array of objects | –        | List of indicators to auto-detect a version range to be bumped.                                     |
| `versionRangeIndicators.*.patterns`           | Array of objects | ✅        | List of version range patterns to match for this indicator.                                         |
| `versionRangeIndicators.*.patterns.*.pattern` | String           | ✅        | Regular expression to match a specific version range indicator.                                     |
| `versionRangeIndicators.*.patterns.*.type`    | String (enum)    | ✅        | Type of the pattern to match, can be `commitMessage`, `fileAdded`, `fileDeleted` or `fileModified`. |
| `versionRangeIndicators.*.range`              | String (enum)    | ✅        | Version range to use when patterns match, can be `major`, `minor`, `next` or `patch`.               |
| `versionRangeIndicators.*.strategy`           | String (enum)    | –        | Match strategy for configured patterns, can be `matchAll`, `matchAny` (default) or `matchNone`.     |
