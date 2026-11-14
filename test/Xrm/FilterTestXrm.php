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

use Base3\CoreFoundation\Xrm\XrmFilter;

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
