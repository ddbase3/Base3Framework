<?php declare(strict_types=1);

namespace Base3\Test\Worker\Policy\Logic;

use PHPUnit\Framework\TestCase;

final class NotJobExecutionPolicyTest extends TestCase {

	private function createMap(): \Base3\Test\Support\StaticClassMapStub {
		$map = new \Base3\Test\Support\StaticClassMapStub();
		$map->register('allow', new \Base3\Worker\Policy\Timing\AlwaysRunJobPolicy());
		$map->register('deny', new \Base3\Worker\Policy\Domain\ConfigEnabledJobPolicy(new \Base3\Configuration\ArrayConfiguration()));
		return $map;
	}

	public function testNegationInvertsDecision(): void {
		$p = new \Base3\Worker\Policy\Logic\NotJobExecutionPolicy($this->createMap());
		$p->setData(['item' => ['policy'=>'allow']]);
		$this->assertFalse($p->shouldRun('job'));
		$this->assertSame('Skip (negated policy allowed execution)', $p->getReason());
		$p->setData(['policy'=>'deny']);
		$this->assertTrue($p->shouldRun('job'));
		$p->markRun('job');
	}

	public function testMissingInnerPolicyIsNoop(): void {
		$p = new \Base3\Worker\Policy\Logic\NotJobExecutionPolicy($this->createMap());
		$p->setData(['item' => false]);
		$this->assertTrue($p->shouldRun('job'));
		$this->assertSame('object', $p->getSchema()['type']);
	}

}

require_once __DIR__ . '/../../../Support/StaticClassMapStub.php';
