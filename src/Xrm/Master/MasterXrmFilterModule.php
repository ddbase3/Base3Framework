<?php

namespace Xrm\Master;

use Xrm\AbstractXrmFilterModule;

class MasterXrmFilterModule extends AbstractXrmFilterModule {

	private $servicelocator;
	private $xrms;
	private $logger;

	private $filterlist;

	public function __construct() {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->xrms = $this->getXrms();
		$this->logger = $this->servicelocator->get('logger');

		$this->filterlist = array("archive", "name", "owner", "created", "changed", "tag");
	}

	// Implementation of IBase

	public function getName() {
		return "masterxrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return in_array($filter->attr, $this->filterlist)
			&& get_class($xrm) == "Xrm\\Master\\MasterXrm" ? 2 : 0;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {
		$entries = array();

		if (in_array($filter->attr, $this->filterlist)) {

			$ids = array();
			$this->xrms = $this->getXrms();
			foreach ($this->xrms as $name => $xrmclosure) {
				$x = $xrmclosure();
				$f = new \Xrm\XrmFilter($filter->attr, $filter->op, $filter->val);
				$es = $x->getEntriesIntern($f, true);

				if ($es != null && sizeof($es)) $ids = array_merge($ids, $es);
			}
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

	// Private functions

	private function getXrms() {
		// funktioniert nicht anders bei Closures
		return $this->servicelocator->get('xrms');
	}

}
