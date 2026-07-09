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

namespace Base3\Usermanager;

class Permission {

	public $id;
	public $scope;
	public $permission;
	public $label;
	public $info;
	public $archive;

	public static function for(string $scope, string $permission): self {
		$grant = new self();
		$grant->scope = $scope;
		$grant->permission = $permission;
		return $grant;
	}

	public static function fromArray(array $data): self {
		$permission = new self();

		foreach (['id', 'scope', 'permission', 'label', 'info', 'archive'] as $key) {
			if (array_key_exists($key, $data)) {
				$permission->$key = $data[$key];
			}
		}

		return $permission;
	}
}
