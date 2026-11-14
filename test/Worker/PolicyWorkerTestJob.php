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

namespace Base3\Test\Worker;

use Base3\CoreFoundation\Worker\Api\IJobExecutionPolicy;
use Base3\CoreFoundation\Worker\Api\IPolicyControlledJob;

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
