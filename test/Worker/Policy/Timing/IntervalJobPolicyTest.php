<?php declare(strict_types=1);

namespace Base3\Test\Worker\Policy\Timing;

use PHPUnit\Framework\TestCase;

final class IntervalJobPolicyTest extends TestCase {

	public function testIntervalsSkipUntilExpiredAndRespectPolicyIds(): void {
		$store = new \Base3\Test\Worker\Policy\PolicyMemoryStateStore();
		$p = new \Base3\Worker\Policy\Timing\IntervalJobPolicy($store);
		$p->setData(['seconds' => 3600, 'id' => 'Nightly Task']);
		$this->assertTrue($p->shouldRun('Export-JOB'));
		$p->markRun('Export-JOB');
		$this->assertArrayHasKey('worker.job.export_job.interval.nightly_task.last_run_at', $store->values);
		$this->assertFalse($p->shouldRun('Export-JOB'));
		$this->assertSame('Skip (interval not reached)', $p->getReason());
		$store->set('worker.job.export_job.interval.nightly_task.last_run_at', 'not-a-date');
		$this->assertTrue($p->shouldRun('Export-JOB'));
		$this->assertSame(['seconds'], $p->getSchema()['required']);
	}

	public function testZeroIntervalNeverBlocks(): void {
		$p = new \Base3\Worker\Policy\Timing\IntervalJobPolicy(new \Base3\Test\Worker\Policy\PolicyMemoryStateStore());
		$p->setData(['seconds' => -10]);
		$this->assertTrue($p->shouldRun('job'));
		$p->markRun('job');
		$this->assertTrue($p->shouldRun('job'));
	}

}

require_once __DIR__ . '/../PolicyMemoryStateStore.php';
