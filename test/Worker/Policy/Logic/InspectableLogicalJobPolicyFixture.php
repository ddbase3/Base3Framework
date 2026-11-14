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

namespace Base3\Test\Worker\Policy\Logic;

use Base3\CoreFoundation\Worker\Policy\Logic\AbstractLogicalJobExecutionPolicy;

final class InspectableLogicalJobPolicyFixture extends AbstractLogicalJobExecutionPolicy {

	public static function getName(): string { return 'inspectablelogicaljobpolicyfixture'; }
	public function shouldRun(string $jobName) { return count($this->policies) > 0; }
	public function countPolicies(): int { return count($this->policies); }
	public function exposeIsList(array $items): bool { return $this->isList($items); }
	public function exposeDefinitions(array $items): array { return $this->extractPolicyDefinitions($items); }
}
