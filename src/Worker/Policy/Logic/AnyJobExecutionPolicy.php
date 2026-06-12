<?php declare(strict_types=1);

namespace Base3\Worker\Policy\Logic;

use Base3\Worker\Api\IJobExecutionPolicy;

final class AnyJobExecutionPolicy extends AbstractLogicalJobExecutionPolicy {

	private ?IJobExecutionPolicy $matchedPolicy = null;

	public static function getName(): string {
		return 'anyjobexecutionpolicy';
	}

	public function shouldRun(string $jobName) {
		$this->matchedPolicy = null;
		$reasons = [];

		foreach ($this->policies as $policy) {
			if ($policy->shouldRun($jobName)) {
				$this->matchedPolicy = $policy;
				return true;
			}

			$reason = trim((string)$policy->getReason());
			if ($reason !== '') {
				$reasons[] = $reason;
			}
		}

		$this->setReason($reasons !== [] ? implode(' / ', $reasons) : 'Skip (no policy allowed execution)');
		return false;
	}

	public function markRun(string $jobName) {
		if ($this->matchedPolicy !== null) {
			$this->matchedPolicy->markRun($jobName);
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
