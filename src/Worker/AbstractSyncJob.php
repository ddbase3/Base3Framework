<?php

namespace Worker;

use Worker\Api\ICron;

abstract class AbstractSyncJob implements ICron {

	protected $servicelocator;
	protected $logger;

	protected $lists;
	protected $syncfile;
	protected $newsynctime;
	protected $failed;

	public function __construct() {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->logger = $this->servicelocator->get('logger');

		$this->lists = array();
		$this->syncfile = DIR_LOCAL . "/Sync/" . $this->getName() . ".txt";
		$this->failed = false;
	}

	// Implementation of IBase

	public function getName() {
		$ps = explode("\\", get_class($this));
		return strtolower(array_pop($ps));
	}

	// Implementation of IJob

	public function isActive() {
		return true;
	}

	public function getPriority() {
		return 1;
	}

	public function go() {
		$this->log("Start");
		$this->fillTodoLists();
		$this->workTodoLists();
		$this->setSyncTime();
		$this->log("Finished");
	}

	// Protected methods

	protected function log($message) {
		$this->logger->log("SyncJob", $this->getName() . " - " . $message);
	}

	protected function getAppEntriesChange() { return array(); }
	protected function getXrmEntriesChange() { return array(); }

	protected function deleteFromAppDisconnect($id) {}
	protected function deleteFromAppExecute($id) {}
	protected function deleteFromAppConnect($id) {}
	protected function deleteFromXrmDisconnect($id) {}
	protected function deleteFromXrmExecute($id) {}
	protected function deleteFromXrmConnect($id) {}
	protected function createAppToXrmDisconnect($id) {}
	protected function createAppToXrmExecute($id) {}
	protected function createAppToXrmConnect($id) {}
	protected function createXrmToAppDisconnect($id) {}
	protected function createXrmToAppExecute($id) {}
	protected function createXrmToAppConnect($id) {}
	protected function updateAppToXrmDisconnect($id) {}
	protected function updateAppToXrmExecute($id) {}
	protected function updateAppToXrmConnect($id) {}
	protected function updateXrmToAppDisconnect($id) {}
	protected function updateXrmToAppExecute($id) {}
	protected function updateXrmToAppConnect($id) {}

	// Private methods

	private function fillTodoLists() {
		$t = $this->getSyncTime();

		$appentries = $this->getAppEntriesChange();
		$xrmentries = $this->getXrmEntriesChange();

		if (!$appentries || !sizeof($appentries) || !$xrmentries || !sizeof($xrmentries)) {
			echo "No entries found. Aborted. Please check.\n";
			// Zum Starten muss dieser Bereich einmal manuell übergangen werden.
			// TODO Check erstellen für jede zu synchronisierende Datenbank (z.B. BASE3 XRM: Test-Eintrag erstellen, auslesen, löschen)
			exit;
		}

		// Einträge mit gleichem Stand herausfiltern
		// TODO später mit ETAG arbeiten
		foreach ($appentries as $appid => $appts) {
			if (!isset($xrmentries[$appid])) continue;
			if ($appts != $xrmentries[$appid]) continue;
			unset($appentries[$appid]);
			unset($xrmentries[$appid]);
			// echo $appid . " [ok]<br>";
		}

		// Einträge mit verschiedenem Stand
		foreach ($appentries as $appid => $appts) {
			if (!isset($xrmentries[$appid])) continue;
			if ($xrmentries[$appid] > $appts) {
				// TODO aktualisieren $xrm => $app
				$this->lists["updateXrmToApp"][] = $appid;
			} else {
				// TODO aktualisieren $app => $xrm
				$this->lists["updateAppToXrm"][] = $appid;
			}
			unset($appentries[$appid]);
			unset($xrmentries[$appid]);
		}

		// Einträge nur in App
		foreach ($appentries as $appid => $appts) {
			if ($appentries[$appid] < $t) {
				// TODO löschen $app
				$this->lists["deleteFromApp"][] = $appid;
			} else {
				// TODO neu $app => $xrm
				$this->lists["createAppToXrm"][] = $appid;
			}
			unset($appentries[$appid]);
		}

		// Einträge nur in XRM
		foreach ($xrmentries as $xrmid => $xrmts) {
			if ($xrmentries[$xrmid] < $t) {
				// TODO löschen $xrm
				$this->lists["deleteFromXrm"][] = $xrmid;
			} else {
				// TODO neu $xrm => $app
				$this->lists["createXrmToApp"][] = $xrmid;
			}
			unset($xrmentries[$xrmid]);
		}
	}

	private function workTodoLists() {
		$tasks = array(
			"deleteFromApp",
			"deleteFromXrm",
			"createAppToXrm",
			"createXrmToApp",
			"updateAppToXrm",
			"updateXrmToApp"
		);
		$steps = array("Disconnect", "Execute", "Connect");
		$msgs = [];
		foreach ($tasks as $t) {
			if (!isset($this->lists[$t])) continue;
			$num = sizeof($this->lists[$t]);
			$msgs[] = $t . ": " . $num;
			foreach ($steps as $s) {
				$func = $t . $s;
				$cnt = 0;
				foreach ($this->lists[$t] as $id) {
					echo $func . ": " . (++$cnt) . " / " . $num . "\n";
					call_user_func(array($this, $func), $id);
				}
			}
		}
		$this->log(sizeof($msgs) ? implode(" / ", $msgs) : "Nothing to do");
	}

	private function getSyncTime() {
		$this->newsynctime = date("Y-m-d H:i:s");
		if (!file_exists($this->syncfile)) return "0000-00-00 00:00:00";
		$t = file_get_contents($this->syncfile);
		return $t;
	}

	private function setSyncTime() {
		if ($this->failed || !$this->newsynctime) {
			echo "Sync failed!\n";
			return;
		}
		$fp = fopen($this->syncfile, "w");
		fwrite($fp, $this->newsynctime);
		fclose($fp);
	}

}
