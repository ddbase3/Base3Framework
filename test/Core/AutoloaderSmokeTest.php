<?php declare(strict_types=1);

namespace Base3\Test\Core;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use Base3\Core\Autoloader;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class AutoloaderSmokeTest extends TestCase {

	public static function setUpBeforeClass(): void {
		$frameworkRoot = dirname(__DIR__, 2);
		$pluginRoot = is_dir($frameworkRoot . '/plugin') ? $frameworkRoot . '/plugin' : dirname($frameworkRoot);
		foreach ([
			'DIR_SRC' => $frameworkRoot . '/src/',
			'DIR_TEST' => $frameworkRoot . '/test/',
			'DIR_PLUGIN' => rtrim($pluginRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
		] as $name => $value) {
			if (!defined($name)) define($name, $value);
		}
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
