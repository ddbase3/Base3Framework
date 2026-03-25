<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of BASE3 Framework.
 *
 * BASE3 Framework is a lightweight, modular PHP framework for scalable
 * and maintainable web applications. Built for extensibility,
 * performance, and modern development, it can run standalone or
 * integrate as a subsystem within a host system.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/Base3Framework
 **********************************************************************/

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

	public function getOutput(string $out = 'html', bool $final = false): string {
		if ($out != "json" || !isset($_REQUEST["call"])) return null;

		if ($_REQUEST["call"] == "connect") {
			$this->servicelocator->get('microservicehelper')->connect();
			return json_encode(array("result" => "done"));
		}

		return null;
	}

	public function getHelp(): string {
		return "Help on Microservice";
	}
}
