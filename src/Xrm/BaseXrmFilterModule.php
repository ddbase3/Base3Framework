<?php

namespace Xrm;

use Xrm\Api\IXrmFilterModule;

class BaseXrmFilterModule implements IXrmFilterModule {

	// Implementation of IBase

	public function getName() {
		return "basexrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return $filter == null || in_array($filter->attr, array("ids", "app", "xrm")) ? 1 : 0;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {
// TODO LIMIT !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
		$entries = array();

		if ($filter == null) {

			$ids = $xrm->getAllEntryIds();
			$entries = $idsonly ? $ids : $xrm->getEntries($ids);

		} else if ($filter->attr == "ids" && $filter->op == "in") {  // $filter->val ist Array mit IDs

			$entries = $idsonly ? $filter->val : $xrm->getEntries($filter->val);

		} else if ($filter->attr == "ids" && $filter->op == "notin") {  // $filter->val ist Array mit IDs

			$all = $xrm->getAllEntryIds();
			$ids = array_diff($all, $filter->val);
			$entries = $idsonly ? $ids : $xrm->getEntries($ids);

		} else if ($filter->attr == "app" && $filter->op == "eq") {  // $filter->val ist String

			$f = new \Xrm\XrmFilter("alloc", "with", "xxap" . strtolower($filter->val));
			$entries = $xrm->getEntriesIntern($f, $idsonly);

		} else if ($filter->attr == "app" && $filter->op == "neq") {  // $filter->val ist String

			$all = $xrm->getAllEntryIds();
			$f = new \Xrm\XrmFilter("app", "eq", $filter->val);
			$es = $this->getEntries($xrm, $f, true);
			$ids = array_diff($all, $es);
			$entries = $idsonly ? $ids : $xrm->getEntries($ids);

		} else if ($filter->attr == "xrm" && $filter->op == "eq") {  // $filter->val ist String

			$ids = $xrm->getXrmEntryIds($filter->val);
			$entries = $idsonly ? $ids : $xrm->getEntries($ids);

		} else if ($filter->attr == "xrm" && $filter->op == "neq") {  // $filter->val ist String

			$ids = $xrm->getXrmEntryIds($filter->val, true);
			$entries = $idsonly ? $ids : $xrm->getEntries($ids);

		}

		return $entries;
	}

}
