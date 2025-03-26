<?php declare(strict_types=1);

namespace Base3\Xrm;

use Base3\Core\ServiceLocator;
use Base3\Xrm\Api\IXrm;
use Base3\Microservice\AbstractMicroservice;

class DelegateXrmMicroservice extends AbstractMicroservice implements IXrm {

	private $servicelocator;
	private $xrm;

	public function __construct($cnf = null) {
		$this->servicelocator = ServiceLocator::getInstance();
		$this->xrm = $this->servicelocator->get('xrm');
	}

	// Implementation of IXrm

	public function getXrmName() {
		return $this->xrm->getXrmName();
	}

	public function delEntry($id, $moveonly = false) {
		return $this->xrm->delEntry($id, $moveonly);
	}

	public function setEntry($entry) {
		return $this->xrm->setEntry($entry);
	}

	public function getEntry($id) {
		return $this->xrm->getEntry($id);
	}

	public function getEntries($ids) {
		return $this->xrm->getEntries($ids);
	}

	public function getAllocIds($id) {
		return $this->xrm->getAllocIds($id);
	}

	public function getFilteredEntries($filter) {
		return $this->xrm->getFilteredEntries($filter);
	}

	public function getEntriesIntern($filter, $idsonly = false) {
		if (is_object($filter)) $filter = json_encode($filter);
		return $this->xrm->getEntriesIntern($filter, $idsonly);
	}

	public function getAllEntryIds() {
		return $this->xrm->getAllEntryIds();
	}

	public function getXrmEntryIds($xrmname, $invert = false) {
		return $this->xrm->getXrmEntryIds($xrmname, $invert);
	}

	public function setArchive($id, $archive) {
		return $this->xrm->setArchive($archive);
	}

	public function connect($id1, $id2) {
		return $this->xrm->connect($id1, $id2);
	}

	public function disconnect($id1, $id2) {
		return $this->xrm->disconnect($id1, $id2);
	}

	public function addTag($id, $tag) {
		return $this->xrm->addTag($id, $tag);
	}

	public function removeTag($id, $tag) {
		return $this->xrm->removeTag($id, $tag);
	}

	public function addApp($id, $app) {
		return $this->xrm->addApp($id, $app);
	}

	public function removeApp($id, $app) {
		return $this->xrm->removeApp($id, $app);
	}

	public function addAlloc($id1, $id2) {
		return $this->xrm->addAlloc($id1, $id2);
	}

	public function removeAlloc($id1, $id2) {
		return $this->xrm->removeAlloc($id1, $id2);
	}

	public function getAccess($entry) {
		return $this->xrm->getAccess($entry);
	}

	public function getUserEntryId() {
		return $this->xrm->getUserEntryId();
	}

}
