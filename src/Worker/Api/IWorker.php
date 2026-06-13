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

