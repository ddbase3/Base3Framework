<?php declare(strict_types=1);

namespace Base3\Test\Worker\Policy\Timing;

use PHPUnit\Framework\TestCase;

final class ManualOnlyJobPolicyTest extends TestCase {

	public function testOnlyExplicitRequestRunsAndMarkRunConsumesIt(): void {
		$store = new \Base3\Test\Worker\Policy\PolicyMemoryStateStore();
		$p = new \Base3\Worker\Policy\Timing\ManualOnlyJobPolicy($store);
		$p->setData(['policy_id' => 'Operator']);
		$this->assertFalse($p->shouldRun('cleanup'));
		$this->assertSame('Skip (manual run not requested)', $p->getReason());
		$store->set('worker.job.cleanup.manual_only.operator.requested', 1);
		$this->assertTrue($p->shouldRun('cleanup'));
		$p->markRun('cleanup');
		$this->assertSame(0, $store->get('worker.job.cleanup.manual_only.operator.requested'));
		$this->assertFalse($p->shouldRun('cleanup'));
		$this->assertArrayHasKey('worker.job.cleanup.manual_only.operator.last_run_at', $store->values);
	}

}

require_once __DIR__ . '/../PolicyMemoryStateStore.php';
