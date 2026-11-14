<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of BASE3 Framework.
 *
 * BASE3 Framework is a lightweight, modular PHP framework for scalable
 * and maintainable web applications. Built for extensibility,
 * performance, and modern development, it can run standalone or
 * integrate as a subsystem within a host system.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/Base3Framework
 **********************************************************************/

namespace Base3\Test\Core;

use Base3\Bootstrap\Autoloader;
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
