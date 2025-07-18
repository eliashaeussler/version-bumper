# Presets

Config presets can be used to ship preconfigured configuration
for specific project types. When using presets in your config
file, the resulting config will be merged with your main config
object.

Presets are identified by a name and can be customized by preset
options. The available options differ between presets.

## Available presets

### Composer package (`composer-package`)

Preset for Composer packages managed by a `composer.json` file.

| Option | Type   | Required | Description                                                                |
|--------|--------|----------|----------------------------------------------------------------------------|
| `path` | String | –        | Directory where `composer.json` is located, defaults to the current directory. |

### Conventional Commits (`conventional-commits`)

Preset for [Conventional Commits 1.0.0](https://www.conventionalcommits.org/en/v1.0.0/).

_This preset is not configurable._

### NPM package (`npm-package`)

Preset for NPM packages managed by a `package.json` file.

| Option        | Type   | Required       | Description                                                               |
|---------------|--------|----------------|---------------------------------------------------------------------------|
| `packageName` | String | –<sup>1)</sup> | Name of the package as configured in `package.json`.                      |
| `path`        | String | –              | Directory where `package.json` is located, defaults to the current directory. |

<sup>1)</sup> When omitted, the package name is automatically resolved
from the given `package.json` file.

### TYPO3 extension (`typo3-extension`)

Preset for legacy or public TYPO3 extensions managed by an
`ext_emconf.php` file.

| Option          | Type                                            | Required | Description                                                          |
|-----------------|-------------------------------------------------|----------|----------------------------------------------------------------------|
| `documentation` | Boolean or `auto`/`legacy` keyword<sup>2)</sup> | –        | Define whether or not a ReST documentation is used in the extension. |

<sup>2)</sup> By default or if keyword `auto` is used, ReST documentation
version may be replaced, if existent, but version bumping will not fail if
a ReST documentation does not exist. If `legacy` keyword is used, legacy
Sphinx-based rendering documentation files will be used for version bumps.

### TYPO3 commit guidelines (`typo3-commit-guidelines`)

Preset for TYPO3 projects which adhere to the
[Commit Message rules for TYPO3 CMS](https://docs.typo3.org/m/typo3/guide-contributionworkflow/main/en-us/Appendix/CommitMessage.html).

_This preset is not configurable._

## Example

```yaml
presets:
  # Short syntax, only identifier is provided
  - composer-package

  # Extended syntax, identifier and options are provided
  - name: npm-package
    options:
      packageName: '@vendor/my-fancy-library'

  # Extended syntax, but without options (not practically relevant, but possible)
  - name: typo3-extension
```
