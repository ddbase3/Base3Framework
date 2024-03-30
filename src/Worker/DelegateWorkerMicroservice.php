<?php

namespace Worker;

use Worker\Api\IWorker;
use Microservice\AbstractMicroservice;

class DelegateWorkerMicroservice extends AbstractMicroservice implements IWorker {

	private $worker;

	public function __construct($cnf = null) {
		$this->worker = new \Worker\DelegateWorker;
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
