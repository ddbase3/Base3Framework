<?php

namespace Search\Custom;

use Search\Api\ISearchProvider;

class CustomSearchProvider implements ISearchProvider {

	private $servicelocator;
	private $classmap;

	public function __construct($cnf = null) {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->classmap = $this->servicelocator->get('classmap');
	}

	// Implementation of IBase

	public function getName() {
		return "customsearchprovider";
	}

	// Implementation of IOutput

	public function getOutput($out = "html") {
		$result = array();

		$q = $_REQUEST["q"];

		$searchservices = $this->classmap->getInstancesByInterface("Search\\Api\\ISearchService");
		foreach ($searchservices as $searchservice)
			$result = array_merge($result, $searchservice->search($q));

		return json_encode($result);
	}

	public function getHelp() {
		return 'Help of CustomSearchProvider' . "\n";
	}

}
