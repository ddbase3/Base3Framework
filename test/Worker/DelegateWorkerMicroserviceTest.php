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

use PHPUnit\Framework\TestCase;

final class DelegateWorkerMicroserviceTest extends TestCase {

	public function testDelegatesWorkerStatusAndJobExecution(): void {
		$map=new \Base3\Test\Support\StaticClassMapStub();$job=new WorkerTestJob();
		$map->register($job::getName(),$job);
		$config=new \Base3\Core\Configuration\ArrayConfiguration(['worker'=>['active'=>false,'priority'=>4]]);
		$service=new \Base3\Core\Worker\DelegateWorkerMicroservice($map,$config);
		$this->assertSame('delegateworkermicroservice',$service::getName());
		$this->assertFalse($service->isActive());
		$this->assertSame(4,$service->getPriority());
		$this->assertSame('workertestjob',$service->getJobs()[0]['name']);
		$this->assertSame('done',$service->doJob('workertestjob'));
		$this->assertSame(1,$job->runs);
		$this->assertNull($service->doJob('missing'));
	}

}

require_once __DIR__ . '/WorkerTestJob.php';
require_once __DIR__ . '/../Support/StaticClassMapStub.php';
