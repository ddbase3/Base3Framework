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

namespace Base3\Test\Worker\Policy\Logic;

use PHPUnit\Framework\TestCase;

final class NotJobExecutionPolicyTest extends TestCase {

	private function createMap(): \Base3\Test\Support\StaticClassMapStub {
		$map = new \Base3\Test\Support\StaticClassMapStub();
		$map->register('allow', new \Base3\Core\Worker\Policy\Timing\AlwaysRunJobPolicy());
		$map->register('deny', new \Base3\Core\Worker\Policy\Domain\ConfigEnabledJobPolicy(new \Base3\Core\Configuration\ArrayConfiguration()));
		return $map;
	}

	public function testNegationInvertsDecision(): void {
		$p = new \Base3\Core\Worker\Policy\Logic\NotJobExecutionPolicy($this->createMap());
		$p->setData(['item' => ['policy'=>'allow']]);
		$this->assertFalse($p->shouldRun('job'));
		$this->assertSame('Skip (negated policy allowed execution)', $p->getReason());
		$p->setData(['policy'=>'deny']);
		$this->assertTrue($p->shouldRun('job'));
		$p->markRun('job');
	}

	public function testMissingInnerPolicyIsNoop(): void {
		$p = new \Base3\Core\Worker\Policy\Logic\NotJobExecutionPolicy($this->createMap());
		$p->setData(['item' => false]);
		$this->assertTrue($p->shouldRun('job'));
		$this->assertSame('object', $p->getSchema()['type']);
	}

}

require_once __DIR__ . '/../../../Support/StaticClassMapStub.php';
