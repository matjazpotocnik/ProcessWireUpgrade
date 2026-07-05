# AGENTS.md — ProcessWireUpgrade

## File structure

| File | Role |
|------|------|
| `ProcessWireUpgrade.module` | Process module — admin UI, all `execute*()` methods, core upgrade flow |
| `ProcessWireUpgradeCheck.module` | Data engine — fetches version data, checks GitHub SHAs, returns structured results |
| `ProcessWireUpgradeCacheTrait.php` | Centralized cache key registry with TTLs — used by both classes |
| `ProcessWireUpgradeLogsTrait.php` | Logging, debug output, `message()`/`warning()`/`error()` overrides |
| `ProcessWireUpgradeUtilsTrait.php` | `toString()` and other shared utilities |
| `ProcessWireUpgradeWireHttpMulti.php` | Concurrent cURL-multi HTTP client — used for batched GitHub API calls |

## Load-bearing assumptions

- A child module discovered via a parent's `installs[]` array is assumed to share the parent's GitHub repo. This makes parent-promotion safe (upgrading the parent's zip brings the child along). If that assumption is ever wrong for some third-party module pair, the promoted "Upgrade" action silently fetches the wrong artifact.
- `$item['sha']` / `$item['shaDate']` are populated for *any* GitHub-tracked module, not only ones with a pending upgrade — they're informational metadata for the "Last Commit" column. Gating on `!empty($item['sha'])` means "SHA tracking has run for this module," not "has an update."

## Non-obvious core behavior

- `module/?update=$name` (core's `ProcessModule`) only ever checks the official PW modules directory. It never contacts GitHub. If the directory version matches what's installed, core redirects with "already up-to-date" — a dead click. GitHub-sourced updates must go through this module's own `module-download?...&repo=...&sha=...` endpoint.
- PW's integer-style `version` (e.g. `14`) is not directly `version_compare()`-able against a dotted string version without running it through `Modules::formatVersion()` first.

## Cache discipline

- All cache keys must go through `cacheKey()` / `cacheTtl()` / `cacheEntry()` — never hardcode a `ProcessWireUpgrade_*` string. The registry is the single source of truth for key shape and TTL.
- `installedSha` is intentionally `expireNever` — it's a baseline, not a cache, and should survive normal cache-clearing paths. (`___uninstall()` / `___upgrade()` currently do wipe it via the prefix wildcard — that's accepted, not a bug.)

## Scope discipline

- SHA/commit tracking exists specifically to catch unreleased commits with no version signal at all — this is the stated motivation for the fork. It already handles non-directory modules too (Phase 4a in `getModuleVersions()` runs SHA checks against any module with a GitHub `href`, whether or not it's in the PW directory). Do not build a raw-file version-regex fallback alongside this — that approach requires a version bump to trigger, which is the exact limitation the SHA system was built to avoid.

## Fork-specific UI/config behavior

- `useModuleInfoRepo` controls which GitHub repository URL is used for module checks: the official modules-directory repo link or the local module `href`.
- `showUninstalled` is intentionally part of the module configuration because the upgrade list can include modules that are present in `/site/modules/` but not installed yet.
- `processWireUpgradePreserveSymlinks` is the GitHub download/install behavior for symlinked module folders: when enabled, downloads target the realpath so the symlink itself is preserved.
- README should stay end-user friendly and compact; keep the deeper implementation notes here instead of expanding the user-facing README further.

## GitHub error state machine

`$gitHubState` on `ProcessWireUpgradeCheck` tracks API degradation for the current request:

| State | Meaning | Effect |
|-------|---------|--------|
| `''` | No error | Normal operation |
| `'fetch'` | Transient HTTP/network error | Falls back to `remoteSha` then `remoteShaLastGood` cache |
| `'rate'` | 403/429 rate limited | Same fallback; user notified to add/check token |
| `'auth'` | 401 bad credentials | Same fallback; user notified to update token |

`'rate'` and `'auth'` are terminal — once set, all further GitHub requests this cycle are skipped. `trackGitHubHttpFailure()` is a no-op if the state is already terminal. `getGitHubRefreshWarning()` produces the user-facing notice shown at the end of the request.

## Parent-promotion

When any child in `$item['childModules']` has `new > 0`, the parent's local `$new` is forced to `1` before the action-decision chain runs in `execute()`. This lets the existing Sync/Upgrade branches fire for the parent using the parent's own `sha`/`urls.repo` data (correct — parent and child share one repo, see load-bearing assumption above). The comment `#promoted-parent-link` in `execute()` marks this. The guard `!$childNeedsUpdate` that previously blocked Sync for promoted parents has been intentionally removed.

## Core branch model

- Core checks expose `master` and `dev`.
- The `dev` row may include a short SHA suffix in the remote version for informational visibility, but upgrades still target the live `dev` branch download URL.

## WireHttpMulti

- `enableDebug()` exists on `WireHttpMulti` and is intentionally left commented-out at the call site in `newHttpClient()`. It is a diagnostic tool to enable manually, not dead code — do not remove it.
- Error arrays are capped at 3 entries + overflow count to prevent UI message flooding on large batch failures.

## Agent-specific

- Don't trust a prior session's description of "what the code currently does" — re-read the live file before reasoning about behavior or proposing an edit. This codebase has been through many iterations; stale assumptions from an earlier turn are a bigger risk than not knowing the code at all.
- When a question is "what does core do here," trace the actual core source in `wire/` rather than inferring from PW's general reputation — real behavior (like the directory-only check above) isn't guessable from outside.
