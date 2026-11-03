<?php declare(strict_types=1);

namespace Base3\Test\Logger;

use Base3\Logger\Api\ILogger;
use PHPUnit\Framework\TestCase;

final class LoggerBridgeTraitTestDouble3 {
			use \Base3\Logger\LoggerBridgeTrait;

			public function logLevel(string $level, string|\Stringable $message, array $context = []): void {
				// no-op
			}
		}
