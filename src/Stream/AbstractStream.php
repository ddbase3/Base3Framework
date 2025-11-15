<?php declare(strict_types=1);

namespace Base3\Stream;

use Base3\Api\IStream;

abstract class AbstractStream implements IStream {

	protected int $heartbeatIntervalMs = 1500;
	protected int $lastHeartbeat = 0;

	public function start(): void {
		// SSE headers
		header('Content-Type: text/event-stream');
		header('Cache-Control: no-cache');
		header('Connection: keep-alive');
		header('X-Accel-Buffering: no');  // <- CRITICAL for FastCGI

		// Disable PHP buffering
		@ini_set('output_buffering', 'off');
		@ini_set('zlib.output_compression', '0');

		// Stop all output buffers
		while (ob_get_level() > 0) {
			@ob_end_clean();
		}

		// Flush headers
		echo str_pad('', 4096); // <- CRITICAL FastCGI flush trick
		flush();
	}

	public function sendEvent(array $event): void {
		echo "event: message\n";
		echo "data: " . json_encode($event, JSON_UNESCAPED_SLASHES) . "\n\n";

		// Force FastCGI flush
		echo str_pad('', 4096) . "\n";
		if (function_exists('ob_flush')) @ob_flush();
		flush();
	}

	public function heartbeat(): void {
		$now = (int)(microtime(true) * 1000);
		if ($now - $this->lastHeartbeat < $this->heartbeatIntervalMs) {
			return;
		}

		echo ": hb\n\n";

		echo str_pad('', 4096) . "\n";
		if (function_exists('ob_flush')) @ob_flush();
		flush();

		$this->lastHeartbeat = $now;
	}

	public function finish(): void {
		echo "event: done\n";
		echo "data: {}\n\n";

		echo str_pad('', 4096) . "\n";
		if (function_exists('ob_flush')) @ob_flush();
		flush();

		// For FastCGI — closes request properly
		if (function_exists('fastcgi_finish_request')) {
			fastcgi_finish_request();
		}
	}

	abstract public function stream(): void;

	public static function getName(): string {
		return 'abstractstream';
	}
}
