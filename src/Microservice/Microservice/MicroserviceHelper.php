<?php

namespace Microservice\Microservice;

use Microservice\Api\IMicroserviceHelper;
use Api\ICheck;

class MicroserviceHelper implements IMicroserviceHelper, ICheck {

	private $servicelocator;
	private $configuration;

	private $cnf;

	private $url;
	private $name;
	private $masterurl;
	private $filename;

	protected $internalonly;
	protected $binarystream;
	private $flags;

	public function __construct($flags = 0) {
		// could also be exported multiple times to different masters

		$this->flags = $flags;

		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->configuration = $this->servicelocator->get('configuration');

		if ($this->configuration == null) return;
		$this->cnf = $this->configuration->get();

		$this->url = $this->cnf["base"]["url"];
		$this->name = $this->cnf["microservice"]["name"];
		$this->masterurl = $this->cnf["microservice"]["masterurl"];
		$this->filename = DIR_TMP . "microservices.json";

		if (!file_exists($this->filename)) $this->connect();
	}

	// Implementation of IMicroserviceHelper

	public function connect() {
		$allservices = array();
		$services = $this->getMicroServiceList();

		if (strlen($this->masterurl)) {
			$msrec = "microservicereceiver";
			$url = $this->getUrl($this->masterurl, $msrec);
			$conn = new MicroserviceConnector($url, $this->getService($msrec, "Microservice\\Api\\IMicroserviceReceiver"));
			$allservices = $conn->connect($services);
		} else {
			$allservices = $services;
		}

		$fp = fopen($this->filename, "w");
		fwrite($fp, json_encode($allservices));
		fclose($fp);
	}

	public function set($services) {
		$servicedata = $this->getMicroServiceList();
		$servicedata = array_merge($servicedata, $services);
		$fp = fopen($this->filename, "w");
		fwrite($fp, json_encode($servicedata));
		fclose($fp);
		return $servicedata;
	}

	public function get($servicename, $interface, $name, $retry = false) {

		$serviceurl = $this->getServiceUrl($servicename);
		if ($serviceurl == null) {
			if ($retry) return null;
			$this->connect();
			return $this->get($servicename, $interface, $name, true);
		}
		$url = $this->getUrl($serviceurl, $name);

		$m = array();
		$methods = get_class_methods($interface);
		foreach ($methods as $method) {
			if ($method == "__construct") continue;

			$p = array();

			$rm = new \ReflectionMethod($interface, $method);
			$parameters = $rm->getParameters();
			foreach ($parameters as $parameter) $p[] = $parameter->name;
			$m[] = array("name" => $method, "params" => $p);
		}

		$service = array("name" => $name, "interfaces" => array($interface), "methods" => $m);

		return new MicroserviceConnector($url, $service, $this->flags);
	}

	public function reset() {
		if (file_exists($this->filename)) unlink($this->filename);
	}

	public function getMicroserviceList() {
		return strlen($this->filename) && file_exists($this->filename)
			? json_decode(file_get_contents($this->filename), true)
			: array($this->name => $this->url);
	}

	// private methods

	private function getServiceUrl($servicename) {
		$services = json_decode(file_get_contents($this->filename), true);
		if (!isset($services[$servicename])) return null;
		return $services[$servicename];
	}

	private function getUrl($url, $name) {
		return rtrim($url, "/") . "/" . $name . ".json";
	}

	private function getService($name, $interface) {
		$m = array();
		$methods = get_class_methods($interface);
		foreach ($methods as $method) {
			if ($method == "__construct") continue;

			$p = array();

			$rm = new \ReflectionMethod($interface, $method);
			$parameters = $rm->getParameters();
			foreach ($parameters as $parameter) $p[] = $parameter->name;
			$m[] = array("name" => $method, "params" => $p);
		}

		return array("name" => $name, "interfaces" => array($interface), "methods" => $m);
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->configuration == null ? "Fail" : "Ok"
		);
	}

}
