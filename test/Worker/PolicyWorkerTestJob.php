<?php declare(strict_types=1);

namespace Base3\Test\Worker;

use Base3\Worker\Api\IJobExecutionPolicy;
use Base3\Worker\Api\IPolicyControlledJob;

final class PolicyWorkerTestJob implements IPolicyControlledJob {
	public int $runs = 0;
	public ?IJobExecutionPolicy $assignedPolicy = null;
	public array $definition=[];
	public static function getName(): string { return 'policyworkertestjob'; }
	public function isActive() { return true; }
	public function getPriority() { return 1; }
	public function go() { $this->runs++;return 'executed'; }
	public function getPolicyDefinition(): array { return $this->definition; }
	public function setExecutionPolicy(IJobExecutionPolicy $policy): void { $this->assignedPolicy=$policy; }
	public function markRun(): void { if($this->assignedPolicy!==null)$this->assignedPolicy->markRun(self::getName()); }
}
