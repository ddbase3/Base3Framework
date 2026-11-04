<?php declare(strict_types=1);

namespace Base3\Test\ConfigValue\Resolver;

use PHPUnit\Framework\TestCase;

final class ConfigValueResolverTest extends TestCase {

	public function testDiscoversModesAndSchemasAndResolvesInputs(): void {
		$map = new \Base3\Test\Support\StaticClassMapStub();
		$map->register('fixed', new \Base3\ConfigValue\Mode\FixedConfigValueModeResolver());
		$map->register('env', new \Base3\ConfigValue\Mode\EnvConfigValueModeResolver());
		$r = new \Base3\ConfigValue\Resolver\ConfigValueResolver($map);
		$this->assertSame('hello', $r->resolve('hello'));
		$this->assertSame(false, $r->resolve(['mode'=>'fixed','value'=>false]));
		$this->assertSame(['env','fixed'], $r->getModes());
		$this->assertSame(['name'], $r->getModeSchema('env')['required']);
		$this->assertNull($r->getModeSchema('missing'));
		$this->assertSame(['env', 'fixed'], array_keys($r->getModeSchemas()));
		$this->assertSame(['envconfigvaluemoderesolver', 'fixedconfigvaluemoderesolver'], $r->getModeResolverNames());
	}

	public function testNoMatchingModeIsReported(): void {
		$map = new \Base3\Test\Support\StaticClassMapStub();
		$map->register('fixed', new \Base3\ConfigValue\Mode\FixedConfigValueModeResolver());
		$r = new \Base3\ConfigValue\Resolver\ConfigValueResolver($map);
		$this->expectException(\RuntimeException::class);
		$r->resolve(['mode'=>'env','name'=>'SECRET']);
	}

	public function testAmbiguousDefinitionsAreRejected(): void {
		$map = new \Base3\Test\Support\StaticClassMapStub();
		$map->register('one', new \Base3\ConfigValue\Mode\FixedConfigValueModeResolver());
		$map->register('two', new \Base3\ConfigValue\Mode\FixedConfigValueModeResolver());
		$r = new \Base3\ConfigValue\Resolver\ConfigValueResolver($map);
		$this->expectException(\RuntimeException::class);
		$r->resolve('plain');
	}

	public function testDuplicateModeSchemasAreRejected(): void {
		$map = new \Base3\Test\Support\StaticClassMapStub();
		$map->register('one', new \Base3\ConfigValue\Mode\FixedConfigValueModeResolver());
		$map->register('two', new \Base3\ConfigValue\Mode\FixedConfigValueModeResolver());
		$r = new \Base3\ConfigValue\Resolver\ConfigValueResolver($map);
		$this->expectException(\RuntimeException::class);
		$r->getModeSchema('fixed');
	}

}

require_once __DIR__ . '/../../Support/StaticClassMapStub.php';
