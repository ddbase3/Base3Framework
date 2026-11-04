<?php declare(strict_types=1);

namespace Base3\Test\Worker\Policy\Timing;

use PHPUnit\Framework\TestCase;

final class RandomIntervalJobPolicyTest extends TestCase {

	public function testFixedRandomIntervalAndFutureDeadline(): void {
		$store = new \Base3\Test\Worker\Policy\PolicyMemoryStateStore();
		$p = new \Base3\Worker\Policy\Timing\RandomIntervalJobPolicy($store);
		$p->setData(['min_seconds' => 120, 'max_seconds' => 120]);
		$this->assertTrue($p->shouldRun('backups'));
		$p->markRun('backups');
		$this->assertFalse($p->shouldRun('backups'));
		$this->assertArrayHasKey('worker.job.backups.random_interval.next_run_at', $store->values);
		$store->set('worker.job.backups.random_interval.next_run_at', '2000-01-01 00:00:00');
		$this->assertTrue($p->shouldRun('backups'));
		$store->set('worker.job.backups.random_interval.next_run_at', 'not-a-date');
		$this->assertTrue($p->shouldRun('backups'));
	}

	public function testNegativeIntervalsClampToZero(): void {
		$store = new \Base3\Test\Worker\Policy\PolicyMemoryStateStore();
		$p = new \Base3\Worker\Policy\Timing\RandomIntervalJobPolicy($store);
		$p->setData(['min_seconds' => -3, 'max_seconds' => -1]);
		$p->markRun('job');
		$this->assertArrayHasKey('worker.job.job.random_interval.last_run_at', $store->values);
		$this->assertSame(['min_seconds', 'max_seconds'], $p->getSchema()['required']);
	}

}

require_once __DIR__ . '/../PolicyMemoryStateStore.php';
