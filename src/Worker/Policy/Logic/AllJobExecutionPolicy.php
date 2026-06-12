<?php declare(strict_types=1);

namespace Base3\Worker\Policy\Logic;

final class AllJobExecutionPolicy extends AbstractLogicalJobExecutionPolicy {

	public static function getName(): string {
		return 'alljobexecutionpolicy';
	}

	public function shouldRun(string $jobName) {
		foreach ($this->policies as $policy) {
			if (!$policy->shouldRun($jobName)) {
				$this->setReason($policy->getReason());
				return false;
			}
		}

		return true;
	}

	public function markRun(string $jobName) {
		foreach ($this->policies as $policy) {
			$policy->markRun($jobName);
		}
	}

	public function getSchema(): array {
		return [
			'type' => 'array',
			'items' => [
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
			]
		];
	}
}
