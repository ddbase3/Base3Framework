<?php declare(strict_types=1);

namespace Base3\Test\Xrm\Master;

use Base3\Xrm\Cache\CacheXrm;
use Base3\Xrm\XrmFilter;

final class MasterRecordingXrm extends CacheXrm {

	public array $calls = [];
	public array $allIds = [];
	public array $filteredIds = [];
	public array $allocations = [];
	public array $entryBatch = [];
	public ?object $entry = null;
	public bool $deleteResult = true;
	public bool $mutationResult = true;
	public ?string $userEntryId = null;

	public function __construct(public string $name) {}
	public function getXrmName() { return $this->name; }
	public function delEntry($id, $moveonly = false) { $this->calls[] = ['delEntry',$id,$moveonly]; return $this->deleteResult; }
	public function setEntry($entry) {
		$this->calls[] = ['setEntry',$entry];
		$new = (object) (array) $entry;
		$new->id = $new->id ?: 'generated';
		$new->xrmnames = [$this->name];
		return $new;
	}
	public function getEntry($id) { $this->calls[] = ['getEntry',$id]; return $this->entry; }
	public function getEntries($ids) { $this->calls[] = ['getEntries',$ids]; return $this->entryBatch; }
	public function getAllEntryIds() { $this->calls[] = ['getAllEntryIds']; return $this->allIds; }
	public function getXrmEntryIds($name, $invert = false) { $this->calls[] = ['getXrmEntryIds',$name,$invert]; return $this->filteredIds; }
	public function getAllocIds($id) { $this->calls[] = ['getAllocIds',$id]; return $this->allocations; }
	public function getEntriesIntern($filter, $idsonly = false) {
		$this->calls[] = ['getEntriesIntern',$filter,$idsonly];
		return $this->filteredIds;
	}
	public function getUserEntryId() { return $this->userEntryId; }
	public function addTag($id,$tag) { $this->calls[] = ['addTag',$id,$tag]; return $this->mutationResult; }
	public function removeTag($id,$tag) { $this->calls[] = ['removeTag',$id,$tag]; return $this->mutationResult; }
	public function addApp($id,$app) { $this->calls[] = ['addApp',$id,$app]; return $this->mutationResult; }
	public function removeApp($id,$app) { $this->calls[] = ['removeApp',$id,$app]; return $this->mutationResult; }
	public function addAlloc($a,$b) { $this->calls[] = ['addAlloc',$a,$b]; return $this->mutationResult; }
	public function removeAlloc($a,$b) { $this->calls[] = ['removeAlloc',$a,$b]; return $this->mutationResult; }
}
