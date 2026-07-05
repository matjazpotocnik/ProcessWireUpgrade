<?php namespace ProcessWire;

/**
 * Value-object representing a single queued request.
 */
class WireHttpRequestSpec {

	/** @var string HTTP method, default GET */
	public string $method = 'GET';

	/** @var string Target URL */
	public string $url;

	/** @var array<string, mixed>|null Associative POST fields, used when body is null */
	public ?array $post = null;

	/** @var string|null Destination file path for download requests */
	public ?string $toFile = null;

	/** @var array<int|string, mixed>|null Per-request cURL option overrides */
	public ?array $options = null;

	/** @var string|null Raw POST body string; takes precedence over post, Content-Type defaults to application/json */
	public ?string $body = null;


	/**
	 * @param array<int|string, mixed>|null $options
	 */
	public static function get(string $url, ?array $options = null): self {
		$s = new self();
		$s->url = $url;
		$s->options = $options;
		return $s;
	}

	/**
	 * @param array<string, mixed> $postFields
	 * @param array<int|string, mixed>|null $options
	 */
	public static function post(string $url, array $postFields, ?array $options = null): self {
		$s = new self();
		$s->method  = 'POST';
		$s->url = $url;
		$s->post = $postFields;
		$s->options = $options;
		return $s;
	}

	/**
	 * @param array<int|string, mixed>|null $options
	 */
	public static function download(string $url, string $toFile, ?array $options = null): self {
		$s = new self();
		$s->method = 'GET';
		$s->url = $url;
		$s->toFile = $toFile;
		$s->options = $options;
		return $s;
	}
}

/**
 * Immutable result object populated post-flight.
 */
class WireHttpRequestResult {
	/**
	 * @param array<string, string>|null $headers
	 * @param array<int|string, mixed> $specOptions
	 */
	public function __construct(
		public readonly string    $url,
		public readonly string    $method,
		public readonly bool      $success,
		public readonly int       $httpCode,
		public readonly int|false $curlErrorCode,
		public readonly ?string   $curlError,
		public readonly ?string   $body,
		public readonly ?array    $headers,
		public readonly ?string   $toFile,
		public readonly array     $specOptions,
	) {}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return [
			'url'           => $this->url,
			'method'        => $this->method,
			'success'       => $this->success,
			'httpCode'      => $this->httpCode,
			'curlErrorCode' => $this->curlErrorCode,
			'curlError'     => $this->curlError,
			'body'          => $this->body,
			'headers'       => $this->headers,
			'toFile'        => $this->toFile,
			'specOptions'   => $this->specOptions,
		];
	}
}

/**
 * WireHttp-compatible async HTTP queue powered by curl_multi.
 * @property array<string, string> $headers Inherited from WireHttp
 */
class WireHttpMulti extends WireHttp {

	/** @var list<WireHttpRequestSpec> Pending request definitions */
	private array $queue = [];

	/** @var list<WireHttpRequestResult> Ordered result list after execute() */
	private array $results = [];

	/** @var array<int, WireHttpRequestResult> Results keyed by spawn sequence */
	private array $resultsBySeq = [];

	/** @var int Maximum concurrent requests */
	private int $maxConcurrent = 5;

	/** @var bool Whether debug logging is enabled */
	private bool $debug = false;

	/** @var bool Whether to verify SSL certificates */
	private bool $sslVerify = true;

	/** @var array<int, string> Error messages from failed requests */
	protected $error = [];

	public function setConcurrency(int $n): self {
		$this->maxConcurrent = max(1, $n);
		return $this;
	}

	public function setSslVerify(bool $verify): self {
		$this->sslVerify = $verify;
		return $this;
	}

	public function enableDebug(bool $on = true): self {
		$this->debug = $on;
		return $this;
	}

	public function enqueue(WireHttpRequestSpec|string $item): self {
		$this->queue[] = $item instanceof WireHttpRequestSpec ? $item : WireHttpRequestSpec::get((string)$item);
		if ($this->debug) {
			$s = (is_string($item)) ? $item : $item->url;
			$this->log(sprintf('[WireHttpMulti] enqueued %s', $s));
		}
		return $this;
	}

