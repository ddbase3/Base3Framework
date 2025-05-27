<?php declare(strict_types=1);

namespace Base3\Xrm\Master;

use Base3\Core\ServiceLocator;
use Base3\Xrm\AbstractXrmFilterModule;

class MasterXrmFilterModule extends AbstractXrmFilterModule {

	private $servicelocator;
	private $xrms;
	private $logger;

	private $filterlist;

	public function __construct() {
		$this->servicelocator = ServiceLocator::getInstance();
		$this->xrms = $this->getXrms();
		$this->logger = $this->servicelocator->get('logger');

		$this->filterlist = array("archive", "name", "owner", "created", "changed", "tag");
	}

	// Implementation of IBase

	public static function getName(): string {
		return "masterxrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return in_array($filter->attr, $this->filterlist)
			&& get_class($xrm) == \Core\Xrm\Master\MasterXrm::class ? 2 : 0;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {
		$entries = array();

		if (in_array($filter->attr, $this->filterlist)) {

			$ids = array();
			$this->xrms = $this->getXrms();
			foreach ($this->xrms as $name => $xrmclosure) {
				$x = $xrmclosure();
				$f = new \Base3\Xrm\XrmFilter($filter->attr, $filter->op, $filter->val);
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
