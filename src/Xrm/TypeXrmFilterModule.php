<?php

namespace Xrm;

use Xrm\Api\IXrmFilterModule;

class TypeXrmFilterModule implements IXrmFilterModule {

	// Implementation of IBase

	public function getName() {
		return "typexrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return $filter->attr == "type" ? 1 : 0;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {
		$entries = array();

		if ($filter->attr == "type" && $filter->op == "eq") {  // $filter->val ist String

			$f = new \Xrm\XrmFilter("alloc", "with", "xxty" . strtolower($filter->val), $filter->offset, $filter->limit);
			$entries = $xrm->getEntriesIntern($f, $idsonly);

		} else if ($filter->attr == "type" && $filter->op == "neq") {  // $filter->val ist String

			$all = $xrm->getAllEntryIds();
			$f = new \Xrm\XrmFilter("type", "eq", $filter->val, $filter->offset, $filter->limit);
			$es = $this->getEntries($xrm, $f, true);
			$ids = array_diff($all, $es);
			$entries = $idsonly ? $ids : $xrm->getEntries($ids);

		}

		return $entries;
	}

}
