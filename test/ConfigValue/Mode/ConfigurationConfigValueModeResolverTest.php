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

final class ConfigurationConfigValueModeResolverTest extends TestCase {

	public function testCanonicalAndLegacyModesPreserveExplicitNullValues(): void {
		$config = new \Base3\Core\Configuration\ArrayConfiguration(['app' => ['secret' => null, 'key' => 'abc']]);
		$r = new \Base3\Core\ConfigValue\Mode\ConfigurationConfigValueModeResolver($config);
		$this->assertSame('configuration', $r->getMode());
		$this->assertTrue($r->supports(['mode' => 'configuration', 'group' => 'app', 'key' => 'secret']));
		$this->assertTrue($r->supports(['mode' => 'config', 'section' => 'app', 'key' => 'key']));
		$this->assertNull($r->resolve(['group' => 'app', 'key' => 'secret']));
		$this->assertSame('abc', $r->resolve(['section' => 'app', 'key' => 'key']));
		$this->assertSame(['group', 'key'], $r->getSchema()['required']);
	}

	public function testRejectsMissingGroup(): void {
		$r = new \Base3\Core\ConfigValue\Mode\ConfigurationConfigValueModeResolver(new \Base3\Core\Configuration\ArrayConfiguration());
		$this->expectException(\RuntimeException::class);
		$r->resolve(['group' => 'notfound', 'key' => 'key']);
	}

	public function testRejectsMissingKey(): void {
		$r = new \Base3\Core\ConfigValue\Mode\ConfigurationConfigValueModeResolver(new \Base3\Core\Configuration\ArrayConfiguration(['g' => []]));
		$this->expectException(\RuntimeException::class);
		$r->resolve(['group' => 'g', 'key' => 'missing']);
	}

	public function testRejectsMalformedGroupAndKey(): void {
		$r = new \Base3\Core\ConfigValue\Mode\ConfigurationConfigValueModeResolver(new \Base3\Core\Configuration\ArrayConfiguration());
		$this->assertFalse($r->supports('raw'));
		$this->assertFalse($r->supports(['mode' => 'fixed', 'group' => 'g', 'key' => 'k']));
		$this->expectException(\RuntimeException::class);
		$r->resolve(['group' => '', 'key' => 'x']);
	}

	public function testRejectsEmptyKey(): void {
		$this->expectException(\RuntimeException::class);
		(new \Base3\Core\ConfigValue\Mode\ConfigurationConfigValueModeResolver(new \Base3\Core\Configuration\ArrayConfiguration()))->resolve(['group' => 'g', 'key' => '']);
	}

}
