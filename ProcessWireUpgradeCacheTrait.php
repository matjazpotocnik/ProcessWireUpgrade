<?php namespace ProcessWire;

/**
 * Trait handling the cache registry and its associated getter methods.
 */
trait ProcessWireUpgradeCacheTrait {

	public const CACHE_PREFIX = 'ProcessWireUpgrade_';

	/**
	 * Centralized cache key registry with TTL.
	 *
	 * @var array<string, array{key: string, ttl: int|string}>
	 *
	 * TTL values are either integer seconds (e.g. 3600*6 for 6 hours) or
	 * WireCache string constants (expireNever, expireHourly). The string
	 * constants are preferred for semantic intent; they cannot be normalized
	 * to integers because expireNever is a sentinel date string, not a duration.
	 */
	private array $cacheRegistry = [

		// Modules info from the PW modules directory API (module_version,version,requires,urls)
		'moduleVersionsData' => [
			'key' => 'moduleVersionsData',
			'ttl' => WireCache::expireHourly,
		],

		// Core branch list fetched from GitHub (`main`, `dev`)
		'branches' => [
			'key' => 'branches',
			'ttl' => 3600 * 6, // 6 hours
		],

		// Core branches and modules versions used at login hook
		'loginHook' => [
			'key' => 'loginHook',
			'ttl' => 3600 * 12, // 12 hours
		],

		// Commit date for a specific core commit SHA, used to detect when a version bump
		'coreCommitDate' => [
			'key' => 'coreCommitDate_%s',
			'ttl' => WireCache::expireNever,
		],

		// PW version
		'pwVersion' => [
			'key' => 'PW_%s',
			'ttl' => 86400 * 30, // 30 days
		],

		// Latest commit SHA for a module repo (commits after version bump, aka "silent upgrade")
		'remoteSha' => [
			'key' => 'remoteSha_%s_%s',
			'ttl' => 3600 * 6, // 6 hours
		],

		// Fallback cache when the GH API is rate-limited or fails, preserves the last successful SHA
		'remoteShaLastGood' => [
			'key' => 'remoteShaLastGood_%s_%s',
			'ttl' => WireCache::expireNever,
		],

		// SHA baseline for an installed module, the "last known good" checksum used to detect local modifications
		'installedSha' => [
			'key' => 'installedSha_%s_%s',
			'ttl' => WireCache::expireNever,
		],

	];

	/**
	 * Get the raw key template from the registry (with prefix, without sprintf expansion).
	 *
	 * @param string $name Registry key name
	 * @return string
	 * @throws WireException If registry entry does not exist
	 */
	private function cacheKeyTemplate(string $name): string {
		if(!isset($this->cacheRegistry[$name])) {
			throw new WireException("Unknown cache key: $name");
		}
		return self::CACHE_PREFIX . $this->cacheRegistry[$name]['key'];
	}

	/**
	 * Get the cache key string for the given registry entry.
	 *
	 * Dynamic keys accept sprintf-style format arguments.
	 *
	 * Example: $this->cacheKey('remoteSha', 'processwire', 'processwire')
	 * Example output: 'ProcessWireUpgrade_remoteSha_processwire_processwire'
	 *
	 * @param string $name Registry key name
	 * @param string|int ...$args Format arguments for dynamic keys
	 * @return string The fully-resolved cache key
	 * @throws WireException If registry entry does not exist
	 * @throws WireException If any argument is an empty string
	 * @throws WireException If argument count doesn't match format specifiers
	 */
	protected function cacheKey(string $name, string|int ...$args): string {
		$keyTemplate = $this->cacheKeyTemplate($name);
		if($args === []) return $keyTemplate;

		// Strip escaped percents so %%s doesn't count as a placeholder, then count all
		// valid printf-style specifiers for string and decimal integers only: %s, %d,
		// and positional variants like %1$s, %2$d.
		$expectedTokens = preg_match_all('/%(?:\d+\$)?[sd]/', str_replace('%%', '', $keyTemplate));
		if(count($args) !== $expectedTokens) {
			throw new WireException("Cache key '$name' expects $expectedTokens arguments, but " . count($args) . " provided.");
		}

		foreach ($args as $arg) {
			if ($arg === '') {
				throw new WireException("Cache key '$name' cannot accept an empty string identifier.");
			}
		}

		// Note: positional format specifiers (e.g. %1$s, %2$d) are supported but not
		// validated for index ranges. vsprintf() will throw if indices are out of bounds.
		// This is acceptable for internal use where templates are controlled.
		return vsprintf($keyTemplate, $args);
	}

	/**
	 * Get the TTL for the given registry entry.
	 *
	 * @param string $name Registry key name
	 * @return int|string Cache TTL in seconds, or WireCache::expireNever
	 * @throws WireException If registry entry does not exist
	 */
	protected function cacheTtl(string $name): int|string {
		if (!isset($this->cacheRegistry[$name])) {
			throw new WireException("Unknown cache key: $name");
		}

		return $this->cacheRegistry[$name]['ttl'];
	}

	/**
	 * Get cache name and TTL as a single object.
	 *
	 * @param string $name Registry key name
	 * @param string|int ...$args Format arguments for dynamic keys
	 * @return object{name: string, ttl: int|string}
	 * @throws WireException If the registry entry does not exist
	 */
	protected function cacheEntry(string $name, string|int ...$args): object {
		return (object) [
			'name' => $this->cacheKey($name, ...$args),
			'ttl'  => $this->cacheTtl($name),
		];
	}

	/**
	 * Get the static prefix for dynamic cache keys.
	 *
	 * Example: remoteSha => ProcessWireUpgrade_remoteSha_
	 */
	protected function cachePrefix(string $name): string {
		$keyTemplate = $this->cacheKeyTemplate($name);
		$pos = strpos($keyTemplate, '%');
		if($pos === false) {
			throw new WireException("cachePrefix() called on non-dynamic key '$name'");
		}
		return substr($keyTemplate, 0, $pos);
	}

}
