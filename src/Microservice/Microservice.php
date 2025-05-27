<?php declare(strict_types=1);

namespace Base3\Microservice;

use Base3\Core\ServiceLocator;
use Base3\Api\IOutput;

class Microservice implements IOutput {

	private $servicelocator;

	public function __construct() {
		// could also be exported multiple times to different masters
		$this->servicelocator = ServiceLocator::getInstance();
	}

	// Implementation of IBase

	public static function getName(): string {
		return "microservice";
	}

	// Implementation of IOutput

	public function getOutput($out = "html") {
		if ($out != "json" || !isset($_REQUEST["call"])) return null;

		if ($_REQUEST["call"] == "connect") {
			$this->servicelocator->get('microservicehelper')->connect();
			return json_encode(array("result" => "done"));
		}

		return null;
	}

	public function getHelp() {
		return "Help on Microservice";
	}

}
