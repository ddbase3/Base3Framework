<?php declare(strict_types=1);

namespace Base3\Test\Core;

use PHPUnit\Framework\TestCase;

final class ModuleRegistryTest extends TestCase {

	public function testUnknownModuleDoesNotResolveOrRequire(): void {
		$registry = new \Base3\Core\ModuleRegistry();
		$this->assertNull($registry->getModulePath('__not_a_module__'));
		$this->expectException(\RuntimeException::class);
		$registry->requireModulePath('__not_a_module__');
	}

}
