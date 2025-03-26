<?php declare(strict_types=1);

namespace Base3\Xrm;

use Base3\Xrm\Api\IXrmFilterModule;

class TagXrmFilterModule implements IXrmFilterModule {

	// Implementation of IBase

	public function getName() {
		return "tagxrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return $filter->attr == "tag" ? 1 : 0;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {
		$entries = array();
		return $entries;
	}

}
