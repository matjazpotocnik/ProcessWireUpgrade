# ProcessWire Upgrade

ProcessWire Upgrade shows available core and module updates in the admin and lets you install them from one place.

This fork adds GitHub-based commit tracking for unreleased updates, direct GitHub installs for modules that are not in the official directory, optional listing of uninstalled modules, and an option to preserve symlinked module folders during GitHub downloads.

## What It Does

- Checks the ProcessWire core for newer versions on the `master` and `dev` branches
- Checks installed modules against the official ProcessWire modules directory
- Can also check GitHub for unreleased commits after the latest tagged version
- Lets you download and install modules directly from GitHub when a repository is available
- Can show uninstalled modules that are present in `/site/modules/`
- Groups parent and child modules together when one module installs another
- Guides you through core upgrades step by step, including optional database backup and rollback-safe file handling

## Requirements

- PHP 8.2 or newer
- ProcessWire 3.0.246 or newer
- `cURL` PHP extension (`ext-curl`) for GitHub and module directory HTTP checks
- `ZipArchive` PHP extension for installing core upgrades

## Installation

1. Copy the module folder into `/site/modules/`
2. In the admin, go to Modules -> Refresh
3. Install `ProcessWireUpgrade`
4. Its companion module, `ProcessWireUpgradeCheck`, installs automatically
5. A new **Upgrades** page appears under Setup

## Using It

Go to **Setup -> Upgrades**.

The first time you open the page, the module loads the current core and module version data. After that, it shows a table with one row per core branch or module and a status label such as:

- **Upgrade** - a newer version is available
- **Download** - a module is not installed yet, but a GitHub download is available
- **Sync** - the version number has not changed, but GitHub has newer commits
- **Unavailable** - an update exists but no download URL is available
- **Up-to-date** - nothing to do
- **Up-to-date+** - your local version is newer than the one in the directory (common with forks or dev builds)
- **Not in directory** - shown only when debug mode is enabled for modules that have no directory entry

Click the action link in the **Status** column to install or download.

> **Important**: All GitHub downloads (Sync, Download for uninstalled modules, and symlink-preserving Upgrade) are routed through `./module-download?name=...`. This requires `$config->moduleInstall('download', true);` in `/site/config.php`. Without it, the installer will reject downloads from non-official URLs. See [Advanced Configuration](#advanced-configuration).

## Refreshing and GitHub Tracking

Click the **Refresh Core & Modules List** button (top-right of the upgrades page) to reload core and module version data from the ProcessWire directory.

A checkbox below the table lets you toggle GitHub module tracking on or off before each refresh. When enabled, the module also checks GitHub for unreleased commits after the latest tagged version and shows a **Last Commit** column with the date of the most recent activity. The tracking state is saved persistently, so the checkbox retains its setting across page loads.

If GitHub tracking is enabled, the module stores a baseline SHA for installed modules and compares future checks against it. That lets it detect unreleased updates even when the version number has not changed yet.

A GitHub token is strongly recommended if you check many modules often. Without one, you may hit the unauthenticated rate limit.

## GitHub API Authentication

To increase the rate limit for GitHub API requests from 60 to 5,000 per hour, add a personal access token to your `/site/config.php` file:

```php
$config->githubToken = 'your_github_token_here';
```

To create a token:

1. Go to [GitHub Settings > Developer settings > Personal access tokens](https://github.com/settings/tokens)
2. Click "Generate new token (classic)"
3. Give it a descriptive name, such as "ProcessWire Upgrade Module"
4. Select the `public_repo` scope for read access to public repositories
5. Click "Generate token"
6. Copy the token into your `/site/config.php` file as shown above

This is optional, but recommended if you frequently check for updates or have many GitHub-hosted modules.

## Settings

Open the configuration for `ProcessWireUpgradeCheck` in the ProcessWire admin to access these options:

- **Check for upgrades on login?** - runs upgrade checks automatically on superuser login
- **Prioritize Local GitHub URLs?** - uses the module info `href` instead of the directory repository URL when available
- **Check Uninstalled Modules?** - includes modules that are present in `/site/modules/` but not installed
- **Enable GitHub module tracking?** - checks GitHub for unreleased commits after the latest tagged version and shows a **Last Commit** column with the date of the most recent activity; also enables **Sync** actions that download directly from GitHub when the version number has not changed but newer commits exist
- **Clear cache?** - clears cached version and GitHub data

## Advanced Configuration

These settings are optional and go in `/site/config.php`.

```php
// GitHub API token - increases the API rate limit
$config->githubToken = 'your_github_token_here';

// Required for all GitHub downloads (Sync, Download for uninstalled modules,
// and symlink-preserving Upgrade). Without this, ProcessWire's module installer
// will reject downloads from non-official URLs.
$config->moduleInstall('download', true);

// Preserve symlinked module folders during GitHub downloads
// When enabled, downloads are written to the real path target instead of replacing the symlink.
$config->processWireUpgradePreserveSymlinks = true;

// Show extra debug information and local-only modules
$config->processWireUpgradeDebug = true;
```

## Notes

- If a module is installed through a symlinked folder and preserve-symlinks is enabled, GitHub downloads are written to the real target path
- If a module is listed as **Unavailable**, it must be updated manually
- Core upgrades are handled separately from module upgrades and may include a database backup step

## Troubleshooting

- **GitHub authentication failed** - update `$config->githubToken`
- **GitHub is temporarily limiting requests** - wait for the rate limit to reset or add a token
- **A module is missing from the directory** - enable debug mode to see local-only modules
- **A symlinked module folder is being replaced** - enable `$config->processWireUpgradePreserveSymlinks`

## Credits

Originally authored by Ryan Cramer.

This fork adds GitHub commit tracking, direct GitHub installs, optional uninstalled-module checks, symlink-preserving downloads, and additional admin UI improvements.

License: MPL 2.0, same as ProcessWire core.
