<?php

namespace Xrm;

use Xrm\AbstractXrmFilterModule;

class AllocXrmFilterModule extends AbstractXrmFilterModule {

	protected $servicelocator;
	protected $logger;

	public function __construct() {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->logger = $this->servicelocator->get('logger');
	}

	// Implementation of IBase

	public function getName() {
		return "allocxrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return $filter->attr == "alloc" ? 1 : 0;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {
		$entries = array();

		if ($filter->attr == "alloc" && $filter->op == "with") {

			$id = is_object($filter->val) ? $filter->val->id : ( is_array($filter->val) ? $filter->val["id"] : $filter->val );
			$ids = $xrm->getAllocIds($id);
			if ($ids != null && sizeof($ids)) {
/*
				$ids = $this->sliceList($ids, $filter->offset, $filter->limit);
				$entries = $idsonly ? $ids : $xrm->getEntries($ids);
*/
				$entries = $idsonly
					? $ids
					: $this->sliceList($xrm->getEntries($ids), $filter->offset, $filter->limit);
			}

		} else if ($filter->attr == "alloc" && $filter->op == "without") {

			$all = $xrm->getAllEntryIds();
			$f = new \Xrm\XrmFilter("alloc", "with", $filter->val);
			$es = $this->getEntries($xrm, $f, true);
			$ids = array_diff($all, $es);

/*
			$ids = $this->sliceList($ids, $filter->offset, $filter->limit);
			$entries = $idsonly ? $ids : $xrm->getEntries($ids);
*/
			$entries = $idsonly
				? $ids
				: $this->sliceList($xrm->getEntries($ids), $filter->offset, $filter->limit);


		}

		return $entries;
	}

}
