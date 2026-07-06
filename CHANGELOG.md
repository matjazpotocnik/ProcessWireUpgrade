# Changelog

## [v17] - 2026-07-06

### Fixed
- Fix SHA-based Sync/Upgrade being offered when local version is ahead of remote version (downgrade guard)
- Prevent parent-promotion from overriding a parent's ahead-of-remote status when a child has SHA changes

### Added
- Support symlinked module upgrades by routing through `./module-download?name=...` when symlink preservation is enabled, preventing core's installer from destroying the junction/symlink during backup

## [v16] - 2026-07-05

### Fixed
- Fix login notifications to include child-module upgrades
- Fix WireHttpMulti class bug
- Replace concurrent `WireHttpMulti` with sequential `WireHttp::getJSON()` per chunk, preventing partial-data cache corruption
- Require both `master` and `dev` branches before reading or saving cached core branch data
- Code simplification and other fixes

## [v15] - 2026-07-05

### Added
- Show uninstalled modules in the upgrade list when enabled
- Preserve symlinked module folders by downloading into the realpath target

## [v14] - 2026-05-13

### Added
- Add a setting to choose the GitHub repo URL source: the module info array or the ProcessWire modules directory
- Show the last module updated date in the admin UI

## [v13] - 2026-05-10

### Added
- Add GitHub SHA tracking to detect unreleased commits for ProcessWire core and GitHub-hosted modules

### Fixed
- Fix PHPStan static analysis errors across ProcessWireUpgradeCheck.module and ProcessWireUpgrade.module
- Update PHPDoc annotations to match actual return types
- Add a type-safe `toString()` helper for consistent mixed-to-string conversion
- Sanitize user input
- Simplify code flow
- Shorten long expressions and unnecessary variable reuse
- Raise the minimum PHP and ProcessWire version requirements

## [v12] - 2023-03-26 (Forked Version)

### Changed
- Bump the module to v12
- Add logging
- Use $files->rename($old, $new) because PHP rename() can fail on Windows when a folder is locked; $files->rename() falls back to copy in PW 3.0.178+

## [Unreleased] - 2021-04-22 (Original)

### Changed
- A few minor fixes and improvements

## [Unreleased] - 2021-04-16 (Original)

### Changed
- Additional updates and improvements

## [Unreleased] - 2021-04-15 (Original)

### Changed
- Update module to be PW 3.x exclusive
- Improve module upgrades information
- Remove irrelevant core branches
- Lots of code refresh

## [Unreleased] - 2016-09-29 (Original)

### Changed
- Update version requirements
- Upgrade to support new ProcessWire repositories and PW 3.x and 2.8.x upgrades

## [Unreleased] - 2015-12-28 (Original)

### Changed
- Updates to ProcessWireUpgrade accounting for current version info
- Couple of small bug fixes

## [Unreleased] - 2015-04-10 (Original)

### Fixed
- Fix issue with modules directory limit=10, changed to limit=100
- Update to refresh modules list in the Process module

## [Unreleased] - 2015-03-27 (Original)

### Changed
- Make the automatic-login-check optional, per a config setting in ProcessWireUpgradeCheck module
- Default is now OFF, so must be enabled if you want that feature

## [Unreleased] - 2014-12-04 (Original)

### Fixed
- Minor bug fixes

## [Unreleased] - 2014-09-12 (Original)

### Added
- Major update to include module version detection
- Automatic upgrade notifications (at login for superuser)
- Module upgrade links

## [Unreleased] - 2014-09-08 (Original)

### Changed
- Update language in some parts
- Add a set_time_limit
- Additional updates for QA

## [Unreleased] - 2014-09-07 (Original)

### Changed
- Enhancements and bug fixes

## [1.0.0] - 2014-09-05 (Original)

### Added
- Initial release
