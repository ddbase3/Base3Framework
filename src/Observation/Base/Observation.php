<?php

namespace Observation\Base;

use Observation\Api\IObservation;

class Observation implements IObservation {

	private $classmap;

	public function __construct() {
		$servicelocator = \Base3\ServiceLocator::getInstance();
		$this->classmap = $servicelocator->get('classmap');
	}

	// Implementation of IObservation

	public function addObserver($name, $observer) {
		$data = $this->getData($name);
		$data[] = $observer->getName();
		$result = array_unique($data);
		$this->setData($name, $result);
	}

	public function removeObserver($name, $observer) {
		$data = $this->getData($name);
		$result = array_diff($data, array($observer->getName()));
		$this->setData($name, $result);
	}

	public function notifyObservers($name, $notificationType = null, $notificationObject = null) {
		$data = $this->getData($name);
		foreach ($data as $name) {
			$observer = $this->classmap->getInstanceByInterfaceName('Observation\\Api\\IObserver', $name);
			if ($observer != null) $observer->notify($notificationType, $notificationObject);
		}
	}

	// private functions

	private function getData($name) {
		$filename = DIR_LOCAL . "Observation" . DIRECTORY_SEPARATOR . $name . ".json";
		if (!file_exists($filename)) return array();
		$json = file_get_contents($filename);
		return json_decode($json, true);
	}

	private function setData($name, $data) {
		$filename = DIR_LOCAL . "Observation" . DIRECTORY_SEPARATOR . $name . ".json";
		if (!sizeof($data) && file_exists($filename)) {
			unlink($filename);
			return;
		}
		$fp = fopen($filename, "w");
		fwrite($fp, json_encode($data));
		fclose($fp);
	}

}
