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

namespace Base3\Accesscontrol\Authentication;

use Base3\Accesscontrol\AbstractAuth;

class ContinueAuth extends AbstractAuth {

	// Implementation of IBase

	public static function getName(): string {
		return "continueauth";
	}

	// Implementation of IAuthentication

	public function finish($userid) {
		if (isset($_REQUEST["_continueauth"]) && strlen($_REQUEST["_continueauth"])) {
			session_write_close();
			header('Location: ' . $_REQUEST["_continueauth"]);
			exit;
		}
		if ($userid != null && $this->chopExtension(__FILE__) == "index") {
			// TODO check
			// prüfen, was __FILE__ zurückgibt ... nach den htaccess-Einstellungen
			// weiterleitung zu interner Seite, wenn eingestellt (siehe Login-Formular). Das gehört hierher ...
		}
	}

	// Private methods

	private function chopExtension($filename) {
		return substr($filename, 0, strrpos($filename, '.'));
	}

}
