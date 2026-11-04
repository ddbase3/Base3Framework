<?php declare(strict_types=1);

namespace Base3\Test\Logger;

use Base3\Logger\AbstractLogger;

final class RecordingLoggerFixture extends AbstractLogger {

	public array $records = [];
	public function logLevel(string $level, string|\Stringable $message, array $context = []): void {
		$this->records[] = [$level,(string)$message,$context];
	}
}
