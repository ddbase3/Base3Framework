<?php

namespace Worker;

use Api\IOutput;

class MasterWorker implements IOutput {

	private $servicelocator;
	private $workers;
	private $logger;

	public function __construct($cnf = null) {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->workers = $this->servicelocator->get('workers');
		$this->logger = $this->servicelocator->get('logger');
	}

	// Implementation of IBase

	public function getName() {
		return "masterworker";
	}

	// Implementation of IOutput

	public function getOutput($out = "html") {

		$tm0 = microtime(true);

		if (false) {
			echo "Pause.\n";
			sleep(10);
			return '';
		}

////////////////////////////////////////////////////////////////////////////////////////////////////////////

		$joblist = array();

		foreach ($this->workers as $workername => $worker) {
			$o = $worker();
			if (!$o->isActive()) continue;
			$jobs = $o->getJobs();
			foreach ($jobs as $job) {
				if (!$job["active"]) continue;
				for ($i = 0; $i < $o->getPriority() * $job["priority"]; $i++)
					$joblist[] = array(
						"workername" => $workername,
						"worker" => $o,
						"job" => $job["name"],
					);
			}
		}

		shuffle($joblist);

		foreach ($joblist as $job) {
			$tj0 = microtime(true);

			$res = $job["worker"]->doJob($job["job"]);

			$tj1 = microtime(true) - $tj0;
			$str = $job["workername"] . " | " . $job["job"] . " | Laufzeit: " . number_format($tj1, 3, ",", ".") . " Sek. | " . $res;
			echo $str . "\n";
			$this->logger->log("masterworker", $str);

			// usleep(300000); // 500ms
			// sleep(1);	// 1s
			sleep(3);	// 3s
		}

////////////////////////////////////////////////////////////////////////////////////////////////////////////
/*
// alt
		$joblist = array();
		foreach ($this->workers as $workername => $worker) {
			$o = $worker();
			$jobs = $o->getJobs();
			foreach ($jobs as $job) $joblist[] = array(
				"workername" => $workername,
				"worker" => $o,
				"job" => $job["name"],
				"active" => $job["active"],
				"priority" => $job["priority"]
			);
		}
		foreach ($joblist as $job) {
			if (!$job["active"]) continue;
			for ($i = 0; $i < $job["priority"]; $i++) {

				$tj0 = microtime(true);

				$res = $job["worker"]->doJob($job["job"]);

				$tj1 = microtime(true) - $tj0;
				$str = $job["workername"] . " | " . $job["job"] . " | Laufzeit: " . number_format($tj1, 3, ",", ".") . " Sek. | " . $res;
				echo $str . "\n";
				$this->logger->log("masterworker", $str);

				// usleep(500000); // 500ms
				// sleep(1);	// 1s
			}
		}
*/
////////////////////////////////////////////////////////////////////////////////////////////////////////////

		$tm1 = microtime(true) - $tm0;
		$str = "Laufzeit: " . number_format($tm1, 3, ",", ".") . " Sek.";
		echo $str . "\n";
		$this->logger->log("masterworker", $str);

	}

	public function getHelp() {
		return 'Help of MasterWorker' . "\n";
	}

}
