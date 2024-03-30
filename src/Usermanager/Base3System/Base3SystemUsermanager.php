<?php

namespace Usermanager\Base3System;

use Usermanager\Api\IUsermanager;
use Api\ICheck;

class Base3SystemUsermanager implements IUsermanager, ICheck {

	private $servicelocator;
	private $database;
	private $accesscontrol;
	private $session;

	private $user;
	private $groups;

	public function __construct() {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->database = $this->servicelocator->get('database');
		$this->accesscontrol = $this->servicelocator->get('accesscontrol');
		$this->session = $this->servicelocator->get('session');

		if ($this->session && $this->session->started() && isset($_SESSION["authentication"])) {
			if (isset($_SESSION["authentication"]["user"]))
				$this->user = $_SESSION["authentication"]["user"];
			if (isset($_SESSION["authentication"]["groups"]))
				$this->groups = $_SESSION["authentication"]["groups"];
		}
	}

	// Implementation of IUsermanager

	public function getUser() {
		if ($this->user) return $this->user;

		$userid = $this->accesscontrol->getUserId();
		if (!$userid) return null;

		if ($userid == "internal") {

			$this->user = new \Usermanager\User;
			$this->user->id = "internal";
			$this->user->name = "internal";
			$this->user->role = "admin";

		} else {

			$this->database->connect();

			// mode: 0 visit | 1 member | 2 admin
			// lang: Sprache 2 Zeichen
			$sql = "SELECT u.`name` AS `userid`, u.`fullname`, u.`email`, u.`mode`, l.`short` AS `lang`
				FROM `base3system_sysuser` u
				INNER JOIN `base3system_syslang` l ON u.`lang_id` = l.`id`
				WHERE u.`name` = '" . $this->database->escape($userid) . "'";

			$row = $this->database->singleQuery($sql);
			if ($row == null) return null;

			$roles = array(0 => "visit", 1 => "member", 2 => "admin");

			$this->user = new \Usermanager\User;
			$this->user->id = $row["userid"];
			$this->user->name = $row["fullname"];
			$this->user->email = $row["email"];
			$this->user->lang = $row["lang"];
			$this->user->role = $roles[$row["mode"]];

		}

		if ($this->session && $this->session->started()) {
			$_SESSION["authentication"]["user"] = $this->user;
		}

		return $this->user;
	}

	public function getGroups() {
		$userid = $this->accesscontrol->getUserId();

		if (!$userid) return array();
		if ($this->groups) return $this->groups;

		$this->database->connect();

		$sql = "SELECT g.`name` AS `groupid`, g.`info`
			FROM `base3system_sysuser` u
			INNER JOIN `base3system_sysusergroup` ug ON u.`id` = ug.`user_id`
			INNER JOIN `base3system_sysgroup` g ON ug.`group_id` = g.`id`
			WHERE u.`name` = '" . $this->database->escape($userid) . "'";

		$rows = $this->database->multiQuery($sql);
		$this->groups = array();
		foreach ($rows as $row) {
			$group = new \Usermanager\Group;
			$group->id = $row["groupid"];
			$group->name = $row["info"];
			$this->groups[] = $group;
		}

		if ($this->session && $this->session->started()) {
			$_SESSION["authentication"]["groups"] = $this->groups;
		}

		return $this->groups;
	}

	public function registUser($userid, $password, $data = null) {
		// userid/password for authentication
		// data for two-way-auth (i.e. mail, mobile-no, ...) and fullname, language etc.

		$this->database->connect();

		// TODO

	}

	public function changePassword($oldpassword, $newpassword) {
		$userid = $this->accesscontrol->getUserId();
		if (!$userid) return false;

		$this->database->connect();

		// TODO

	}

	public function getAllUsers() {

		// TODO Rechte prüfen, ob man abfragen darf (User muss Admin sein)

		$this->database->connect();

		$sql = "SELECT u.`name` AS `userid`, u.`fullname`, u.`email`, u.`mode`, l.`short` AS `lang`
			FROM `base3system_sysuser` u
			INNER JOIN `base3system_syslang` l ON u.`lang_id` = l.`id`
			WHERE u.`id` != 1";
		$rows = $this->database->multiQuery($sql);

		$roles = array(0 => "visit", 1 => "member", 2 => "admin");

		$users = array();
		foreach ($rows as $row) {
			$user = new \Usermanager\User;
			$user->id = $row["userid"];
			$user->name = $row["fullname"];
			$user->email = $row["email"];
			$user->lang = $row["lang"];
			$user->role = $roles[$row["mode"]];
			$users[] = $user;
		}

		return $users;
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->database == null || $this->accesscontrol == null ? "Fail" : "Ok"
		);
	}

}
