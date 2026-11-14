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

use Base3\CoreFoundation\Api\IContainer;
use Base3\CoreFoundation\Api\IPlugin;
use Base3\Core\PluginClassMap;
use PHPUnit\Framework\TestCase;

final class PluginClassMapTest extends TestCase {

	protected function setUp(): void {

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

	public function testGetScanTargetsUsesDeclaredModuleNamespaces(): void {
		$cm = new PluginClassMapTestDouble2($this->makeContainer());
		$targets = $cm->exposeTargets();
		$found = [];
		foreach ($targets as $target) {
			$found[$target['module']] = $target;
			$this->assertSame('src', $target['subdir']);
		}
		$this->assertArrayHasKey('Core', $found);
		$this->assertArrayHasKey('CoreFoundation', $found);
		$this->assertArrayHasKey('AssistantFoundation', $found);
		$this->assertArrayHasKey('AssistantRuntime', $found);
		$this->assertSame('Base3\\AssistantRuntime', $found['AssistantRuntime']['subns']);
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
