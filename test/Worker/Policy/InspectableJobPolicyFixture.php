<?php declare(strict_types=1);

namespace Base3\Test\Worker\Policy;

use Base3\Worker\Policy\AbstractJobExecutionPolicy;

final class InspectableJobPolicyFixture extends AbstractJobExecutionPolicy {

	public static function getName(): string { return 'inspectablejobpolicyfixture'; }
	public function shouldRun(string $jobName) { return true; }
	public function readString(string $key, string $default=''): string { return $this->getString($key,$default); }
	public function readInt(string $key, int $default=0): int { return $this->getInt($key,$default); }
	public function readPolicyId(): ?string { return $this->getPolicyId(); }
	public function readStateKey(string $job,string $policy,string $key,?string $id=null): string { return $this->stateKey($job,$policy,$key,$id); }
	public function readSameDay(string $date): bool { return $this->isSameDay($date); }
	public function readTimeInWindow(string $now,string $from,string $to): bool { return $this->isTimeInWindow($now,$from,$to); }
	public function readNow(): string { return $this->nowSqlString(); }
	public function changeReason(string $reason): void { $this->setReason($reason); }
}
