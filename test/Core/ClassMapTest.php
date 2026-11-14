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

use Base3\Core\ClassMap;
use Base3\CoreFoundation\Api\IContainer;
use PHPUnit\Framework\TestCase;

final class ClassMapTest extends TestCase {

	protected function setUp(): void {

		if (!defined('DIR_TMP')) {
			$tmp = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'base3-tests' . DIRECTORY_SEPARATOR;
			if (!is_dir($tmp)) @mkdir($tmp, 0777, true);
			define('DIR_TMP', $tmp);
		}
	}

	private function makeContainer(): IContainer {
		return new ClassMapTestDouble1();
	}

	public function testGetScanTargetsUsesDeclaredCoreModules(): void {
		$cm = new ClassMapTestDouble2($this->makeContainer());
		$targets = $cm->exposeTargets();
		$this->assertCount(2, $targets);
		$found = [];
		foreach ($targets as $target) {
			$found[$target['module']] = $target;
			$this->assertSame('src', $target['subdir']);
		}
		$this->assertSame('Base3\\Core', $found['Core']['subns']);
		$this->assertSame('Base3\\CoreFoundation', $found['CoreFoundation']['subns']);
	}
}

require_once __DIR__ . '/ClassMapTestDouble1.php';

require_once __DIR__ . '/ClassMapTestDouble2.php';
