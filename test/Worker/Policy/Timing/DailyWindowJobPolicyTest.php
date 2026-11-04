<?php declare(strict_types=1);

namespace Base3\Test\Worker\Policy\Timing;

use PHPUnit\Framework\TestCase;

final class DailyWindowJobPolicyTest extends TestCase {

	public function testDailyWindowAllowsRunOnlyOnceEachDay(): void {
		$store = new \Base3\Test\Worker\Policy\PolicyMemoryStateStore();
		$p = new \Base3\Worker\Policy\Timing\DailyWindowJobPolicy($store);
		$p->setData(['from' => '00:00', 'to' => '24:00']);
		$this->assertTrue($p->shouldRun('job'));
		$p->markRun('job');
		$this->assertFalse($p->shouldRun('job'));
		$this->assertSame('Skip (already ran today)', $p->getReason());
		$store->set('worker.job.job.daily_window.last_run_at', '2000-01-01 00:00:00');
		$this->assertTrue($p->shouldRun('job'));
	}

	public function testEmptyWindowIsNeverAllowed(): void {
		$p = new \Base3\Worker\Policy\Timing\DailyWindowJobPolicy(new \Base3\Test\Worker\Policy\PolicyMemoryStateStore());
		$p->setData(['from' => '12:00', 'to' => '12:00']);
		$this->assertFalse($p->shouldRun('job'));
		$this->assertSame('Skip (not in daily window)', $p->getReason());
	}

}

require_once __DIR__ . '/../PolicyMemoryStateStore.php';
