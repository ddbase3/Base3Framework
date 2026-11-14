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

namespace Base3\Test\Xrm\Master;

use Base3\Core\Xrm\Cache\CacheXrm;
use Base3\CoreFoundation\Xrm\XrmFilter;

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
