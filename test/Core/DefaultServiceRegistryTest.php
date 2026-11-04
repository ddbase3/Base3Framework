<?php declare(strict_types=1);

namespace Base3\Test\Core;

use PHPUnit\Framework\TestCase;

final class DefaultServiceRegistryTest extends TestCase {

	public function testConstructsLazilyAndCachesExactObject(): void {
		$created = 0;
		$registry = new \Base3\Core\DefaultServiceRegistry(\Base3\ConfigValue\Api\IConfigValueModeResolver::class, 'primary', [
			'primary' => static function () use (&$created) { $created++; return new \Base3\ConfigValue\Mode\FixedConfigValueModeResolver(); },
			'secondary' => static fn() => new \Base3\ConfigValue\Mode\EnvConfigValueModeResolver()
		]);
		$this->assertSame(0, $created);
		$this->assertTrue($registry->has('primary'));
		$this->assertFalse($registry->has('missing'));
		$this->assertSame(['primary','secondary'], $registry->listNames());
		$first = $registry->getDefault();
		$this->assertSame($first, $registry->get('primary'));
		$this->assertSame(1, $created);
		$this->assertInstanceOf(\Base3\ConfigValue\Mode\EnvConfigValueModeResolver::class, $registry->get('secondary'));
	}

	public function testRejectsUnknownServiceInstance(): void {
		$registry = new \Base3\Core\DefaultServiceRegistry(\Base3\ConfigValue\Api\IConfigValueModeResolver::class, 'main', ['main'=>static fn() => new \Base3\ConfigValue\Mode\FixedConfigValueModeResolver()]);
		$this->expectException(\RuntimeException::class);
		$registry->get('unknown');
	}

	public function testRejectsWrongFactoryReturnType(): void {
		$registry = new \Base3\Core\DefaultServiceRegistry(\Base3\ConfigValue\Api\IConfigValueModeResolver::class, 'main', ['main'=>static fn() => new \stdClass()]);
		$this->expectException(\RuntimeException::class);
		$registry->get('main');
	}

	public function testRejectsMissingDefault(): void {
		$this->expectException(\RuntimeException::class);
		new \Base3\Core\DefaultServiceRegistry(\Base3\ConfigValue\Api\IConfigValueModeResolver::class, 'other', ['main'=>static fn() => new \Base3\ConfigValue\Mode\FixedConfigValueModeResolver()]);
	}

	public function testRejectsInvalidInterface(): void {
		$this->expectException(\RuntimeException::class);
		new \Base3\Core\DefaultServiceRegistry(\stdClass::class, 'main', ['main'=>static fn() => new \stdClass()]);
	}

}