	/**
	 * Natively mimics WireHttp::get(), but concurrently across an array of URLs.
	 *
	 * @param array<int|string, string|WireHttpRequestSpec> $requests
	 * @param int|null $concurrency Overrides class maxConcurrent for this call only
	 * @return array<int|string, string|bool> Returns the mapped body string, true for successful downloads, or false on failure
	 */
	public function getMulti(array $requests, ?int $concurrency = null): array {
		$savedQueue  = $this->queue;
		$savedConcurrency = $this->maxConcurrent;
		$this->queue = [];
		$keys = [];

		foreach ($requests as $key => $item) {
			if (is_string($item) || $item instanceof WireHttpRequestSpec) {
				$this->enqueue($item);
				$keys[] = $key;
			}
		}

		$this->setConcurrency($concurrency ?? $this->maxConcurrent);

		try {
			$results = $this->execute();

			$out = [];
			foreach ($results as $index => $res) {
				$key = $keys[$index] ?? $index;
				if (!$res->success) {
					$out[$key] = false;
				} elseif ($res->toFile !== null) {
					$out[$key] = true;
				} else {
					$out[$key] = is_string($res->body) ? $res->body : '';
				}
			}
		} finally {
			$this->queue = $savedQueue;
			$this->maxConcurrent  = $savedConcurrency;
		}
		return $out;
	}

	/**
	 * Natively mimics WireHttp::getJSON(), concurrently.
	 *
	 * @param array<int|string, string|WireHttpRequestSpec> $requests
	 * @param int|null $concurrency Overrides class maxConcurrent for this call only.
	 * @return array<int|string, array<mixed>|false> Returns mapped JSON arrays or false
	 */
	public function getJSONMulti(array $requests, ?int $concurrency = null): array {
		$bodies = $this->getMulti($requests, $concurrency);
		$out = [];

		foreach ($bodies as $key => $body) {
			if (!is_string($body)) {
				$out[$key] = false;
			} else {
				$decoded = json_decode($body, true);
				$out[$key] = json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : false;
			}
		}
		return $out;
	}

	/**
	 * Core execution loop returning rich result objects.
	 *
	 * @return list<WireHttpRequestResult>
	 */
	public function execute(): array {
		$this->results = [];
		$this->resultsBySeq = [];
		$this->resetResponse();
		if (empty($this->queue)) return [];

		$mh = curl_multi_init();

		/** @var array<int, array{handle: \CurlHandle, spec: WireHttpRequestSpec, fileHandle: resource|null, seq: int}> $active Keyed by spl_object_id() of the handle, not a cast — CurlHandle objects have no numeric conversion. */
		$active = [];
		$pool   = $this->queue;
		$this->queue = [];
		$nextSeq = 0;

		$ramp = min($this->maxConcurrent, count($pool));
		for ($i = 0; $i < $ramp; $i++) {
			/** @var WireHttpRequestSpec $nextSpec */
			$nextSpec = array_shift($pool);
			$seq = $nextSeq++;
			$row = $this->spawn($mh, $nextSpec, $seq);
			if ($row !== null) {
				$active[spl_object_id($row['handle'])] = $row;
			} else {
				$this->resultsBySeq[$seq] ??= $this->buildSpawnFailureResult($nextSpec);
			}
		}

		$running = 0;
		do {
			curl_multi_exec($mh, $running);

			while (($info = curl_multi_info_read($mh)) !== false) {
				if (isset($info['handle']) && $info['handle'] instanceof \CurlHandle) {
					$ch = $info['handle'];
					$id = spl_object_id($ch);
					if (isset($active[$id])) {
						$this->resultsBySeq[$active[$id]['seq']] ??= $this->finalize($active[$id]);
						curl_multi_remove_handle($mh, $ch);
						if (PHP_VERSION_ID < 80400) {
							/** @phpstan-ignore-next-line function.deprecated */
							curl_close($ch);
						}
						if (is_resource($active[$id]['fileHandle'])) {
							fclose($active[$id]['fileHandle']);
						}
						unset($active[$id]);
					}
				}
				if (!empty($pool)) {
					/** @var WireHttpRequestSpec $nextSpec */
					$nextSpec = array_shift($pool);
					$seq = $nextSeq++;
					$row = $this->spawn($mh, $nextSpec, $seq);
					if ($row !== null) {
						$active[spl_object_id($row['handle'])] = $row;
					} else {
						$this->resultsBySeq[$seq] ??= $this->buildSpawnFailureResult($nextSpec);
					}
				}
			}

			if ($running > 0) {
				$selected = curl_multi_select($mh, 1.0);
				if ($selected === -1) {
					usleep(10000);
				}
				if ($this->debug) {
					$this->log(sprintf('[WireHttpMulti] active=%d, queued=%d', count($active), count($pool)));
				}
			}
		} while ($running > 0 || !empty($active));

		// Defensive: ensures no handle leaks on unexpected cURL state (e.g. handles
		// that completed but weren't picked up by curl_multi_info_read).
		/** @var array<int, array{handle: \CurlHandle, spec: WireHttpRequestSpec, fileHandle: resource|null, seq: int}> $active Keyed by spl_object_id() of the handle, not a cast — CurlHandle objects have no numeric conversion. */
		foreach ($active as $row) {
			$this->resultsBySeq[$row['seq']] ??= $this->finalize($row);
			curl_multi_remove_handle($mh, $row['handle']);
			if (PHP_VERSION_ID < 80400) {
				/** @phpstan-ignore-next-line function.deprecated */
				curl_close($row['handle']);
			}
			if (is_resource($row['fileHandle'])) {
				fclose($row['fileHandle']);
			}
		}

		// curl_multi_close() deprecated in PHP 8.4; CurlMultiHandle GC'd automatically.
		// Kept for PHP < 8.4 compatibility.
		if(PHP_VERSION_ID < 80400) {
			curl_multi_close($mh);
		}

		ksort($this->resultsBySeq);

		$errorMessages = [];
		foreach($this->resultsBySeq as $result) {
			if($result->success) continue;

			if($result->curlError !== null) {
				$errorMessages[] = $result->curlError;
			} elseif($result->httpCode >= 400) {
				/** @var array<int, string> $httpCodes */
				$httpCodes = $this->httpCodes;
				$errorMessages[] = ($httpCodes[$result->httpCode] ?? "HTTP {$result->httpCode}");
			}

			if($result->httpCode > 0) {
				$this->setHttpCode($result->httpCode); // last failed code wins, mirrors WireHttp
			}
		}

		// Limit error detail to first 3, summarize the rest
		if(count($errorMessages) > 3) {
			$extra = count($errorMessages) - 3;
			$errorMessages = array_slice($errorMessages, 0, 3);
			$errorMessages[] = sprintf('… and %d more request(s) failed', $extra);
		}
		$this->error = array_merge($this->error, $errorMessages);

		$this->results = array_values($this->resultsBySeq);

		return $this->results;
	}

