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

namespace Base3\Test\ConfigValue\Mode;

use PHPUnit\Framework\TestCase;

final class FileConfigValueModeResolverTest extends TestCase {

	public function testTrimModesAndSchema(): void {
		$r = new \Base3\Core\ConfigValue\Mode\FileConfigValueModeResolver();
		$file = tempnam(sys_get_temp_dir(), 'base3-value-');
		try {
			file_put_contents($file, "  secret \n");
			$this->assertTrue($r->supports(['mode' => 'file', 'path' => $file]));
			$this->assertSame('secret', $r->resolve(['path' => $file]));
			$this->assertSame("  secret \n", $r->resolve(['path' => $file, 'trim' => false]));
			$this->assertSame("  secret \n", $r->resolve(['path' => $file, 'trim' => 'no']));
			$this->assertSame('secret', $r->resolve(['path' => $file, 'trim' => 'on']));
			$this->assertSame('secret', $r->resolve(['path' => $file, 'trim' => 1]));
			$this->assertSame(['path'], $r->getSchema()['required']);
		} finally {
			unlink($file);
		}
	}

	public function testRejectsUnreadableFile(): void {
		$r = new \Base3\Core\ConfigValue\Mode\FileConfigValueModeResolver();
		$this->assertFalse($r->supports(['mode' => 'env', 'path' => '/tmp/secret']));
		$this->expectException(\RuntimeException::class);
		$r->resolve(['path' => sys_get_temp_dir().'/base3-file-that-does-not-exist-'.getmypid()]);
	}

	public function testRejectsEmptyPath(): void {
		$this->expectException(\RuntimeException::class);
		(new \Base3\Core\ConfigValue\Mode\FileConfigValueModeResolver())->resolve(['path' => '']);
	}

	public function testRejectsNonArrayDefinition(): void {
		$this->expectException(\RuntimeException::class);
		(new \Base3\Core\ConfigValue\Mode\FileConfigValueModeResolver())->resolve(null);
	}

}
