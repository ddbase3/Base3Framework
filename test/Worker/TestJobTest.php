<?php declare(strict_types=1);

namespace Base3\Test\Worker;

use PHPUnit\Framework\TestCase;

final class TestJobTest extends TestCase {

	public function testExplainsMissingJobParameterWithoutDispatching(): void {
		$map=new \Base3\Test\Support\StaticClassMapStub();
		$request=new \Base3\Test\ServiceSelector\SelectorTestRequest();
		$service=new \Base3\Worker\TestJob($map,$request);
		$this->assertSame('testjob',$service::getName());
		$this->assertStringContainsString('select a job',$service->getOutput());
		$this->assertStringContainsString('TestJob',$service->getHelp());
	}

	public function testDispatchesRequestedJobByRegisteredName(): void {
		$map=new \Base3\Test\Support\StaticClassMapStub();$job=new WorkerTestJob();
		$map->register($job::getName(),$job);
		$request=new \Base3\Test\ServiceSelector\SelectorTestRequest(['job'=>$job::getName()]);
		$service=new \Base3\Worker\TestJob($map,$request);
		$this->assertSame("\ndone\n\n",$service->getOutput());
		$this->assertSame(1,$job->runs);
	}

}

require_once __DIR__ . '/WorkerTestJob.php';
require_once __DIR__ . '/../ServiceSelector/SelectorTestRequest.php';
require_once __DIR__ . '/../Support/StaticClassMapStub.php';
