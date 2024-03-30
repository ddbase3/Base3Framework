<?php

namespace Xrm\Master;

use Api\ICheck;
use Xrm\AbstractXrm;

class MasterXrm extends AbstractXrm implements ICheck {

	private $xrms;

	public function __construct($cnf = null) {
		parent::__construct();
		$this->xrms = $this->getXrms();
	}

	// Implementation of IXrm

	public function getXrmName() {
		return null;
	}

	public function delEntry($id, $moveonly = false) {
		$result = false;
		$this->xrms = $this->getXrms();
		foreach ($this->xrms as $xrmclosure) {
			$xrm = $xrmclosure();
			$res = $xrm->delEntry($id, $moveonly);
			if ($res) $result = true;
		}
		return $result;
	}

	public function setEntry($entry) {
		// TODO Duplikate sortieren
		$entries = array();
		$this->xrms = $this->getXrms();
		foreach ($this->xrms as $xrmclosure) {
			$xrm = $xrmclosure();
			$entry = $xrm->setEntry($entry);  // $entry überschreiben, weil hier nach dem ersten Aufruf bereits eine ID enthalten sein sollte
			if ($entry != null) $entries[] = (object) $entry;
		}

		$xrmnames = array();
		if (sizeof($entries)) foreach ($entries as $e) $xrmnames = array_merge($xrmnames, $e->xrmnames);

		$entry = sizeof($entries) ? (object) array_pop($entries) : null;
		if ($entry != null) $entry->xrmnames = array_unique($xrmnames);
		return $entry;
	}

	public function getEntry($id) {
		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getEntry", "id" => $id)));
		// TODO Duplikate sortieren
		$entries = array();
		$this->xrms = $this->getXrms();
		foreach ($this->xrms as $name => $xrmclosure) {
			$xrm = $xrmclosure();
			$e = $xrm->getEntry($id);
			if ($e != null) $entries[] = $e;
		}

		$xrmnames = array();
		if (sizeof($entries)) foreach ($entries as $e) {
			$e = (object) $e;
			$xrmnames = array_merge($xrmnames, $e->xrmnames);
		}

		$entry = sizeof($entries) ? (object) array_pop($entries) : null;
		if ($entry != null) $entry->xrmnames = array_unique($xrmnames);

		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getEntry", "num" => sizeof($entries) ? 1 : 0)));
		return $entry;
	}

	public function getAllocIds($id) {
		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getAllocIds", "id" => $id)));
		$entries = array();
		$this->xrms = $this->getXrms();
		foreach ($this->xrms as $name => $xrmclosure) {
			$xrm = $xrmclosure();
			$es = $xrm->getAllocIds($id);
			if ($es != null && sizeof($es)) $entries = array_merge($entries, $es);
		}
		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getAllocIds", "num" => sizeof($entries))));
		return $entries;
	}

	public function getEntries($ids) {
		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getEntries", "ids" => $ids)));

		$this->xrms = $this->getXrms();
		// TODO Duplikate sortieren

		$entries = array();
		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getEntries", "numxrms" => sizeof($this->xrms))));
		foreach ($this->xrms as $name => $xrmclosure) {
			$xrm = $xrmclosure();
			$es = $xrm->getEntries($ids);
//$this->logger->log("syncjob", $name." | ".sizeof($ids)." | ".sizeof($es));

			if (is_array($es)) foreach ($es as $en) {
				$e = (object) $en;
				if (array_key_exists($e->id, $entries)) {
					$entries[$e->id]->xrmnames = array_unique(array_merge($entries[$e->id]->xrmnames, $e->xrmnames));
					continue;
				}
				$entries[$e->id] = $e;
			}

			// if ($es != null && sizeof($es)) $entries = array_merge($entries, $es);

		}

		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getEntries", "num" => sizeof($entries))));
		return $entries;
	}

	public function getAllEntryIds() {
		$entries = array();
		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getAllEntryIds")));
		$this->xrms = $this->getXrms();
		foreach ($this->xrms as $xrmclosure) {
			$xrm = $xrmclosure();
			$es = $xrm->getAllEntryIds();
			if (is_array($es)) $entries = array_merge($entries, $es);
		}
		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getAllEntryIds", "num" => sizeof($entries))));
		return $entries;
	}

	public function getXrmEntryIds($xrmname, $invert = false) {
		$entries = array();
		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getXrmEntryIds", "xrmname" => $xrmname)));
		$this->xrms = $this->getXrms();
		foreach ($this->xrms as $nn => $xrmclosure) {
			$xrm = $xrmclosure();

			/////////////////////////////////////////////////////////////////////////////
			// TODO: Abfrage über Array-Index oder über getXrmName? - Letzteres benötigt einen Microservice-Call!
			//
			// ... auch nicht gut, weil Cache die FileXrms übernehmen soll
			// if ($nn != $xrmname) continue;
			// if ($xrm->getXrmName() != $xrmname) continue;
			//
			/////////////////////////////////////////////////////////////////////////////

			$es = $xrm->getXrmEntryIds($xrmname, $invert);
			$entries = array_merge($entries, $es);
		}
		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getXrmEntryIds", "num" => sizeof($entries))));
		return $entries;
	}

	public function addTag($id, $tag) {
		$result = true;
		$this->xrms = $this->getXrms();
		foreach ($this->xrms as $xrmclosure) {
			$xrm = $xrmclosure();
			$result &= $xrm->addTag($id, $tag);
		}
		return $result;
	}

	public function removeTag($id, $tag) {
		$result = true;
		$this->xrms = $this->getXrms();
		foreach ($this->xrms as $xrmclosure) {
			$xrm = $xrmclosure();
			$result &= $xrm->removeTag($id, $tag);
		}
		return $result;
	}

	public function addApp($id, $app) {
		$result = true;
		$this->xrms = $this->getXrms();
		foreach ($this->xrms as $xrmclosure) {
			$xrm = $xrmclosure();
			$result &= $xrm->addApp($id, $app);
		}
		return $result;
	}

	public function removeApp($id, $app) {
		$result = true;
		$this->xrms = $this->getXrms();
		foreach ($this->xrms as $xrmclosure) {
			$xrm = $xrmclosure();
			$result &= $xrm->removeApp($id, $app);
		}
		return $result;
	}

	public function addAlloc($id1, $id2) {
		$result = true;
		$this->xrms = $this->getXrms();
		foreach ($this->xrms as $xrmclosure) {
			$xrm = $xrmclosure();
			$result &= $xrm->addAlloc($id1, $id2);
		}
		return $result;
	}

	public function removeAlloc($id1, $id2) {
		$result = true;
		$this->xrms = $this->getXrms();
		foreach ($this->xrms as $xrmclosure) {
			$xrm = $xrmclosure();
			$result &= $xrm->removeAlloc($id1, $id2);
		}
		return $result;
	}

	public function getUserEntryId() {
		$this->xrms = $this->getXrms();
		foreach ($this->xrms as $xrmclosure) {
			$xrm = $xrmclosure();
			$userEntryId = $xrm->getUserEntryId();
			if ($userEntryId != null) return $userEntryId;
		}
		return null;
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->xrms == null ? "Fail" : "Ok"
		);
	}

	// Private functions

	private function getXrms() {
		// funktioniert nicht anders bei Closures
		// vielleicht auch konfigurierbar aus cnf/config.ini?
		return $this->servicelocator->get('xrms');
	}

}
