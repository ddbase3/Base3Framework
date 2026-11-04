<?php declare(strict_types=1);

namespace Base3\Test\Xrm;

use Base3\Xrm\Api\IXrm;

/** A record-only IXrm test double, without persistence or any alternate XRM path. */
final class XrmMicroserviceRecorder implements IXrm {

	public array $calls = [];
	public array $responses = [];

	private function record(string $method, array $arguments): mixed {
		$this->calls[] = [$method, $arguments];
		return $this->responses[$method] ?? null;
	}

	public function getXrmName() { return $this->record(__FUNCTION__, []); }
	public function delEntry($id, $moveonly = false) { return $this->record(__FUNCTION__, [$id, $moveonly]); }
	public function setEntry($entry) { return $this->record(__FUNCTION__, [$entry]); }
	public function getEntry($id) { return $this->record(__FUNCTION__, [$id]); }
	public function getEntries($ids) { return $this->record(__FUNCTION__, [$ids]); }
	public function getAllocIds($id) { return $this->record(__FUNCTION__, [$id]); }
	public function getFilteredEntries($filter) { return $this->record(__FUNCTION__, [$filter]); }
	public function getEntriesIntern($filter, $idsonly = false) { return $this->record(__FUNCTION__, [$filter, $idsonly]); }
	public function getAllEntryIds() { return $this->record(__FUNCTION__, []); }
	public function getXrmEntryIds($xrmname, $invert = false) { return $this->record(__FUNCTION__, [$xrmname, $invert]); }
	public function setArchive($id, $archive) { return $this->record(__FUNCTION__, [$id, $archive]); }
	public function connect($id1, $id2) { return $this->record(__FUNCTION__, [$id1, $id2]); }
	public function disconnect($id1, $id2) { return $this->record(__FUNCTION__, [$id1, $id2]); }
	public function addTag($id, $tag) { return $this->record(__FUNCTION__, [$id, $tag]); }
	public function removeTag($id, $tag) { return $this->record(__FUNCTION__, [$id, $tag]); }
	public function addApp($id, $app) { return $this->record(__FUNCTION__, [$id, $app]); }
	public function removeApp($id, $app) { return $this->record(__FUNCTION__, [$id, $app]); }
	public function addAlloc($id1, $id2) { return $this->record(__FUNCTION__, [$id1, $id2]); }
	public function removeAlloc($id1, $id2) { return $this->record(__FUNCTION__, [$id1, $id2]); }
	public function getAccess($entry) { return $this->record(__FUNCTION__, [$entry]); }
	public function getUserEntryId() { return $this->record(__FUNCTION__, []); }
}
