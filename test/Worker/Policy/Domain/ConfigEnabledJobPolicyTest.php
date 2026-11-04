<?php declare(strict_types=1);

namespace Base3\Test\Worker\Policy\Domain;

use PHPUnit\Framework\TestCase;

final class ConfigEnabledJobPolicyTest extends TestCase {

	public function testUsesExistingConfigurationWithoutImplicitGrant(): void {
		$config = new \Base3\Configuration\ArrayConfiguration(['job' => ['cleanup.active' => 1]]);
		$p = new \Base3\Worker\Policy\Domain\ConfigEnabledJobPolicy($config);
		$this->assertTrue($p->shouldRun('cleanup'));
		$this->assertFalse($p->shouldRun('backup'));
		$this->assertSame('Skip (disabled by configuration)', $p->getReason());
		$p->setData(['section' => 'worker', 'key' => 'enabled', 'expected' => 0]);
		$this->assertTrue($p->shouldRun('backup'));
		$this->assertSame('object', $p->getSchema()['type']);
	}

}
