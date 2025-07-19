<?php declare(strict_types=1);

namespace Base3\Api;

interface IAiChatModel {

	/**
	 * Sends a message list to the assistant model and returns its response.
	 *
	 * @param array $messages List of messages:
	 *  [['role' => 'user', 'content' => 'Hi'], ...]
	 * @return string Assistant reply
	 */
	public function chat(array $messages): string;

	/**
	 * Returns the raw model response (full object).
	 *
	 * @param array $messages
	 * @return mixed Raw result from API
	 */
	public function raw(array $messages): mixed;

	/**
	 * Sets options like model, temperature, etc.
	 *
	 * @param array $options
	 * @return void
	 */
	public function setOptions(array $options): void;

	/**
	 * Optional: get options for debugging/logging.
	 */
	public function getOptions(): array;
}

