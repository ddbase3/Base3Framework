<?php declare(strict_types=1);

namespace Base3\Worker\Policy\Timing;

use Base3\State\Api\IStateStore;
use Base3\Worker\Policy\AbstractJobExecutionPolicy;

final class DailyAfterTimeJobPolicy extends AbstractJobExecutionPolicy {

	private const POLICY_TYPE = 'daily_after_time';
	private const DEFAULT_LAST_RUN_AT = '1970-01-01 00:00:00';

	public function __construct(
		private readonly IStateStore $state
	) {}

	public static function getName(): string {
		return 'dailyaftertimejobpolicy';
	}

	public function shouldRun(string $jobName) {
		$time = $this->getString('time', '00:00');

		if (date('H:i') < $time) {
			$this->setReason('Skip (before daily time)');
			return false;
		}

		$lastRunAt = (string)$this->state->get(
			$this->stateKey($jobName, self::POLICY_TYPE, 'last_run_at', $this->getPolicyId()),
			self::DEFAULT_LAST_RUN_AT
		);

		if ($this->isSameDay($lastRunAt)) {
			$this->setReason('Skip (already ran today)');
			return false;
		}

		return true;
	}

	public function markRun(string $jobName) {
		$this->state->set(
			$this->stateKey($jobName, self::POLICY_TYPE, 'last_run_at', $this->getPolicyId()),
			$this->nowSqlString()
		);
	}

	public function getSchema(): array {
		return [
			'type' => 'object',
			'required' => ['time'],
			'properties' => [
				'time' => [
					'type' => 'string',
					'description' => 'Earliest time of day when the job may run, format HH:MM.'
				],
				'id' => [
					'type' => 'string',
					'description' => 'Optional policy instance id.'
				]
			]
		];
	}
}