	/**
	 * Creates a cURL handle and returns the active row array.
	 *
	 * @param \CurlMultiHandle $mh
	 * @param WireHttpRequestSpec $spec
	 * @return array{handle: \CurlHandle, spec: WireHttpRequestSpec, fileHandle: resource|null, seq: int}|null
	 */
	protected function spawn(\CurlMultiHandle $mh, WireHttpRequestSpec $spec, int $seq): ?array {
		$ch = curl_init();
		if (!$ch instanceof \CurlHandle) return null;

		$fileHandle = null;

		$timeout = (int) $this->getTimeout();

		/** @var array<int, mixed> $opts */
		$opts = [
			CURLOPT_URL            => $spec->url,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS      => 5,
			CURLOPT_CONNECTTIMEOUT => min(5, $timeout),
			CURLOPT_TIMEOUT        => $timeout,
			CURLOPT_SSL_VERIFYPEER => $this->sslVerify,
			CURLOPT_SSL_VERIFYHOST => $this->sslVerify ? 2 : 0,
		];
		$opts[CURLOPT_USERAGENT] = $this->getUserAgent();

		$headerLines = [];
		if (is_array($this->headers)) {
			/** @var string $value */
			foreach ($this->headers as $name => $value) {
				if($name === 'user-agent') continue;
				$headerLines[] = trim((string)$name) . ': ' . trim($value);
			}
		}

		if ($spec->method === 'POST') {
			$opts[CURLOPT_POST] = true;
			$hasContentType = array_key_exists('content-type', $this->getHeaders());
			if ($spec->body !== null) {
				$opts[CURLOPT_POSTFIELDS] = $spec->body;
				if(!$hasContentType) $headerLines[] = 'Content-Type: application/json';
			} elseif (is_array($spec->post)) {
				$opts[CURLOPT_POSTFIELDS] = $spec->post;
			}
		} elseif ($spec->method !== 'GET') {
			$opts[CURLOPT_CUSTOMREQUEST] = strtoupper($spec->method);
		}

		if ($spec->toFile !== null) {
			$openedFile = @fopen($spec->toFile, 'w');
			if (is_resource($openedFile)) {
				$fileHandle = $openedFile;
				$opts[CURLOPT_FILE] = $fileHandle;
				$opts[CURLOPT_RETURNTRANSFER] = false;
			} else {
				// Fail fast: Record failure immediately, don't hit the network
				$this->resultsBySeq[$seq] = new WireHttpRequestResult(
					url:           $spec->url,
					method:        $spec->method,
					success:       false,
					httpCode:      0,
					curlErrorCode: 23, // CURLE_WRITE_ERROR
					curlError:     "Cannot open for writing: {$spec->toFile}",
					body:          null,
					headers:       null,
					toFile:        $spec->toFile,
					specOptions:   is_array($spec->options) ? $spec->options : []
				);
				return null;
			}
		} else {
			$opts[CURLOPT_RETURNTRANSFER] = true;
			$opts[CURLOPT_HEADER] = true;
		}

		// Allowed escape hatch: callers can override any cURL option via $spec->options,
		// including internal ones like CURLOPT_RETURNTRANSFER. Use with care.
		if (!empty($spec->options) && is_array($spec->options)) {
			$opts = (array) array_replace($opts, $spec->options);
		}

		if (!empty($headerLines)) {
			$opts[CURLOPT_HTTPHEADER] = $headerLines;
		}

		curl_setopt_array($ch, $opts);
		curl_multi_add_handle($mh, $ch);

		return [
			'handle'     => $ch,
			'spec'       => $spec,
			'fileHandle' => $fileHandle,
			'seq'        => $seq,
		];
	}

