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

namespace Base3\Worker;

use Base3\Api\ICheck;
use Base3\Api\IClassMap;
use Base3\Configuration\Api\IConfiguration;
use Base3\Util\Chronos\Chronos;
use Base3\Worker\Api\ICron;
use Base3\Worker\Api\IJob;
use Base3\Worker\Api\IWorker;

class DelegateWorker implements IWorker, ICheck {

	private bool $active = true;
	private int $priority = 1;

	public function __construct(
		private readonly IClassMap $classmap,
		private readonly IConfiguration $configuration
	) {
		$this->active = $this->configuration->getBool('worker', 'active', true);

		$priority = $this->configuration->getInt('worker', 'priority', 1);
		if ($priority > 0) $this->priority = $priority;
	}

	// Implementation of IBase

	public static function getName(): string {
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
		$jobs = $this->classmap->getInstancesByInterface(IJob::class);

		foreach ($jobs as $job) {
			$name = $job->getName();
			$priority = $job->getPriority();

			$joblist[] = array(
				"name" => $name,
				"active" => $job->isActive(),
				"priority" => $priority
			);
		}

		return $joblist;
	}

	public function doJob($job) {
		$job = $this->classmap->getInstanceByInterfaceName(IJob::class, $job);
		if ($job == null) return null;
		if (($job instanceof ICron) && !$this->checkCron($job)) return null;
		return $job->go();
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			'depending_services' => $this->classmap == null ? 'Fail' : 'Ok',
			'worker_active_' . ($this->active ? 'true' : 'false') => 'Ok',
			'worker_priority_' . $this->priority => 'Ok',
			'num_of_jobs_' . sizeof($this->getJobs()) => 'Ok'
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

		$d = Chronos::create($td[0], $td[1], $td[2], $td[3], $td[4], $td[5]);

		// Seconds
		if ($d->getSecond() != 0) {
			$d->addMinutes(1);
			$d->setSecond(0);
		}

		// Minutes
		if (is_int($tc[0])) {
			if ($d->getMinute() > $tc[0]) $d->addHours(1);
			$d->setMinute($tc[0]);
		}

		// Hours
		if (is_int($tc[1])) {
			if ($d->getHour() > $tc[1]) $d->addDays(1);
			$d->setHour($tc[1]);
		}

		// Days
		if (is_int($tc[2])) {
			if ($d->getDay() > $tc[2]) $d->addMonth(1);
			$d->setDay($tc[2]);
		}

		// Months
		if (is_int($tc[3])) {
			if ($d->getMonth() > $tc[3]) $d->addYear(1);
			$d->setMonth($tc[3]);
		}

		// TODO Weekdays
		// TODO Lists and interval expressions

		return $d->format("Y-m-d H:i:s");
	}
}
