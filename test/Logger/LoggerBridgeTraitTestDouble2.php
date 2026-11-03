<?php declare(strict_types=1);

namespace Base3\Test\Logger;

use Base3\Logger\Api\ILogger;
use PHPUnit\Framework\TestCase;

final class LoggerBridgeTraitTestDouble2 {
			use \Base3\Logger\LoggerBridgeTrait;

			private array $calls;

			public function __construct(array &$calls) {
				$this->calls = &$calls;
			}

			public function logLevel(string $level, string|\Stringable $message, array $context = []): void {
				$this->calls[] = [
					'level' => $level,
					'message' => (string)$message,
					'context' => $context
				];
			}
		}
