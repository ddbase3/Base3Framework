<?php

namespace Xrm;

use Xrm\Api\IXrmFilterModule;

class NameXrmFilterModule implements IXrmFilterModule {

	// Implementation of IBase

	public function getName() {
		return "namexrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return $filter->attr == "name" ? 1 : 0;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {
		$entries = array();
		return $entries;
	}

}
