<?php

namespace Adviser\SimpleNeuralNetwork;

use Adviser\Api\IAdviser;

class SimpleNeuralNetwork implements IAdviser {

	private $servicelocator;
	private $database;

	public function __construct() {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->database = $this->servicelocator->get('database');
	}

	// Implementation of IBase

	public function getName() {
		return "simpleneuralnetwork";
	}

}
