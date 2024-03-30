<?php

namespace Xrm;

use Xrm\Api\IXrmFilterModule;

abstract class AbstractXrmFilterModule implements IXrmFilterModule {

	// Protected methods

	protected function sliceList($elements, $offset, $limit) {

/*
		usort($elements, function($a, $b) {
			if (substr($a, 0, 2) == "xx") return 1;
			if (substr($b, 0, 2) == "xx") return -1;
			return 0;
		});
*/

		if ($offset == null && $limit == null) return $elements;
		if ($limit == null) return array_slice($elements, intval($offset));
		return array_slice($elements, intval($offset), intval($limit));
	}

}
