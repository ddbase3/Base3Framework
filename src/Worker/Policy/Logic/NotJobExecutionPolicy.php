<?php declare(strict_types=1);

namespace Base3\Worker\Policy\Logic;

use Base3\Worker\Api\IJobExecutionPolicy;

final class NotJobExecutionPolicy extends AbstractLogicalJobExecutionPolicy {

	private ?IJobExecutionPolicy $policy = null;

	public static function getName(): string {
		return 'notjobexecutionpolicy';
	}

	public function setData(array $data) {
		parent::setData($data);

		$definition = $data['item'] ?? $data;
		$this->policy = is_array($definition) ? $this->createPolicy($definition) : null;
	}

	public function shouldRun(string $jobName) {
		if ($this->policy === null) {
			return true;
		}

		if ($this->policy->shouldRun($jobName)) {
			$this->setReason('Skip (negated policy allowed execution)');
			return false;
		}

		return true;
	}

	public function markRun(string $jobName) {}

	public function getSchema(): array {
		return [
			'type' => 'object',
			'required' => ['policy'],
			'properties' => [
				'policy' => [
					'type' => 'string',
					'description' => 'Policy name.'
				],
				'data' => [
					'type' => 'object',
					'description' => 'Policy configuration data.'
				]
			]
		];
	}
}
