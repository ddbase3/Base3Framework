<?php declare(strict_types=1);

namespace Base3\Test\ConfigValue\Mode;

use PHPUnit\Framework\TestCase;

final class ConfigurationConfigValueModeResolverTest extends TestCase {

	public function testCanonicalAndLegacyModesPreserveExplicitNullValues(): void {
		$config = new \Base3\Configuration\ArrayConfiguration(['app' => ['secret' => null, 'key' => 'abc']]);
		$r = new \Base3\ConfigValue\Mode\ConfigurationConfigValueModeResolver($config);
		$this->assertSame('configuration', $r->getMode());
		$this->assertTrue($r->supports(['mode' => 'configuration', 'group' => 'app', 'key' => 'secret']));
		$this->assertTrue($r->supports(['mode' => 'config', 'section' => 'app', 'key' => 'key']));
		$this->assertNull($r->resolve(['group' => 'app', 'key' => 'secret']));
		$this->assertSame('abc', $r->resolve(['section' => 'app', 'key' => 'key']));
		$this->assertSame(['group', 'key'], $r->getSchema()['required']);
	}

	public function testRejectsMissingGroup(): void {
		$r = new \Base3\ConfigValue\Mode\ConfigurationConfigValueModeResolver(new \Base3\Configuration\ArrayConfiguration());
		$this->expectException(\RuntimeException::class);
		$r->resolve(['group' => 'notfound', 'key' => 'key']);
	}

	public function testRejectsMissingKey(): void {
		$r = new \Base3\ConfigValue\Mode\ConfigurationConfigValueModeResolver(new \Base3\Configuration\ArrayConfiguration(['g' => []]));
		$this->expectException(\RuntimeException::class);
		$r->resolve(['group' => 'g', 'key' => 'missing']);
	}

	public function testRejectsMalformedGroupAndKey(): void {
		$r = new \Base3\ConfigValue\Mode\ConfigurationConfigValueModeResolver(new \Base3\Configuration\ArrayConfiguration());
		$this->assertFalse($r->supports('raw'));
		$this->assertFalse($r->supports(['mode' => 'fixed', 'group' => 'g', 'key' => 'k']));
		$this->expectException(\RuntimeException::class);
		$r->resolve(['group' => '', 'key' => 'x']);
	}

	public function testRejectsEmptyKey(): void {
		$this->expectException(\RuntimeException::class);
		(new \Base3\ConfigValue\Mode\ConfigurationConfigValueModeResolver(new \Base3\Configuration\ArrayConfiguration()))->resolve(['group' => 'g', 'key' => '']);
	}

}
