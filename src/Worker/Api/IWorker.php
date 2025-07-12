<?php declare(strict_types=1);

namespace Base3\Worker\Api;

use Base3\Api\IBase;

/**
 * Interface IWorker
 *
 * Represents a worker capable of executing one or more jobs.
 */
interface IWorker extends IBase {

	/**
	 * Indicates whether the worker is currently active.
	 *
	 * @return bool True if active, false otherwise
	 */
	public function isActive();

	/**
	 * Returns the worker's priority (0–100).
	 *
	 * Used to determine execution order or resource allocation.
	 *
	 * @return int Priority value
	 */
	public function getPriority();

	/**
	 * Returns the list of jobs this worker is responsible for.
	 *
	 * @return array<int, IJob> List of job instances
	 */
	public function getJobs();

	/**
	 * Executes a specific job.
	 *
	 * @param IJob $job The job to be executed
	 * @return mixed Result of the job execution
	 */
	public function doJob($job);

}

