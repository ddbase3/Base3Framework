<?php declare(strict_types=1);

namespace Base3\Test\Core;

use Base3\Core\Autoloader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class AutoloaderUnrelatedClassesTest extends TestCase {

	#[DataProvider('debugLevels')]
	public function testMissingClassesOutsideRegisteredNamespacesDoNotWriteToOutput(string $debug): void {
		$originalDebug = getenv('DEBUG');
		putenv('DEBUG=' . $debug);

		try {
			$autoload = new ReflectionMethod(Autoloader::class, 'autoload');

			ob_start();
			try {
				$autoload->invoke(null, 'ExternalVendorWithoutNamespaceMapping\\MissingAutoloadRegressionClass');
			}
			finally {
				$output = ob_get_clean();
			}

			self::assertSame('', $output);
		}
		finally {
			if ($originalDebug === false) {
				putenv('DEBUG');
			}
			else {
				putenv('DEBUG=' . $originalDebug);
			}
		}
	}

	/** @return array<string, array{string}> */
	public static function debugLevels(): array {
		return [
			'general debugging' => ['1'],
			'namespace tracing' => ['2']
		];
	}
}
