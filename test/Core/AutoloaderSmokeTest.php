<?php declare(strict_types=1);

namespace Base3\Test\Core;

use PHPUnit\Framework\TestCase;
use Base3\Core\Autoloader;

class AutoloaderSmokeTest extends TestCase {

	public static function setUpBeforeClass(): void {
		// Register autoloader
		Autoloader::register();

		// Add plugins dynamically (explicitly)
		self::addPlugins();
	}

	/**
	 * Add plugins dynamically to the autoloader.
	 */
	private static function addPlugins(): void {
		foreach (glob(DIR_PLUGIN . '*', GLOB_ONLYDIR) as $pluginPath) {
			$pluginName = basename($pluginPath);
			$srcPath = realpath($pluginPath . '/src');
			$testPath = realpath($pluginPath . '/test');

			if ($srcPath !== false) {
				Autoloader::registerPlugin($pluginName . '\\', $srcPath . '/');
			}

			if ($testPath !== false) {
				Autoloader::registerPlugin($pluginName . '\\Test\\', $testPath . '/');
			}
		}
	}

	public function testAutoloaderLoadsDummyClass(): void {
		$dummy = new \Base3\Test\Dummy\DummyClass();
		$this->assertSame('Hello from DummyClass!', $dummy->sayHello());
	}
}
