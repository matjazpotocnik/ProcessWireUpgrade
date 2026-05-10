# ProcessWire Upgrade

Provides core and module upgrade notifications and optionally
installation from the admin. 

Can be used to upgrade your ProcessWire core or any module that
is available from <https://processwire.com/modules/>.

## Please note before using this tool

Files installed by this tool are readable and writable by Apache,
which may be a security problem in some hosting environments. 
Especially shared hosting environments where Apache runs as the
same user across all hosting accounts. If in doubt, you should
instead install core upgrades and/or modules and module upgrades
manually through your hosting account (FTP, SSH, etc.), which
is already very simple to do. This ensures that any installed files 
are owned and writable by your user account rather than Apache.

Even if you don't use this tool to install the upgrades, this tool
is still useful in identifying when upgrades are available. 

## Core Upgrades

This tool checks if upgrades are available for your ProcessWire installation. 
If available, it will download the update. If your file system is
writable, it will install the update for you. If your file system is
not writable, then it will install upgrade files in a writable 
location (under /site/assets/cache/) and give you instructions on 
what files to move. 

Options to upgrade from the master or dev branch are available. 
Additionally, you can upgrade to the latest commit (SHA) on the dev branch
for bleeding-edge updates.

This tool makes versioned backup copies of any files it 
overwrites during the upgrade. Should an upgrade fail for some
reason, you can manually restore from the backups should you
need to. 

After installing a core upgrade, you may want to manually update
the permissions of installed files to be non-writable depending,
on your environment. 


## Module Upgrades

Uses web services from modules.processwire.com to compare your
current installed versions of modules to the latest remote 
versions available. Provides upgrade links when it finds newer
versions of modules you have installed. 

For modules hosted on GitHub, it can also detect and offer updates
to the latest commit (SHA) even if no new version has been tagged,
allowing for bleeding-edge module updates.

## GitHub API Authentication

To increase the rate limit for GitHub API requests (from 60 to 5,000 requests per hour), you can optionally set a GitHub personal access token in your `/site/config.php` file:

```php
$config->githubToken = 'your_github_token_here';
```

To create a token:
1. Go to [GitHub Settings > Developer settings > Personal access tokens](https://github.com/settings/tokens)
2. Click "Generate new token (classic)"
3. Give it a descriptive name (e.g., "ProcessWire Upgrade Module")
4. Select the `public_repo` scope (read access to public repositories)
5. Click "Generate token"
6. Copy the token and add it to your `/site/config.php` as shown above

This is optional but recommended if you frequently check for updates or have many GitHub-hosted modules, as it prevents hitting the rate limit.
