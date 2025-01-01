<?php declare(strict_types=1);

namespace Xrm;

use Xrm\AbstractXrmFilterModule;

class AssignXrmFilterModule extends AbstractXrmFilterModule {

	protected $servicelocator;

	public function __construct() {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
	}

	// Implementation of IBase

	public function getName() {
		return "assignxrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return $filter->attr == "assigned" ? 1 : 0;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {
		$entries = array();

		if ($filter->attr == "assigned" && $filter->op == "to" && $filter->val == "user") {

			$id = $xrm->getUserEntryId();
			if ($id != null) {
				$ids = $xrm->getAllocIds($id);
				if ($ids != null && sizeof($ids)) {
					$entries = $idsonly
						? $ids
						: $this->sliceList($xrm->getEntries($ids), $filter->offset, $filter->limit);
				}
			}

		}

		return $entries;
	}

}
