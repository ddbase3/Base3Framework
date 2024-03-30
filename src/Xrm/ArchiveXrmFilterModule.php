<?php

namespace Xrm;

use Xrm\AbstractXrmFilterModule;

class ArchiveXrmFilterModule extends AbstractXrmFilterModule {

	// Implementation of IBase

	public function getName() {
		return "archivexrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return $filter->attr == "archive" ? 1 : 0;
	}

	// TODO
	// Hilfs-DB (Datei-basiert) im Master (oder Cache?) fragen, welche Entry-IDs so gesetzt sind (File-XRM und Master-XRM nutzt dies, BASE3-XRM nicht)

	public function getEntries($xrm, $filter, $idsonly = false) {
		$entries = array();

		if ($filter->attr == "archive" && (
				$filter->op == "eq" && $filter->val == "undef"
				|| $filter->op == "neq" && $filter->val != "undef")) {
			$ids = $xrm->getAllEntryIds();
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
