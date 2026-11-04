<?php declare(strict_types=1);

namespace Base3\Test\Xrm;

use Base3\Xrm\XrmFilter;

final class FilterTestXrm {

	public array $allIds = ['a', 'b', 'c', 'd'];
	public array $matchingIds = ['a', 'c'];
	public array $allocations = ['group' => ['a','c']];
	public ?string $userId = 'group';
	public ?XrmFilter $lastFilter = null;
	public ?string $lastAlloc = null;

	public function getAllEntryIds(): array { return $this->allIds; }
	public function getEntries(array $ids): array {
		$items = [];
		foreach ($ids as $id) $items[] = 'entry:' . $id;
		return $items;
	}
	public function getEntriesIntern(XrmFilter $filter, bool $idsonly = false): array {
		$this->lastFilter = $filter;
		return $idsonly ? $this->matchingIds : $this->getEntries($this->matchingIds);
	}
	public function getXrmEntryIds(string $name, bool $invert = false): array {
		return $invert ? ['b', 'd'] : ['a', 'c'];
	}
	public function getAllocIds(string $alloc): array {
		$this->lastAlloc = $alloc;
		return $this->allocations[$alloc] ?? [];
	}
	public function getUserEntryId(): ?string { return $this->userId; }
}
