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

final class FixedConfigValueModeResolverTest extends TestCase {

	public function testScalarAndPlainArrayValuesPassThrough(): void {
		$r = new \Base3\Core\ConfigValue\Mode\FixedConfigValueModeResolver();
		$this->assertSame('fixed', $r->getMode());
		$this->assertTrue($r->supports('raw'));
		$this->assertTrue($r->supports(['value' => 42]));
		$this->assertSame(['value' => 42], $r->resolve(['value' => 42]));
		$this->assertSame(0, $r->resolve(0));
		$this->assertNull($r->resolve(null));
	}

	public function testCanonicalAndLegacyDefinitions(): void {
		$r = new \Base3\Core\ConfigValue\Mode\FixedConfigValueModeResolver();
		$this->assertTrue($r->supports(['mode' => 'fixed', 'value' => false]));
		$this->assertTrue($r->supports(['mode' => 'default', 'value' => 2]));
		$this->assertSame(false, $r->resolve(['mode' => 'fixed', 'value' => false]));
		$this->assertNull($r->resolve(['mode' => 'default']));
		$this->assertFalse($r->supports(['mode' => 'env', 'value' => 2]));
		$this->assertSame(['value'], $r->getSchema()['required']);
		$this->assertSame('fixedconfigvaluemoderesolver', $r::getName());
	}

}
