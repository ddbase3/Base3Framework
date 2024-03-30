<?php

namespace Xrm;

use Api\IOutput;

class XrmSearchService implements IOutput {

	private $servicelocator;

	public function __construct() {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
	}

	// Implementation of IBase

	public function getName() {
		return "xrmsearchservice";
	}

	// Implementation of IOutput

	public function getOutput($out = "html") {

		if (!isset($_REQUEST["q"])) return null;

		$scope = isset($_REQUEST["x"]) && $_REQUEST["x"] == "global" ? 'xrmglobal' : 'xrm';
		$xrm = $this->servicelocator->get($scope);

		$filter = new \Xrm\XrmFilter;
		$filter->fromJson($_REQUEST["q"]);

		$format = isset($_REQUEST["fo"]) ? $_REQUEST["fo"] : "ids";  // ids | data | teaser | display
		switch ($format) {

			case "ids":
				$entries = $xrm->getEntriesIntern($filter, 1);
				return json_encode($entries);

			case "data":
				$entries = $xrm->getFilteredEntries($filter);
				return json_encode($entries);

			case "teaser":
			case "display":
				$entries = $xrm->getFilteredEntries($filter);
				$entrydisplays = array();
				foreach ($entries as $entry) {
					$display = new \Custom\Display\XrmEntryDisplay;
					$display->setData(array("xrmentry" => $entry, "teaser" => $format != "display"));
					$entrydisplays[] = $display->getOutput();
				}
				return '<div>' . implode("\n", $entrydisplays) . '</div>';

		}

		return null;
	}

	public function getHelp() {
		return 'Help of XrmSearchService' . "\n";
	}

}
