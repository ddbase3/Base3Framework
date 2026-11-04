<?php declare(strict_types=1);

namespace Base3\Test\ConfigValue\Mode;

use PHPUnit\Framework\TestCase;

final class FixedConfigValueModeResolverTest extends TestCase {

	public function testScalarAndPlainArrayValuesPassThrough(): void {
		$r = new \Base3\ConfigValue\Mode\FixedConfigValueModeResolver();
		$this->assertSame('fixed', $r->getMode());
		$this->assertTrue($r->supports('raw'));
		$this->assertTrue($r->supports(['value' => 42]));
		$this->assertSame(['value' => 42], $r->resolve(['value' => 42]));
		$this->assertSame(0, $r->resolve(0));
		$this->assertNull($r->resolve(null));
	}

	public function testCanonicalAndLegacyDefinitions(): void {
		$r = new \Base3\ConfigValue\Mode\FixedConfigValueModeResolver();
		$this->assertTrue($r->supports(['mode' => 'fixed', 'value' => false]));
		$this->assertTrue($r->supports(['mode' => 'default', 'value' => 2]));
		$this->assertSame(false, $r->resolve(['mode' => 'fixed', 'value' => false]));
		$this->assertNull($r->resolve(['mode' => 'default']));
		$this->assertFalse($r->supports(['mode' => 'env', 'value' => 2]));
		$this->assertSame(['value'], $r->getSchema()['required']);
		$this->assertSame('fixedconfigvaluemoderesolver', $r::getName());
	}

}
