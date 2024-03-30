<?php

namespace Xrm;

use Xrm\Api\IXrmFilterModule;

class LogXrmFilterModule implements IXrmFilterModule {

	// Implementation of IBase

	public function getName() {
		return "logxrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return in_array($filter->attr, array("owner", "created", "changed")) ? 1 : 0;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {
		$entries = array();
		return $entries;
	}

}
