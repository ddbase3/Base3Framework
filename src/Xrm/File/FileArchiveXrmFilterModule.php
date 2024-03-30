<?php

namespace Xrm\File;

use Xrm\Api\IXrmFilterModule;

class FileArchiveXrmFilterModule implements IXrmFilterModule {

	// Implementation of IBase

	public function getName() {
		return "filearchivexrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return $filter->attr == "archive" && get_class($xrm) == "Xrm\\File\\FileXrm" ? 2 : 0;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {
		// FileXrm nutzt den Cache, daher müssen hier keine Einträge geliefert werden
		$entries = array();
		return $entries;
	}

}
