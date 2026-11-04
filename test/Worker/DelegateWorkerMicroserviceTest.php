<?php declare(strict_types=1);

namespace Base3\Test\Worker;

use PHPUnit\Framework\TestCase;

final class DelegateWorkerMicroserviceTest extends TestCase {

	public function testDelegatesWorkerStatusAndJobExecution(): void {
		$map=new \Base3\Test\Support\StaticClassMapStub();$job=new WorkerTestJob();
		$map->register($job::getName(),$job);
		$config=new \Base3\Configuration\ArrayConfiguration(['worker'=>['active'=>false,'priority'=>4]]);
		$service=new \Base3\Worker\DelegateWorkerMicroservice($map,$config);
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
