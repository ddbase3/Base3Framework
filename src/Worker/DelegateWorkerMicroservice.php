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

namespace Base3\Worker;

use Base3\Worker\DelegateWorker;
use Base3\Worker\Api\IWorker;
use Base3\Microservice\AbstractMicroservice;

class DelegateWorkerMicroservice extends AbstractMicroservice implements IWorker {

	private $worker;

	public function __construct() {
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
