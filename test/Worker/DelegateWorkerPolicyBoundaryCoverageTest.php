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

namespace Base3\Test\Worker;

use Base3\Core\Configuration\ArrayConfiguration;
use Base3\Test\Support\StaticClassMapStub;
use Base3\Test\Worker\Policy\InspectableJobPolicyFixture;
use Base3\Core\Worker\DelegateWorker;
use PHPUnit\Framework\TestCase;

final class DelegateWorkerPolicyBoundaryCoverageTest extends TestCase {

	private function build(array $definition, ?InspectableJobPolicyFixture $policy = null): array {
		$job = new PolicyWorkerTestJob();
		$job->definition = $definition;
		$map = new StaticClassMapStub();
		$map->register($job::getName(), $job);
		if ($policy !== null) $map->register($policy::getName(), $policy);
		return [new DelegateWorker($map, new ArrayConfiguration()), $job];
	}

	public function testEmptyAndInvalidPolicyNamesAreDeniedBeforeExecution(): void {
		foreach ([[], ['policy' => []], ['policy' => new \stdClass()], ['policy' => '  '], ['policy' => false]] as $definition) {
			[$worker, $job] = $this->build($definition);
			$this->assertSame('Skip (invalid job policy)', $worker->doJob($job::getName()));
			$this->assertSame(0, $job->runs);
		}
	}

	public function testUnknownPolicyDoesNotFallThroughToJobExecution(): void {
		[$worker, $job] = $this->build(['policy' => 'unknown']);
		$this->assertSame('Skip (invalid job policy)', $worker->doJob($job::getName()));
		$this->assertSame(0, $job->runs);
	}

	public function testScalarPolicyDataIsNormalizedToEmptyArray(): void {
		$policy = new InspectableJobPolicyFixture();
		[$worker, $job] = $this->build(['policy' => $policy::getName(), 'data' => 'invalid'], $policy);
		$this->assertSame('executed', $worker->doJob($job::getName()));
		$this->assertSame('default', $policy->readString('note', 'default'));
		$this->assertSame(1, $job->runs);
	}

	public function testStructuredPolicyDataIsPreserved(): void {
		$policy = new InspectableJobPolicyFixture();
		[$worker, $job] = $this->build(['policy' => $policy::getName(), 'data' => ['note' => 'retained']], $policy);
		$this->assertSame('executed', $worker->doJob($job::getName()));
		$this->assertSame('retained', $policy->readString('note'));
	}
}

require_once __DIR__ . '/PolicyWorkerTestJob.php';
require_once __DIR__ . '/../Support/StaticClassMapStub.php';
require_once __DIR__ . '/Policy/InspectableJobPolicyFixture.php';
