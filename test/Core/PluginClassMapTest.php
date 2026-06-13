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
		return new class implements IContainer {
			public function getServiceList(): array { return []; }
			public function set(string $name, $classDefinition, $flags = 0): IContainer { return $this; }
			public function remove(string $name) {}
			public function has(string $name): bool { return false; }
			public function get(string $name) { return null; }
		};
	}

	public function testGetScanTargetsIncludesSrcAndPluginDirs(): void {
		$cm = new class($this->makeContainer()) extends PluginClassMap {
			public function exposeTargets(): array {
				return $this->getScanTargets();
			}
		};

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
		$cm = new class($this->makeContainer()) extends PluginClassMap {

			private array $fakeMap;

			public function __construct(IContainer $container) {
				parent::__construct($container);
				$this->fakeMap = [
					'PluginA' => [
						'interface' => [
							IPlugin::class => ['PluginA\\MyPlugin'],
						],
					],
					'PluginB' => [
						'interface' => [
							'SomeOtherInterface' => ['PluginB\\X'],
						],
					],
					'PluginC' => [
						// missing interface key -> ignored
					],
				];
			}

			protected function getScanTargets(): array {
				return [];
			}

			protected function &getMap(): array {
				return $this->fakeMap;
			}
		};

		$plugins = $cm->getPlugins();

		$this->assertSame(['PluginA'], $plugins);
	}
}
