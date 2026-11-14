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

final class ManualOnlyJobPolicyTest extends TestCase {

	public function testOnlyExplicitRequestRunsAndMarkRunConsumesIt(): void {
		$store = new \Base3\Test\Worker\Policy\PolicyMemoryStateStore();
		$p = new \Base3\Core\Worker\Policy\Timing\ManualOnlyJobPolicy($store);
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
