<?php

namespace Scriptlock\Custom;

use Scriptlock\Api\IScriptlock;
use Api\ICheck;

class ScriptlockCustom implements IScriptlock, ICheck {

	private $servicelocator;
	private $classmap;

	public function __construct() {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->classmap = $this->servicelocator->get('classmap');
		if ($this->check()) $this->lock();
	}

	// Implementation of IScriptlock

	public function check() {
		$conditions = $this->classmap->getInstancesByInterface("Scriptlock\\Api\\IScriptlockCondition");
		foreach ($conditions as $condition)
			if ($condition->activated() && $condition->check()) return true;
		return false;
	}

	public function lock() {
		// TODO execute list of IScriptlockExecution (ordered)
		exit;
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->servicelocator->get('classmap') == null ? "Fail" : "Ok"
		);
	}

}
