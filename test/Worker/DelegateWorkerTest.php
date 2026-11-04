<?php declare(strict_types=1);

namespace Base3\Test\Worker;

use PHPUnit\Framework\TestCase;

final class DelegateWorkerTest extends TestCase {

	public function testWorkerConfigurationAndDiscoveredJobs(): void {
		$map=new \Base3\Test\Support\StaticClassMapStub();
		$job=new WorkerTestJob();$map->register($job::getName(),$job);
		$config=new \Base3\Configuration\ArrayConfiguration(['worker'=>['active'=>false,'priority'=>3]]);
		$w=new \Base3\Worker\DelegateWorker($map,$config);
		$this->assertSame('delegateworker',$w::getName());
		$this->assertFalse($w->isActive());
		$this->assertSame(3,$w->getPriority());
		$this->assertSame([['name'=>'workertestjob','active'=>true,'priority'=>2]],$w->getJobs());
		$this->assertSame('done',$w->doJob('workertestjob'));
		$this->assertSame(1,$job->runs);
		$this->assertNull($w->doJob('unknown'));
		$this->assertSame('Ok',$w->checkDependencies()['num_of_jobs_1']);
	}

	public function testInvalidPriorityFallsBackToDefault(): void {
		$map=new \Base3\Test\Support\StaticClassMapStub();
		$w=new \Base3\Worker\DelegateWorker($map,new \Base3\Configuration\ArrayConfiguration(['worker'=>['priority'=>-4]]));
		$this->assertSame(1,$w->getPriority());
		$this->assertTrue($w->isActive());
		$this->assertSame([],$w->getJobs());
	}

	public function testInvalidPolicyDeniesExecutionRatherThanRunningJob(): void {
		$map=new \Base3\Test\Support\StaticClassMapStub();$job=new PolicyWorkerTestJob();
		$map->register($job::getName(),$job);
		$w=new \Base3\Worker\DelegateWorker($map,new \Base3\Configuration\ArrayConfiguration());
		$job->definition=[];
		$this->assertSame('Skip (invalid job policy)',$w->doJob($job::getName()));
		$this->assertSame(0,$job->runs);
		$job->definition=['policy'=>'absent'];
		$this->assertSame('Skip (invalid job policy)',$w->doJob($job::getName()));
		$this->assertSame(0,$job->runs);
	}

	public function testPolicyControlledJobReceivesApprovedPolicy(): void {
		$map=new \Base3\Test\Support\StaticClassMapStub();$job=new PolicyWorkerTestJob();
		$policy=new \Base3\Worker\Policy\Timing\AlwaysRunJobPolicy();
		$job->definition=['policy'=>$policy::getName(),'data'=>['note'=>true]];
		$map->register($job::getName(),$job);$map->register($policy::getName(),$policy);
		$w=new \Base3\Worker\DelegateWorker($map,new \Base3\Configuration\ArrayConfiguration());
		$this->assertSame('executed',$w->doJob($job::getName()));
		$this->assertSame($policy,$job->assignedPolicy);
		$this->assertSame(1,$job->runs);
	}

}

require_once __DIR__ . '/WorkerTestJob.php';
require_once __DIR__ . '/PolicyWorkerTestJob.php';
require_once __DIR__ . '/../Support/StaticClassMapStub.php';
