<?php declare(strict_types=1);

namespace Base3\Test\Xrm;

final class RecordingXrmLogFixture {

	public array $messages = [];

	public function info(string $message, array $context = []): void {
		$this->messages[] = [$message, $context];
	}
}
