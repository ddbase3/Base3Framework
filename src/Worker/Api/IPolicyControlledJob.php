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

/**
 * Interface IPolicyControlledJob
 *
 * Represents a job whose execution is additionally controlled by a policy.
 */
interface IPolicyControlledJob extends IJob {

	/**
	 * Returns the policy definition for this job.
	 *
	 * Example:
	 * [
	 *     'policy' => 'dailywindowjobpolicy',
	 *     'data' => [
	 *         'from' => '02:00',
	 *         'to' => '04:00'
	 *     ]
	 * ]
	 *
	 * @return array Policy definition
	 */
	public function getPolicyDefinition(): array;

	/**
	 * Provides the resolved policy instance for this execution.
	 */
	public function setExecutionPolicy(IJobExecutionPolicy $policy): void;

	/**
	 * Marks the current execution as completed for the active policy.
	 */
	public function markRun(): void;

}
