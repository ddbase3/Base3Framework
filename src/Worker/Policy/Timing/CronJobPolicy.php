<?php declare(strict_types=1);

namespace Base3\Worker\Policy\Timing;

use Base3\State\Api\IStateStore;
use Base3\Worker\Policy\AbstractJobExecutionPolicy;

final class CronJobPolicy extends AbstractJobExecutionPolicy {

	private const POLICY_TYPE = 'cron';

	public function __construct(
		private readonly IStateStore $state
	) {}

	public static function getName(): string {
		return 'cronjobpolicy';
	}

	public function shouldRun(string $jobName) {
		$expression = $this->getString('expression', '');
		$parts = preg_split('/\s+/', trim($expression)) ?: [];

		if (count($parts) !== 5) {
			$this->setReason('Skip (invalid cron expression)');
			return false;
		}

		[$minute, $hour, $dayOfMonth, $month, $dayOfWeek] = $parts;

		if (!$this->matches((int)date('i'), $minute, 0, 59)) {
			$this->setReason('Skip (cron minute does not match)');
			return false;
		}

		if (!$this->matches((int)date('G'), $hour, 0, 23)) {
			$this->setReason('Skip (cron hour does not match)');
			return false;
		}

		if (!$this->matches((int)date('j'), $dayOfMonth, 1, 31)) {
			$this->setReason('Skip (cron day of month does not match)');
			return false;
		}

		if (!$this->matches((int)date('n'), $month, 1, 12)) {
			$this->setReason('Skip (cron month does not match)');
			return false;
		}

		if (!$this->matches((int)date('w'), $dayOfWeek, 0, 6)) {
			$this->setReason('Skip (cron day of week does not match)');
			return false;
		}

		$currentSlot = date('Y-m-d H:i');
		$lastRunSlot = (string)$this->state->get(
			$this->stateKey($jobName, self::POLICY_TYPE, 'last_run_slot', $this->getPolicyId()),
			''
		);

		if ($lastRunSlot === $currentSlot) {
			$this->setReason('Skip (cron slot already handled)');
			return false;
		}

		return true;
	}

	public function markRun(string $jobName) {
		$this->state->set(
			$this->stateKey($jobName, self::POLICY_TYPE, 'last_run_slot', $this->getPolicyId()),
			date('Y-m-d H:i')
		);
	}

	public function getSchema(): array {
		return [
			'type' => 'object',
			'required' => ['expression'],
			'properties' => [
				'expression' => [
					'type' => 'string',
					'description' => 'Cron expression with five fields: minute hour dayOfMonth month dayOfWeek.'
				],
				'id' => [
					'type' => 'string',
					'description' => 'Optional policy instance id.'
				]
			]
		];
	}

	private function matches(int $value, string $expression, int $min, int $max): bool {
		foreach (explode(',', $expression) as $part) {
			if ($this->matchesPart($value, trim($part), $min, $max)) {
				return true;
			}
		}

		return false;
	}

	private function matchesPart(int $value, string $part, int $min, int $max): bool {
		if ($part === '*') {
			return true;
		}

		$step = 1;
		if (str_contains($part, '/')) {
			[$part, $stepRaw] = explode('/', $part, 2);
			$step = max(1, (int)$stepRaw);
		}

		if ($part === '*') {
			return (($value - $min) % $step) === 0;
		}

		if (str_contains($part, '-')) {
			[$fromRaw, $toRaw] = explode('-', $part, 2);
			$from = max($min, (int)$fromRaw);
			$to = min($max, (int)$toRaw);

			return $value >= $from && $value <= $to && (($value - $from) % $step) === 0;
		}

		return $value === (int)$part;
	}
}
