<?php declare(strict_types=1);

namespace Base3\Api;

use Base3\Api\IBase;

/**
 * Interface for continuous streaming outputs.
 * Designed for server-sent events or similar push mechanisms.
 */
interface IStream extends IBase {

	/**
	 * Starts the streaming session (headers, buffering off, etc.).
	 */
	public function start(): void;

	/**
	 * Sends one logical event to the client.
	 *
	 * @param array<string, mixed> $event
	 */
	public function sendEvent(array $event): void;

	/**
	 * Sends a heartbeat to keep the stream alive.
	 */
	public function heartbeat(): void;

	/**
	 * Closes the stream.
	 */
	public function finish(): void;

	/**
	 * Main execution entrypoint.
	 * Does not return a string; the stream is written directly.
	 */
	public function stream(): void;
}