	protected function buildSpawnFailureResult(WireHttpRequestSpec $spec): WireHttpRequestResult {
		return new WireHttpRequestResult(
			url:           $spec->url,
			method:        $spec->method,
			success:       false,
			httpCode:      0,
			curlErrorCode: 2,
			curlError:     'curl_init() failed',
			body:          null,
			headers:       null,
			toFile:        $spec->toFile,
			specOptions:   is_array($spec->options) ? $spec->options : []
		);
	}

	/**
	 * @param array{handle: \CurlHandle, spec: WireHttpRequestSpec, fileHandle: resource|null, seq: int} $row
	 * @return WireHttpRequestResult
	 */
	protected function finalize(array $row): WireHttpRequestResult {
		$ch   = $row['handle'];
		$spec = $row['spec'];

		$httpCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curlErrNo = curl_errno($ch);
		$curlErr   = curl_error($ch);

		$body    = null;
		$headers = [];
		$success = ($curlErrNo === 0) && ($httpCode >= 200) && ($httpCode < 300);

		if (is_resource($row['fileHandle'])) {
			if (!$success && $spec->toFile !== null && file_exists($spec->toFile)) {
				@unlink($spec->toFile);
			}
		} else {
			$response = curl_multi_getcontent($ch);
			$responseStr = is_string($response) ? $response : '';
			$headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);

			if ($headerSize > 0 && strlen($responseStr) >= $headerSize) {
				$headerBlock = substr($responseStr, 0, $headerSize);
				$headers     = $this->parseHeaders($headerBlock);
				$body        = substr($responseStr, $headerSize);
			} else {
				$body = $responseStr;
			}
		}

		return new WireHttpRequestResult(
			url:           $spec->url,
			method:        $spec->method,
			success:       $success,
			httpCode:      $httpCode,
			curlErrorCode: $curlErrNo,
			curlError:     $curlErr !== '' ? $curlErr : null,
			body:          $body,
			headers:       !empty($headers) ? $headers : null,
			toFile:        $spec->toFile,
			specOptions:   is_array($spec->options) ? $spec->options : []
		);
	}

	/**
	 * @return array<string, string>
	 */
	protected function parseHeaders(string $raw): array {
		$lines = preg_split('/\r?\n/', trim($raw));
		if (!is_array($lines)) return [];

		$headers = [];
		foreach ($lines as $line) {
			if (strpos($line, ':') !== false) {
				$parts = explode(':', $line, 2);
				if (count($parts) === 2) {
					$headers[trim(strtolower($parts[0]))] = trim($parts[1]);
				}
			}
		}
		return $headers;
	}
}
