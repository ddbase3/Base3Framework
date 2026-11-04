<?php declare(strict_types=1);

namespace Base3\Test\Worker\Policy\Logic;

use Base3\Worker\Policy\Logic\AbstractLogicalJobExecutionPolicy;

final class InspectableLogicalJobPolicyFixture extends AbstractLogicalJobExecutionPolicy {

	public static function getName(): string { return 'inspectablelogicaljobpolicyfixture'; }
	public function shouldRun(string $jobName) { return count($this->policies) > 0; }
	public function countPolicies(): int { return count($this->policies); }
	public function exposeIsList(array $items): bool { return $this->isList($items); }
	public function exposeDefinitions(array $items): array { return $this->extractPolicyDefinitions($items); }
}
