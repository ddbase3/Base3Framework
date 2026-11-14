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

namespace Base3\Test\Xrm;

use Base3\CoreFoundation\Xrm\Api\IXrmFilterModule;

final class XrmFilterPriorityFixture implements IXrmFilterModule {

	public int $matches = 0;
	public array $requests = [];
	public mixed $lastMatchFilter = null;

	public function __construct(public int $priority, public array $result = []) {}

	public static function getName(): string { return 'xrmfilterpriorityfixture'; }

	public function match($xrm, $filter) {
		$this->matches++;
		$this->lastMatchFilter = $filter;
		return $this->priority;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {
		$this->requests[] = [$xrm, $filter, $idsonly];
		return $this->result;
	}
}
