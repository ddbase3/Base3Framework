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

use Base3\Api\IOutput;
use Base3\Logger\Api\ILogger;

class MasterWorker implements IOutput {

	private $lockHandle = null;

	public function __construct(
		private readonly ILogger $logger,
		private array $workers
	) {}

	// Implementation of IBase

	public static function getName(): string {
		return "masterworker";
	}

	// Implementation of IOutput

	public function getOutput(string $out = 'html', bool $final = false): string {

		if (!$this->acquireLock()) {
			$str = "MasterWorker skipped: lock is already held.";
			echo $str . "\n";
			$this->logger->info($str, ['scope' => 'masterworker']);
			return $str;
		}

		try {
			$tm0 = microtime(true);

			if (false) {
				echo "Paused.\n";
				sleep(10);
				return '';
			}

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
				$str = $job["workername"] . " | " . $job["job"] . " | Runtime: " . number_format($tj1, 3, ",", ".") . " sec. | " . $res;
				echo $str . "\n";
				$this->logger->info($str, ['scope' => 'masterworker']);

				sleep(3);
			}

			$tm1 = microtime(true) - $tm0;
			$str = "Runtime: " . number_format($tm1, 3, ",", ".") . " sec.";
			echo $str . "\n";
			$this->logger->info($str, ['scope' => 'masterworker']);

			return $str;
		} finally {
			$this->releaseLock();
		}
	}

	// Private methods

	private function acquireLock(): bool {
		$file = DIR_TMP . 'masterworker.lock';

		$handle = fopen($file, 'c');
		if ($handle === false) return false;

		if (!flock($handle, LOCK_EX | LOCK_NB)) {
			fclose($handle);
			return false;
		}

		$this->lockHandle = $handle;

		ftruncate($this->lockHandle, 0);
		fwrite($this->lockHandle, getmypid() . "\n");
		fwrite($this->lockHandle, date("Y-m-d H:i:s") . "\n");
		fflush($this->lockHandle);

		return true;
	}

	private function releaseLock(): void {
		if ($this->lockHandle == null) return;

		flock($this->lockHandle, LOCK_UN);
		fclose($this->lockHandle);

		$this->lockHandle = null;
	}
}
