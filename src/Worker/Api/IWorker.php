<?php declare(strict_types=1);

namespace Base3\Worker\Api;

use Base3\Api\IBase;

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
