<?php declare(strict_types=1);

namespace Base3\Worker\Api;

use Base3\Api\IBase;

/**
 * Interface IJob
 *
 * Represents a background or scheduled job that can be executed by a worker.
 */
interface IJob extends IBase {

	/**
	 * Determines whether the job is currently active and should be considered for execution.
	 *
	 * @return bool True if active, false otherwise
	 */
	public function isActive();

	/**
	 * Returns the job's execution priority (0–100).
	 *
	 * Higher values indicate higher priority.
	 *
	 * @return int Priority value
	 */
	public function getPriority();

	/**
	 * Executes the job logic.
	 *
	 * @return mixed Result of the job execution (may vary by implementation)
	 */
	public function go();

}

