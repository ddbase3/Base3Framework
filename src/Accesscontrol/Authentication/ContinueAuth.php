<?php

namespace Accesscontrol\Authentication;

use Accesscontrol\AbstractAuth;

class ContinueAuth extends AbstractAuth {

	// Implementation of IBase

	public function getName() {
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
