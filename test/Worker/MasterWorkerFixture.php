<?php declare(strict_types=1);

namespace Base3\Test\Worker;

use Base3\Worker\Api\IWorker;

final class MasterWorkerFixture implements IWorker {

	public int $statusChecks = 0;
	public int $jobQueries = 0;
	public int $executions = 0;

	public function __construct(public bool $active = false, public array $jobs = []) {}

	public static function getName(): string { return 'masterworkerfixture'; }
	public function isActive() { $this->statusChecks++; return $this->active; }
	public function getPriority() { return 1; }
	public function getJobs() { $this->jobQueries++; return $this->jobs; }
	public function doJob($job) { $this->executions++; return 'done'; }
}
