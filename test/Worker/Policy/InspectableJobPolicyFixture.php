<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of BASE3 Framework.
 *
 * BASE3 Framework is a lightweight, modular PHP framework for scalable
 * and maintainable web applications. Built for extensibility,
 * performance, and modern development, it can run standalone or
 * integrate as a subsystem within a host system.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/Base3Framework
 **********************************************************************/

namespace Base3\Test\Worker\Policy;

use Base3\CoreFoundation\Worker\Policy\AbstractJobExecutionPolicy;

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
