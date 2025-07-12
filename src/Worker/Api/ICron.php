<?php declare(strict_types=1);

namespace Base3\Worker\Api;

/**
 * Interface ICron
 *
 * Represents a scheduled job with a cron-style time definition.
 * Inherits execution behavior from IJob.
 */
interface ICron extends IJob {

	/**
	 * Returns the cron time code defining when the job should run.
	 *
	 * Format: [minute, hour, dayOfMonth, month, dayOfWeek]
	 * Example: ["0", "4", "*", "*", "*"] → every day at 4:00 AM
	 *
	 * @return array<int, string> Cron time pattern components
	 */
	public function getTimeCode();

}

