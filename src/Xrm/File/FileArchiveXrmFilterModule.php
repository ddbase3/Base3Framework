<?php declare(strict_types=1);

namespace Base3\Xrm\File;

use Base3\Xrm\Api\IXrmFilterModule;

class FileArchiveXrmFilterModule implements IXrmFilterModule {

	// Implementation of IBase

	public function getName() {
		return "filearchivexrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return $filter->attr == "archive" && get_class($xrm) == "Base3\\Xrm\\File\\FileXrm" ? 2 : 0;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {
		// FileXrm nutzt den Cache, daher müssen hier keine Einträge geliefert werden
		$entries = array();
		return $entries;
	}

}
