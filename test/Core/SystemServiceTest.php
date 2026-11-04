<?php declare(strict_types=1);

namespace Base3\Test\Core;

use PHPUnit\Framework\TestCase;

final class SystemServiceTest extends TestCase {

	public function testStandaloneHostAndEmbeddedIdentityAgree(): void {
		$service = new \Base3\Core\SystemService();
		$this->assertSame('BASE3', $service->getHostSystemName());
		$this->assertSame('BASE3', $service->getEmbeddedSystemName());
		$this->assertSame($service->getHostSystemVersion(), $service->getEmbeddedSystemVersion());
	}

}
