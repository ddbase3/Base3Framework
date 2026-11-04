<?php declare(strict_types=1);

namespace Base3\Test\Worker\Policy\Timing;

use PHPUnit\Framework\TestCase;

final class DailyAfterTimeJobPolicyTest extends TestCase {

	public function testDailyExecutionTracksDayAndCutoff(): void {
		$store = new \Base3\Test\Worker\Policy\PolicyMemoryStateStore();
		$p = new \Base3\Worker\Policy\Timing\DailyAfterTimeJobPolicy($store);
		$p->setData(['time' => '00:00']);
		$this->assertTrue($p->shouldRun('report'));
		$p->markRun('report');
		$this->assertFalse($p->shouldRun('report'));
		$this->assertSame('Skip (already ran today)', $p->getReason());
		$store->set('worker.job.report.daily_after_time.last_run_at', '2000-01-01 00:00:00');
		$this->assertTrue($p->shouldRun('report'));
		$p->setData(['time' => '99:99']);
		$this->assertFalse($p->shouldRun('report'));
		$this->assertSame('Skip (before daily time)', $p->getReason());
	}

}

require_once __DIR__ . '/../PolicyMemoryStateStore.php';
