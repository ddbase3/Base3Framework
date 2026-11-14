<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of BASE3 Framework.
 *
 * BASE3 Framework is a lightweight, modular PHP framework for scalable
 * and maintainable web applications. Built for extensibility,
 * performance, and modern development, it can run standalone or
 * integrate as a subsystem within a host system.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/Base3Framework
 **********************************************************************/

namespace Base3\Test\Worker\Policy\Timing;

use PHPUnit\Framework\TestCase;

final class DailyWindowJobPolicyTest extends TestCase {

	public function testDailyWindowAllowsRunOnlyOnceEachDay(): void {
		$store = new \Base3\Test\Worker\Policy\PolicyMemoryStateStore();
		$p = new \Base3\Core\Worker\Policy\Timing\DailyWindowJobPolicy($store);
		$p->setData(['from' => '00:00', 'to' => '24:00']);
		$this->assertTrue($p->shouldRun('job'));
		$p->markRun('job');
		$this->assertFalse($p->shouldRun('job'));
		$this->assertSame('Skip (already ran today)', $p->getReason());
		$store->set('worker.job.job.daily_window.last_run_at', '2000-01-01 00:00:00');
		$this->assertTrue($p->shouldRun('job'));
	}

	public function testEmptyWindowIsNeverAllowed(): void {
		$p = new \Base3\Core\Worker\Policy\Timing\DailyWindowJobPolicy(new \Base3\Test\Worker\Policy\PolicyMemoryStateStore());
		$p->setData(['from' => '12:00', 'to' => '12:00']);
		$this->assertFalse($p->shouldRun('job'));
		$this->assertSame('Skip (not in daily window)', $p->getReason());
	}

}

require_once __DIR__ . '/../PolicyMemoryStateStore.php';
