<?php declare(strict_types=1);

namespace Base3\Worker\Policy\Logic;

use Base3\Api\IClassMap;
use Base3\Worker\Api\IJobExecutionPolicy;
use Base3\Worker\Policy\AbstractJobExecutionPolicy;

abstract class AbstractLogicalJobExecutionPolicy extends AbstractJobExecutionPolicy {

	/**
	 * @var array<int, IJobExecutionPolicy>
	 */
	protected array $policies = [];

	public function __construct(
		protected readonly IClassMap $classMap
	) {}

	public function setData(array $data) {
		parent::setData($data);
		$this->policies = [];

		foreach ($this->extractPolicyDefinitions($data) as $definition) {
			$policy = $this->createPolicy($definition);
			if ($policy !== null) {
				$this->policies[] = $policy;
			}
		}
	}

	protected function createPolicy(array $definition): ?IJobExecutionPolicy {
		$name = $definition['policy'] ?? null;
		if (!is_scalar($name) || trim((string)$name) === '') {
			return null;
		}

		$policy = $this->classMap->getInstanceByInterfaceName(
			IJobExecutionPolicy::class,
			(string)$name
		);

		if (!$policy instanceof IJobExecutionPolicy) {
			return null;
		}

		$policyData = $definition['data'] ?? [];
		$policy->setData(is_array($policyData) ? $policyData : []);

		return $policy;
	}

	protected function extractPolicyDefinitions(array $data): array {
		if (isset($data['items']) && is_array($data['items'])) {
			return $data['items'];
		}

		if ($this->isList($data)) {
			return $data;
		}

		return [];
	}

	protected function isList(array $array): bool {
		$i = 0;
		foreach ($array as $key => $_) {
			if ($key !== $i) {
				return false;
			}
			$i++;
		}

		return true;
	}
}
