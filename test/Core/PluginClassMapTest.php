<?php declare(strict_types=1);

namespace Base3Test\Core;

use Base3\Api\IContainer;
use Base3\Api\IPlugin;
use Base3\Core\PluginClassMap;
use PHPUnit\Framework\TestCase;

final class PluginClassMapTest extends TestCase {

	protected function setUp(): void {
		if (!defined('DIR_SRC')) {
			$guess = rtrim((string)realpath(__DIR__ . '/../../src'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
			define('DIR_SRC', is_dir($guess) ? $guess : sys_get_temp_dir() . DIRECTORY_SEPARATOR);
		}

		if (!defined('DIR_PLUGIN')) {
			$guess = rtrim((string)realpath(__DIR__ . '/../../plugin'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
			define('DIR_PLUGIN', is_dir($guess) ? $guess : sys_get_temp_dir() . DIRECTORY_SEPARATOR);
		}

		if (!defined('DIR_TMP')) {
			$tmp = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'base3-tests' . DIRECTORY_SEPARATOR;
			if (!is_dir($tmp)) @mkdir($tmp, 0777, true);
			define('DIR_TMP', $tmp);
		}
	}

	private function makeContainer(): IContainer {
		return new PluginClassMapTestDouble1();
	}

	public function testGetScanTargetsIncludesSrcAndPluginDirs(): void {
		$cm = new PluginClassMapTestDouble2($this->makeContainer());

		$targets = $cm->exposeTargets();

		$this->assertCount(2, $targets);

		$this->assertSame(DIR_SRC, $targets[0]['basedir']);
		$this->assertSame('', $targets[0]['subdir']);
		$this->assertSame('Base3', $targets[0]['subns']);

		$this->assertSame(DIR_PLUGIN, $targets[1]['basedir']);
		$this->assertSame('src', $targets[1]['subdir']);
		$this->assertSame('', $targets[1]['subns']);
	}

	public function testGetPluginsReturnsAppsWithIPluginInterfaceInMap(): void {
		$cm = new PluginClassMapTestDouble3($this->makeContainer());

		$plugins = $cm->getPlugins();

		$this->assertSame(['PluginA'], $plugins);
	}
}

require_once __DIR__ . '/PluginClassMapTestDouble1.php';

require_once __DIR__ . '/PluginClassMapTestDouble2.php';

require_once __DIR__ . '/PluginClassMapTestDouble3.php';
