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
use Base3\Core\ComposerClassMap;
use PHPUnit\Framework\TestCase;

final class ComposerClassMapTest extends TestCase {

	private string $autoloadClassmapFile;
	private string $tmpClassDir;

	protected function setUp(): void {
		if (!defined('DIR_TMP')) {
			$tmp = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'base3-tests' . DIRECTORY_SEPARATOR;
			if (!is_dir($tmp)) @mkdir($tmp, 0777, true);
			define('DIR_TMP', $tmp);
		}

		$this->tmpClassDir = rtrim(DIR_TMP, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'composerclassmap_' . uniqid('', true);
		@mkdir($this->tmpClassDir, 0777, true);

		// Determine the EXACT vendor path used by ComposerClassMap at runtime:
		$ref = new \ReflectionClass(ComposerClassMap::class);
		$srcDir = dirname($ref->getFileName());               // .../src/Core
		$root = dirname($srcDir, 3);                          // same as dirname(__DIR__, 3) inside the class
		$this->autoloadClassmapFile = $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'autoload_classmap.php';

		@mkdir(dirname($this->autoloadClassmapFile), 0777, true);
	}

	protected function tearDown(): void {
		$this->rmDir($this->tmpClassDir);

		if (is_file($this->autoloadClassmapFile)) {
			$contents = (string)@file_get_contents($this->autoloadClassmapFile);
			if (str_contains($contents, 'COMPOSERCLASSMAP_TEST_MARKER')) {
				@unlink($this->autoloadClassmapFile);
			}
		}

		// try remove empty vendor/composer and vendor (only if empty)
		$this->tryRmdirIfEmpty(dirname($this->autoloadClassmapFile));
		$this->tryRmdirIfEmpty(dirname(dirname($this->autoloadClassmapFile)));
	}

	private function tryRmdirIfEmpty(string $dir): void {
		if (!is_dir($dir)) return;
		$entries = array_values(array_diff(scandir($dir) ?: [], ['.', '..']));
		if (count($entries) === 0) @rmdir($dir);
	}

	private function rmDir(string $dir): void {
		if (!is_dir($dir)) return;
		$items = scandir($dir);
		if (!is_array($items)) return;
		foreach ($items as $it) {
			if ($it === '.' || $it === '..') continue;
			$path = $dir . DIRECTORY_SEPARATOR . $it;
			if (is_dir($path)) $this->rmDir($path);
			else @unlink($path);
		}
		@rmdir($dir);
	}

	private function makeContainer(): IContainer {
		return new ComposerClassMapTestDouble1();
	}

	private function writePhp(string $path, string $code): void {
		$dir = dirname($path);
		if (!is_dir($dir)) @mkdir($dir, 0777, true);
		file_put_contents($path, $code);
	}

	private function exportPath(string $path): string {
		return var_export($path, true);
	}

	public function testGetScanTargetsIsEmpty(): void {
		$cm = new ComposerClassMapTestDouble2($this->makeContainer());

		$this->assertSame([], $cm->exposeTargets());
	}

	public function testGenerateFromComposerClassMapBuildsInterfaceAndNameMaps(): void {
		$this->writePhp($this->tmpClassDir . DIRECTORY_SEPARATOR . 'IFoo.php', <<<'PHP'
<?php declare(strict_types=1);
namespace Base3\MyApp;
interface IFoo {}
PHP);
		$goodFile = $this->tmpClassDir . DIRECTORY_SEPARATOR . 'Good.php';
		$this->writePhp($goodFile, <<<'PHP'
<?php declare(strict_types=1);

namespace Base3\MyApp;

use Base3\CoreFoundation\Api\IBase;
require_once __DIR__ . '/IFoo.php';

class Good implements IBase, IFoo {
	public static function getName(): string { return 'good'; }
}
PHP);

		$abstractFile = $this->tmpClassDir . DIRECTORY_SEPARATOR . 'AbstractThing.php';
		$this->writePhp($abstractFile, <<<'PHP'
<?php declare(strict_types=1);

namespace Base3\MyApp;

abstract class AbstractThing {}
PHP);

		$throwFile = $this->tmpClassDir . DIRECTORY_SEPARATOR . 'ThrowName.php';
		$this->writePhp($throwFile, <<<'PHP'
<?php declare(strict_types=1);

namespace Base3\OtherApp;

use Base3\CoreFoundation\Api\IBase;

class ThrowName implements IBase {
	public static function getName(): string { throw new \RuntimeException('nope'); }
}
PHP);

		$nonNamespacedFile = $this->tmpClassDir . DIRECTORY_SEPARATOR . 'NoNsClass.php';
		$this->writePhp($nonNamespacedFile, <<<'PHP'
<?php declare(strict_types=1);

class NoNsClass {}
PHP);

		$missingFile = $this->tmpClassDir . DIRECTORY_SEPARATOR . 'Missing.php';
		$this->writePhp($missingFile, <<<'PHP'
<?php declare(strict_types=1);

// defines nothing on purpose
PHP);

		// Write vendor/composer/autoload_classmap.php at the EXACT location ComposerClassMap requires
		$this->writePhp($this->autoloadClassmapFile, <<<PHP
<?php
// COMPOSERCLASSMAP_TEST_MARKER
return [
	'Base3\\\\MyApp\\\\Good' => {$this->exportPath($goodFile)},
	'Base3\\\\MyApp\\\\AbstractThing' => {$this->exportPath($abstractFile)},
	'Base3\\\\OtherApp\\\\ThrowName' => {$this->exportPath($throwFile)},
	'NoNsClass' => {$this->exportPath($nonNamespacedFile)},
	'Base3\\\\MyApp\\\\Missing' => {$this->exportPath($missingFile)},
];
PHP);

		$cm = new ComposerClassMapTestDouble3($this->makeContainer());

		$map = $cm->exposeMap();

		$this->assertArrayHasKey('MyApp', $map);
		$this->assertArrayHasKey('OtherApp', $map);

		$this->assertArrayHasKey('interface', $map['MyApp']);
		$this->assertArrayHasKey('name', $map['MyApp']);

		$this->assertArrayHasKey('Base3\\MyApp\\IFoo', $map['MyApp']['interface']);
		$this->assertContains('Base3\\MyApp\\Good', $map['MyApp']['interface']['Base3\\MyApp\\IFoo']);

		$this->assertSame('Base3\\MyApp\\Good', $map['MyApp']['name']['good']);
		$this->assertTrue(!isset($map['OtherApp']['name']));

		// Skips
		foreach (($map['MyApp']['interface'] ?? []) as $iface => $classes) {
			$this->assertNotContains('Base3\\MyApp\\AbstractThing', $classes);
			$this->assertNotContains('Base3\\MyApp\\Missing', $classes);
		}

		$this->assertFileExists($cm->getFile());
	}
}

require_once __DIR__ . '/ComposerClassMapTestDouble1.php';

require_once __DIR__ . '/ComposerClassMapTestDouble2.php';

require_once __DIR__ . '/ComposerClassMapTestDouble3.php';
