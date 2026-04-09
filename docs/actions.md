# Actions

An action describes an executable process which can be triggered right
before versions are bumped or directly after a successful version bump.
This typically reflects updates to lock files after versions have been
bumped in manifest files (e.g. update `package-lock.json` after
version in `package.json` has been bumped to a new version).

Actions are identified by a unique identifier. At the moment, they are
not configurable.

## Action types

Actions can be triggered at the following positions in the lifecycle:

* **Pre-actions** are executed right before a version is bumped
* **Post-actions** are excecuted after a successful version bump

> [!TIP]
> Learn more about how to configure actions for the appropriate action
> types in the [schema](schema.md#files-to-modify).

## Available actions

### Update `composer.lock` file (`composer-lock`)

Action to update an existing `composer.lock` file after a version bump
in `composer.json` has happened. Note that this is normally not required,
because you should follow Composer's
[Best Practices](https://getcomposer.org/doc/04-schema.md#version) and
avoid writing the current version into your `composer.json` file.

### Update `package-lock.json` file (`package-lock`)

Action to update an existing `package-lock.json` file after a version
bump in `package-json` has happened.
