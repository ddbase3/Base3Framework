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

namespace Base3\Test\ConfigValue\Resolver;

use PHPUnit\Framework\TestCase;

final class ConfigValueResolverTest extends TestCase {

	public function testDiscoversModesAndSchemasAndResolvesInputs(): void {
		$map = new \Base3\Test\Support\StaticClassMapStub();
		$map->register('fixed', new \Base3\Core\ConfigValue\Mode\FixedConfigValueModeResolver());
		$map->register('env', new \Base3\Core\ConfigValue\Mode\EnvConfigValueModeResolver());
		$r = new \Base3\Core\ConfigValue\Resolver\ConfigValueResolver($map);
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
		$map->register('fixed', new \Base3\Core\ConfigValue\Mode\FixedConfigValueModeResolver());
		$r = new \Base3\Core\ConfigValue\Resolver\ConfigValueResolver($map);
		$this->expectException(\RuntimeException::class);
		$r->resolve(['mode'=>'env','name'=>'SECRET']);
	}

	public function testAmbiguousDefinitionsAreRejected(): void {
		$map = new \Base3\Test\Support\StaticClassMapStub();
		$map->register('one', new \Base3\Core\ConfigValue\Mode\FixedConfigValueModeResolver());
		$map->register('two', new \Base3\Core\ConfigValue\Mode\FixedConfigValueModeResolver());
		$r = new \Base3\Core\ConfigValue\Resolver\ConfigValueResolver($map);
		$this->expectException(\RuntimeException::class);
		$r->resolve('plain');
	}

	public function testDuplicateModeSchemasAreRejected(): void {
		$map = new \Base3\Test\Support\StaticClassMapStub();
		$map->register('one', new \Base3\Core\ConfigValue\Mode\FixedConfigValueModeResolver());
		$map->register('two', new \Base3\Core\ConfigValue\Mode\FixedConfigValueModeResolver());
		$r = new \Base3\Core\ConfigValue\Resolver\ConfigValueResolver($map);
		$this->expectException(\RuntimeException::class);
		$r->getModeSchema('fixed');
	}

}

require_once __DIR__ . '/../../Support/StaticClassMapStub.php';
