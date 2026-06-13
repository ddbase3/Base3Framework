<?php declare(strict_types=1);

namespace Base3\Worker\Policy\Timing;

use Base3\State\Api\IStateStore;
use Base3\Worker\Policy\AbstractJobExecutionPolicy;

final class IntervalJobPolicy extends AbstractJobExecutionPolicy {

	private const POLICY_TYPE = 'interval';
	private const DEFAULT_LAST_RUN_AT = '1970-01-01 00:00:00';

	public function __construct(
		private readonly IStateStore $state
	) {}

	public static function getName(): string {
		return 'intervaljobpolicy';
	}

	public function shouldRun(string $jobName) {
		$seconds = $this->getInt('seconds', 0);

		if ($seconds <= 0) {
			return true;
		}

		$lastRunAt = (string)$this->state->get(
			$this->stateKey($jobName, self::POLICY_TYPE, 'last_run_at', $this->getPolicyId()),
			self::DEFAULT_LAST_RUN_AT
		);

		$lastRunTs = strtotime($lastRunAt);
		if ($lastRunTs === false) {
			return true;
		}

		if ((time() - $lastRunTs) < $seconds) {
			$this->setReason('Skip (interval not reached)');
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
			'required' => ['seconds'],
			'properties' => [
				'seconds' => [
					'type' => 'integer',
					'description' => 'Minimum number of seconds between two successful runs.'
				],
				'id' => [
					'type' => 'string',
					'description' => 'Optional policy instance id.'
				]
			]
		];
	}
}
