<?php

namespace Worker;

use Api\IOutput;

class TestJob implements IOutput {

	private $servicelocator;
	private $classmap;

	public function __construct($cnf = null) {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->classmap = $this->servicelocator->get('classmap');

		if (php_sapi_name() == "cli") {
			$options = getopt("", array("job:"));
		        if (isset($options["job"])) $_REQUEST["job"] = $_GET["job"] = $options["job"];
		}
	}

	// Implementation of IBase

	public function getName() {
		return "testjob";
	}

	// Implementation of IOutput

	public function getOutput($out = "html") {

		if (!isset($_REQUEST["job"])) {
			return '<p>Bitte einen Job über den Parameter &quot;job&quot; angeben.</p>';
			return;
		}

		$job = $this->classmap->getInstanceByInterfaceName('Worker\\Api\\IJob', $_REQUEST["job"]);
		$res = $job->go();

		return '<p>' . $res . '</p>';
	}

	public function getHelp() {
		return 'Help of TestJob' . "\n";
	}

}
