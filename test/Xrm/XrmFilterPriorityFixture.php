<?php declare(strict_types=1);

namespace Base3\Test\Xrm;

use Base3\Xrm\Api\IXrmFilterModule;

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
