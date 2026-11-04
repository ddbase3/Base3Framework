<?php declare(strict_types=1);

namespace Base3\Test\Worker\Policy\Logic;

use PHPUnit\Framework\TestCase;

final class AnyJobExecutionPolicyTest extends TestCase {

	private function createMap(): \Base3\Test\Support\StaticClassMapStub {
		$map = new \Base3\Test\Support\StaticClassMapStub();
		$map->register('allow', new \Base3\Worker\Policy\Timing\AlwaysRunJobPolicy());
		$map->register('deny', new \Base3\Worker\Policy\Domain\ConfigEnabledJobPolicy(new \Base3\Configuration\ArrayConfiguration()));
		return $map;
	}

	public function testAnyAllowsWhenAtLeastOnePolicyDoes(): void {
		$p = new \Base3\Worker\Policy\Logic\AnyJobExecutionPolicy($this->createMap());
		$p->setData(['items' => [['policy' => 'deny'], ['policy' => 'allow']]]);
		$this->assertTrue($p->shouldRun('cleanup'));
		$p->markRun('cleanup');
		$p->setData(['items' => [['policy' => 'deny']]]);
		$this->assertFalse($p->shouldRun('cleanup'));
		$this->assertSame('Skip (disabled by configuration)', $p->getReason());
	}

	public function testEmptyPolicyListDeniesAndDoesNotMark(): void {
		$p = new \Base3\Worker\Policy\Logic\AnyJobExecutionPolicy($this->createMap());
		$p->setData(['items' => []]);
		$this->assertFalse($p->shouldRun('job'));
		$this->assertSame('Skip (no policy allowed execution)', $p->getReason());
		$p->markRun('job');
	}

}

require_once __DIR__ . '/../../../Support/StaticClassMapStub.php';
