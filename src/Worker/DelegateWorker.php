<?php

namespace Worker;

use Worker\Api\IWorker;
use Api\ICheck;

class DelegateWorker implements IWorker, ICheck {

	private $servicelocator;
	private $classmap;
	private $configuration;

	private $active;
	private $priority;

	public function __construct($cnf = null) {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->classmap = $this->servicelocator->get('classmap');
		$this->configuration = $this->servicelocator->get('configuration');

		$this->active = true;
		$this->priority = 1;

		if ($cnf == null && $this->configuration != null) $cnf = $this->configuration->get('worker');
		if ($cnf != null) {
			if (isset($cnf["active"])) $this->active = !!$cnf["active"];
			if (isset($cnf["priority"])) $this->priority = intval($cnf["priority"]);
		}
	}

	// Implementation of IBase

	public function getName() {
		return "delegateworker";
	}

	// Implementation of IWorker

	public function isActive() {
		return $this->active;
	}

	public function getPriority() {
		return $this->priority;
	}

	public function getJobs() {
		$joblist = array();
		$jobs = $this->classmap->getInstancesByInterface("Worker\\Api\\IJob");
		foreach ($jobs as $job) {
			$name = $job->getName();
			$priority = $job->getPriority();
			$joblist[] = array("name" => $name, "active" => $job->isActive(), "priority" => $priority);
		}
		return $joblist;
	}

	public function doJob($job) {
		$job = $this->classmap->getInstanceByInterfaceName('Worker\\Api\\IJob', $job);
		if ($job == null) return null;
		if (($job instanceof \Worker\Api\ICron) && !$this->checkCron($job)) return null;
		return $job->go();
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->classmap == null ? "Fail" : "Ok"
		);
	}

	// Private methods

	private function checkCron($job) {
		$t = date("Y-m-d H:i:s");
		$file = DIR_LOCAL . "/Worker/" . $job->getName() . ".txt";

		if (!file_exists($file)) {
			$fp = fopen($file, "w");
			fwrite($fp, $t);
			fclose($fp);
			return false;
		}

		$tl = file_get_contents($file);
		$tc = $job->getTimeCode();
		$tn = $this->getNextExecution($tl, $tc);
		if ($tn > $t) return false;

		$fp = fopen($file, "w");
		fwrite($fp, $t);
		fclose($fp);
		return true;
	}

	private function getNextExecution($tl, $tc) {
		$tx = str_replace([" ", ":"], "-", $tl);
		$td = array_map("intval", explode("-", $tx));

		$d = \Util\Chronos\Chronos::create($td[0], $td[1], $td[2], $td[3], $td[4], $td[5]);

		// seconds
		if ($d->getSecond() != 0) {
			$d->addMinutes(1);
			$d->setSecond(0);
		}		

		// minutes
		if (is_int($tc[0])) {  // ganzzahlig
			if ($d->getMinute() > $tc[0]) $d->addHours(1);
			$d->setMinute($tc[0]);
		}

		// hours
		if (is_int($tc[1])) {  // ganzzahlig
			if ($d->getHour() > $tc[1]) $d->addDays(1);
			$d->setHour($tc[1]);
		}

		// days
		if (is_int($tc[2])) {  // ganzzahlig
			if ($d->getDay() > $tc[2]) $d->addMonth(1);
			$d->setDay($tc[2]);
		}

		// months
		if (is_int($tc[3])) {  // ganzzahlig
			if ($d->getMonth() > $tc[3]) $d->addYear(1);
			$d->setMonth($tc[3]);
		}

		// TODO Wochentage
		// TODO Aufzählungen, Bruch-Angaben

		return $d->format("Y-m-d H:i:s");
	}
}
