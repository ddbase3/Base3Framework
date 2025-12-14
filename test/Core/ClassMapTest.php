<?php declare(strict_types=1);

namespace Base3Test\Core;

use Base3\Core\ClassMap;
use Base3\Api\IContainer;
use PHPUnit\Framework\TestCase;

final class ClassMapTest extends TestCase {

	protected function setUp(): void {
		if (!defined('DIR_SRC')) {
			// In your project bootstrap, DIR_SRC is usually defined.
			// For safety in isolated runs, define it to the real src directory if possible.
			$guess = rtrim((string)realpath(__DIR__ . '/../../src'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
			define('DIR_SRC', is_dir($guess) ? $guess : sys_get_temp_dir() . DIRECTORY_SEPARATOR);
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

	public function testGetScanTargetsConfiguredForSrcBase3Namespace(): void {
		$cm = new class($this->makeContainer()) extends ClassMap {
			public function exposeTargets(): array {
				return $this->getScanTargets();
			}
		};

		$targets = $cm->exposeTargets();

		$this->assertCount(1, $targets);
		$this->assertSame(DIR_SRC, $targets[0]['basedir']);
		$this->assertSame('', $targets[0]['subdir']);
		$this->assertSame('Base3', $targets[0]['subns']);
	}
}
