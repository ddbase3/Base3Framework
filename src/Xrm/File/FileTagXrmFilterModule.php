<?php

namespace Xrm\File;

use Xrm\AbstractXrmFilterModule;

class FileTagXrmFilterModule extends AbstractXrmFilterModule {

	// Implementation of IBase

	public function getName() {
		return "filetagxrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return $filter->attr == "tag" && get_class($xrm) == "Xrm\\File\\FileXrm" ? 2 : 0;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {
		$entries = array();

		if ($filter->attr == "tag" && $filter->op == "conn") {

			$ids = $xrm->getAllocIds("xxta" . $filter->val);
			if ($ids != null && sizeof($ids)) {
/*
				$ids = $this->sliceList($ids, $filter->offset, $filter->limit);
				$entries = $idsonly ? $ids : $xrm->getEntries($ids);
*/
				$entries = $idsonly
					? $ids
					: $this->sliceList($xrm->getEntries($ids), $filter->offset, $filter->limit);
			}

		} else if ($filter->attr == "tag" && $filter->op == "notconn") {

			$all = $xrm->getAllEntryIds();
			$f = new \Xrm\XrmFilter("tag", "conn", $filter->val);
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
