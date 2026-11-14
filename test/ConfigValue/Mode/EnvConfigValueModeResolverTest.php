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

final class EnvConfigValueModeResolverTest extends TestCase {

	public function testResolvesPresentAbsentAndLegacyVariables(): void {
		$r = new \Base3\Core\ConfigValue\Mode\EnvConfigValueModeResolver();
		$name = 'BASE3_UNITTEST_ENV_'.getmypid();
		try {
			putenv($name.'=from-process');
			$this->assertTrue($r->supports(['mode' => 'env', 'name' => $name]));
			$this->assertSame('from-process', $r->resolve(['mode' => 'env', 'name' => $name]));
			$this->assertSame('from-process', $r->resolve(['mode' => 'env', 'value' => $name]));
			putenv($name);
			$this->assertNull($r->resolve(['name' => $name]));
		} finally {
			putenv($name);
		}
	}

	public function testRejectsMalformedDefinition(): void {
		$r = new \Base3\Core\ConfigValue\Mode\EnvConfigValueModeResolver();
		$this->assertFalse($r->supports('raw'));
		$this->assertFalse($r->supports(['mode' => 'file', 'name' => 'X']));
		$this->assertSame(['name'], $r->getSchema()['required']);
		$this->expectException(\RuntimeException::class);
		$r->resolve(['name' => '']);
	}

	public function testRejectsNonArrayDefinition(): void {
		$this->expectException(\RuntimeException::class);
		(new \Base3\Core\ConfigValue\Mode\EnvConfigValueModeResolver())->resolve('value');
	}

}
