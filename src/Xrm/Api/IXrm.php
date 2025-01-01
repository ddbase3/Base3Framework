<?php declare(strict_types=1);

namespace Xrm\Api;

interface IXrm {

	public function getXrmName();
	public function delEntry($id, $moveonly = false);
	public function setEntry($entry);
	public function getEntry($id);
	public function getEntries($ids);
	public function getAllocIds($id);
	public function getFilteredEntries($filter);
	public function getEntriesIntern($filter, $idsonly = false);
	public function getAllEntryIds();
	public function getXrmEntryIds($xrmname, $invert = false);
	public function setArchive($id, $archive);
	public function connect($id1, $id2);
	public function disconnect($id1, $id2);
	public function addTag($id, $tag);
	public function removeTag($id, $tag);
	public function addApp($id, $app);
	public function removeApp($id, $app);
	public function addAlloc($id1, $id2);
	public function removeAlloc($id1, $id2);
	public function getAccess($entry);
	public function getUserEntryId();

}
