<?php

namespace Xrm;

use Xrm\AbstractXrmFilterModule;

class ConjXrmFilterModule extends AbstractXrmFilterModule {

	protected $servicelocator;
	protected $logger;

	public function __construct() {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->logger = $this->servicelocator->get('logger');
	}

	// Implementation of IBase

	public function getName() {
		return "conjxrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return $filter->attr == "conj" ? 1 : 0;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {
		$entries = array();

		if ($filter->attr == "conj" && in_array($filter->op, array("or", "and", "not"))) {

			$ids = array();

			if ($filter->op == "or") {  // $filter->val ist Array von Filtern

				$idlists = array();
				foreach ($filter->val as $f) {
					$es = $xrm->getEntriesIntern($f, true);
					$idlists[] = $es != null && sizeof($es) ? $es : array();
				}
				$ids = $this->getUnion($idlists);

			} else if ($filter->op == "and") {  // $filter->val ist Array von Filtern

				$idlists = array();
				foreach ($filter->val as $f) {
					$es = $xrm->getEntriesIntern($f, true);
					$idlists[] = $es != null && sizeof($es) ? $es : array();
				}
				$ids = $this->getIntersection($idlists);

			} else if ($filter->op == "not") {  // $filter->val ist Filter

				$all = $xrm->getAllEntryIds();
				$es = $xrm->getEntriesIntern($filter->val, true);
				$ids = array_diff($all, $es);

			}

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

	// Private methods

	private function getIntersection($as) {
		if (sizeof($as) < 2) return $as[0];
		return array_values(array_intersect(... $as));
	}

	private function getUnion($as) {
		if (sizeof($as) < 2) return $as[0];
		return array_unique(array_merge(... $as));
	}

}
