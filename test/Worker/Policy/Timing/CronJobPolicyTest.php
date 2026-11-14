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

final class CronJobPolicyTest extends TestCase {

	public function testWildcardScheduleIsAllowedOnlyOncePerMinute(): void {
		$store = new \Base3\Test\Worker\Policy\PolicyMemoryStateStore();
		$p = new \Base3\Core\Worker\Policy\Timing\CronJobPolicy($store);
		$p->setData(['expression' => '* * * * *']);
		$this->assertTrue($p->shouldRun('sync'));
		$p->markRun('sync');
		$this->assertFalse($p->shouldRun('sync'));
		$this->assertSame('Skip (cron slot already handled)', $p->getReason());
		$this->assertSame(['expression'], $p->getSchema()['required']);
	}

	public function testRejectsMalformedAndMismatchedExpressions(): void {
		$p = new \Base3\Core\Worker\Policy\Timing\CronJobPolicy(new \Base3\Test\Worker\Policy\PolicyMemoryStateStore());
		$p->setData(['expression' => '* * *']);
		$this->assertFalse($p->shouldRun('sync'));
		$this->assertSame('Skip (invalid cron expression)', $p->getReason());
		$p->setData(['expression' => ((int)date('i') + 1) % 60 . ' * * * *']);
		$this->assertFalse($p->shouldRun('sync'));
		$this->assertSame('Skip (cron minute does not match)', $p->getReason());
		$p->setData(['expression' => '* '.(((int)date('G') + 1) % 24).' * * *']);
		$this->assertFalse($p->shouldRun('sync'));
		$this->assertSame('Skip (cron hour does not match)', $p->getReason());
	}

	public function testCommasRangesAndStepsMatchCurrentTime(): void {
		$p = new \Base3\Core\Worker\Policy\Timing\CronJobPolicy(new \Base3\Test\Worker\Policy\PolicyMemoryStateStore());
		$p->setData(['expression' => '0-59/1 0-23/1 1-31 1-12 0-6']);
		$this->assertTrue($p->shouldRun('sync'));
		$p->setData(['expression' => '99,'.date('i').' * * * *']);
		$this->assertTrue($p->shouldRun('sync'));
	}

}

require_once __DIR__ . '/../PolicyMemoryStateStore.php';
