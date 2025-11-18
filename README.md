<div align="center">

# Version Bumper

[![Coverage](https://img.shields.io/coverallsCoverage/github/eliashaeussler/version-bumper?logo=coveralls)](https://coveralls.io/github/eliashaeussler/version-bumper)
[![CGL](https://img.shields.io/github/actions/workflow/status/eliashaeussler/version-bumper/cgl.yaml?label=cgl&logo=github)](https://github.com/eliashaeussler/version-bumper/actions/workflows/cgl.yaml)
[![Tests](https://img.shields.io/github/actions/workflow/status/eliashaeussler/version-bumper/tests.yaml?label=tests&logo=github)](https://github.com/eliashaeussler/version-bumper/actions/workflows/tests.yaml)
[![Supported PHP Versions](https://img.shields.io/packagist/dependency-v/eliashaeussler/version-bumper/php?logo=php)](https://packagist.org/packages/eliashaeussler/version-bumper)

</div>

A Composer plugin to bump project versions during release preparations.
Provides a Composer command `bump-version` and offers an easy-to-use PHP
API for integration in other frameworks.

## 🔥 Installation

[![Packagist](https://img.shields.io/packagist/v/eliashaeussler/version-bumper?label=version&logo=packagist)](https://packagist.org/packages/eliashaeussler/version-bumper)
[![Packagist Downloads](https://img.shields.io/packagist/dt/eliashaeussler/version-bumper?color=brightgreen)](https://packagist.org/packages/eliashaeussler/version-bumper)

```bash
composer require --dev eliashaeussler/version-bumper
```

## ⚡ Quickstart

Add a `version-bumper.yaml` config file:

```yaml
# version-bumper.yaml

presets:
  - composer-package

releaseOptions:
  commitMessage: '[RELEASE] Release of my-fancy-library {%version%}'
```

Bump next major/minor/patch version:

```bash
composer bump-version [major|minor|patch] --release
```

## 📝 Documentation

* Usage
  - [Console command](docs/cli.md)
  - [PHP API](docs/api.md)
  - [Version range](docs/version-range.md)
* Configuration
  - [Config file](docs/config-file.md)
  - [Presets](docs/presets.md)
  - [Schema](docs/schema.md)

## 🧑‍💻 Contributing

Please have a look at [`CONTRIBUTING.md`](CONTRIBUTING.md).

## ⭐ License

This project is licensed under [GNU General Public License 3.0 (or later)](LICENSE).
