<?php

namespace Worker\Api;

use Api\IBase;

interface IWorker extends IBase {

	// active?
	public function isActive();

	// value 0..100
	public function getPriority();

	// get jobs
	public function getJobs();

	// do job
	public function doJob($job);

}
