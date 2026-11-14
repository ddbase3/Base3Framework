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

final class AlwaysRunJobPolicyTest extends TestCase {

	public function testAlwaysAllowsExecutionWithoutConfiguration(): void {
		$p = new \Base3\Core\Worker\Policy\Timing\AlwaysRunJobPolicy();
		$this->assertSame('alwaysrunjobpolicy', $p::getName());
		$this->assertTrue($p->shouldRun('cleanup'));
		$p->setData(['unexpected' => true]);
		$p->markRun('cleanup');
		$this->assertTrue($p->shouldRun('cleanup'));
		$this->assertSame('', $p->getReason());
		$this->assertSame([], $p->getSchema());
	}

}
