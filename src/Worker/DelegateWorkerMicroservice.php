<?php declare(strict_types=1);

namespace Base3\Worker;

use Base3\Worker\DelegateWorker;
use Base3\Worker\Api\IWorker;
use Base3\Microservice\AbstractMicroservice;

class DelegateWorkerMicroservice extends AbstractMicroservice implements IWorker {

	private $worker;

	public function __construct($cnf = null) {
		$this->worker = new DelegateWorker;
	}

	// Implementation of IWorker

	public function isActive() {
		return $this->worker->isActive();
	}

	public function getPriority() {
		return $this->worker->getPriority();
	}

	public function getJobs() {
		return $this->worker->getJobs();
	}

	public function doJob($job) {
		return $this->worker->doJob($job);
	}

}
