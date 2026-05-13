# Changelog

## [v14] - 2026-05-13

### Added
- Config setting in the module setup to select Source for GitHub repo URL - can come from module info array or from PW modules directory
- Last module updated date

## [v13] - 2026-05-10

### Added
- Support for updating to the latest SHA commit for both ProcessWire core (dev-latest branch) and modules from GitHub repositories

### Fixed
- Fixed PHPStan static analysis errors (level max) across ProcessWireUpgradeCheck.module and ProcessWireUpgrade.module
- Updated PHPDoc annotations to match actual return types
- Added type-safe `toString()` helper method for consistent mixed-to-string conversion
- Sanitized user input 
- Optimized code flow
- Simplified long expressions and variable reuse for better readability
- Raised minimum PHP and PW version

## [v12] - 2023-03-26 (Forked Version)

### Changed
- Version bump to v12
- Added logging
- Use $files->rename($old, $new) - on windows, PHP rename() can fail with access denied if folder is locked, eg. explorer is opened in the directory. $files->rename() will try to copy if rename fails, since PW 3.0.178

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
