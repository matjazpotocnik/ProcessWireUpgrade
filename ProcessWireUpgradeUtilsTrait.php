<?php namespace ProcessWire;

/**
 * Shared functionality for ProcessWireUpgrade and ProcessWireUpgradeCheck.
 */
trait ProcessWireUpgradeUtilsTrait {

	/**
	 * Convert mixed values to string, with optional dot-notation path for arrays.
	 *
	 * @param mixed $value
	 * @param string $path dot-notation path e.g. 'commit.message'
	 * @param int $depth Current recursion depth (internal use)
	 */
	private function toString(mixed $value, string $path = '', int $depth = 0): string {
		if($depth > 10) return '';

		if($path !== '' && is_array($value)) {
			foreach(explode('.', $path) as $key) {
				if(!is_array($value) || !array_key_exists($key, $value)) return '';
				$value = $value[$key];
			}
		}

		return match(true) {
			is_string($value) => $value,
			is_array($value) => implode(' ', array_filter(
				array_map(fn($v) => $this->toString($v, '', $depth + 1), $value),
				fn($v) => $v !== ''
			)),
			is_int($value) || is_float($value) || is_bool($value) => (string) $value,
			$value instanceof \Stringable => (string) $value,
			default => '',
		};
	}

}
