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

final class RandomIntervalJobPolicyTest extends TestCase {

	public function testFixedRandomIntervalAndFutureDeadline(): void {
		$store = new \Base3\Test\Worker\Policy\PolicyMemoryStateStore();
		$p = new \Base3\Core\Worker\Policy\Timing\RandomIntervalJobPolicy($store);
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
		$p = new \Base3\Core\Worker\Policy\Timing\RandomIntervalJobPolicy($store);
		$p->setData(['min_seconds' => -3, 'max_seconds' => -1]);
		$p->markRun('job');
		$this->assertArrayHasKey('worker.job.job.random_interval.last_run_at', $store->values);
		$this->assertSame(['min_seconds', 'max_seconds'], $p->getSchema()['required']);
	}

}

require_once __DIR__ . '/../PolicyMemoryStateStore.php';
