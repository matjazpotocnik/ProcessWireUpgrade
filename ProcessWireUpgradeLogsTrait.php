<?php namespace ProcessWire;

/**
 * Centralized logging overrides for message(), warning(), error() and log().
 */
trait ProcessWireUpgradeLogsTrait {

	/**
	 * Log file name for centralized logging
	 */
	public const LOG_FILENAME = 'process-wire-upgrade';

	private ?bool $debug = null;

	protected function isDebugMode(): bool {
		if($this->debug !== null) return $this->debug;
		return (bool) $this->config->get('processWireUpgradeDebug');
	}

	public function enableDebug(bool $on = true): static {
		$this->debug = $on;
		return $this;
	}

	/**
	 * Override core message() to also write to the central upgrade log file.
	 *
	 * @param string|array<string, string> $text
	 * @param int|bool|string $flags
	 * @return $this
	 */
	public function message($text, $flags = 0): static {
		if($flags === true) $flags = Notice::log;

		if($this->noticeWantsLog($flags)) {
			$this->log->save(self::LOG_FILENAME, $this->prefixLogValue('[INFO] ', $text));
		}

		if($this->hasLogOnlyFlag($flags)) return $this;

		return parent::message($text, $this->stripNoticeLogFlag($flags));
	}

	/**
	 * Override core warning() to also write to the central upgrade log file.
	 *
	 * @param string|array<string, string> $text
	 * @param int|bool|string $flags
	 * @return $this
	 */
	public function warning($text, $flags = 0): static {
		if($flags === true) $flags = Notice::log;

		if($this->noticeWantsLog($flags)) {
			$this->log->save(self::LOG_FILENAME, $this->prefixLogValue('[WARN] ', $text));
		}

		if($this->hasLogOnlyFlag($flags)) return $this;

		return parent::warning($text, $this->stripNoticeLogFlag($flags));
	}

	/**
	 * Override core error() to also write to the central upgrade log file.
	 *
	 * @param string|array<string, string> $text
	 * @param int|bool|string $flags
	 * @return $this
	 */
	public function error($text, $flags = 0): static {
		if($flags === true) $flags = Notice::log;

		if($this->noticeWantsLog($flags)) {
			$this->log->save(self::LOG_FILENAME, $this->prefixLogValue('[ERROR] ', $text));
		}

		if($this->hasLogOnlyFlag($flags)) return $this;

		return parent::error($text, $this->stripNoticeLogFlag($flags));
	}

	/**
	 * Override core log() to write to the central upgrade log file
	 * instead of separate class-named log files.
	 *
	 * @param mixed $str value to log
	 * @param array<string, mixed> $options
	 * @return WireLog
	 */
	public function log(mixed $str = '', array $options = []): WireLog {
		// Preserve Wire::log() behavior: empty string means return WireLog instance.
		if($str === '') return $this->log;

		$options['name'] = self::LOG_FILENAME;
		$prefix = is_string($options['prefix'] ?? null) ? $options['prefix'] : '[LOG] ';
		unset($options['prefix']);
		$payload = $this->prefixLogValue($prefix, $str);

		/** @var WireLog $wireLog */
		$wireLog = parent::log($payload, $options);
		return $wireLog;
	}

	/**
	 * Write to the central upgrade log file only when debug mode is active.
	 *
	 * @param mixed $str
	 * @param array<string, mixed> $options
	 * @return WireLog
	 */
	public function debug(mixed $str = '', array $options = []): WireLog {
		if(!$this->isDebugMode()) return $this->log;

		if(!is_string($options['prefix'] ?? null)) {
			$options['prefix'] = '[DEBUG] ' . $this->getDebugPrefix();
		}

		return $this->log($str, $options);
	}

	/**
	 * Helper to log a message/error/warning, notify the UI, and redirect to a URL.
	 *
	 * @param string $text
	 * @param string $type 'message', 'warning', or 'error'
	 * @param string $url redirect URL, defaults to page url
	 * @param array<string, mixed> $context Optional data to log
	 * @return never
	 */
	protected function abort(string $text, string $type = 'error', string $url = '', array $context = []): never {
		$debugMessage = "[ABORT] $text";

		if(!empty($context)) {
			$json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			$debugMessage .= " | Context: " . ($json !== false ? $json : '(unencodable)');
		}

		$this->debug($debugMessage);
		
		$this->{$type}($text, true);

		if($url === '') {
			$url = ($this->page->id) ? $this->page->url : $this->config->urls->admin;
		}
		$this->session->location($url);
	}

	/**
	 * Prefix a value and return it as a string suitable for WireLog::save().
	 *
	 * @param string $prefix
	 * @param mixed $value
	 * @return string
	 */
	private function prefixLogValue(string $prefix, mixed $value): string {
		return $prefix . match(true) {
			is_string($value) => $value,
			is_int($value), is_float($value), is_bool($value) => (string) $value,
			$value instanceof \Stringable => (string) $value,
			is_array($value), is_object($value) => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '(unencodable)',
			default => '',
		};
	}

	/**
	 * @param int|bool|string $flags
	 */
	private function hasLogOnlyFlag($flags): bool {
		if(is_int($flags)) return ($flags & Notice::logOnly) === Notice::logOnly;
		if(is_string($flags)) return preg_match('/(^|\s)logOnly(\s|$)/i', $flags) === 1;
		return false;
	}

	/**
	 * Remove "log" from flags so parent notice handling does not write to default notice logs.
	 *
	 * @param int|bool|string $flags
	 * @return int|bool|string
	 */
	private function stripNoticeLogFlag($flags) {
		if(is_int($flags)) return $flags & ~Notice::log;

		if(is_string($flags)) {
			$stripped = preg_replace('/\blog\b/i', '', $flags);
			if($stripped === null) return $flags;
			$stripped = preg_replace('/\s+/', ' ', trim($stripped));
			return $stripped === null ? $flags : $stripped;
		}

		return $flags;
	}

	/**
	 * Determine if logging to the central file is wanted.
	 *
	 * Bool true has already been normalized to Notice::log by the caller.
	 *
	 * @param int|bool|string $flags
	 * @return bool
	 */
	private function noticeWantsLog(int|bool|string $flags): bool {
		return (is_int($flags) && ($flags & Notice::log)) ||
		       (is_string($flags) && preg_match('/\blog/i', $flags) === 1);
	}

	/**
	 * Generates a standard debugging prefix containing the calling class and method.
	 *
	 * @return string Formatted prefix, e.g., "[ProcessWireUpgrade::executePrepare] "
	 */
	protected function getDebugPrefix(): string {
		$prefix = '';
		
		// Fetch 3 frames deep to account for this helper and the logging wrapper
		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

		if(isset($backtrace[2])) {
			$frame = $backtrace[2];
			$callerMethod = $frame['function'];

			$classLabel = '';
			if(isset($frame['class'])) {
				$pos = strrpos($frame['class'], '\\');
				$callerClass = $pos === false ? $frame['class'] : substr($frame['class'], $pos + 1);
				$classLabel = "{$callerClass}::";
			}

			$prefix = "[{$classLabel}{$callerMethod}] ";
		}

		return $prefix;
	}

}
