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

namespace Base3\Token;

use Base3\Token\Api\IToken;

class TokenProxy implements IToken {

	private $connector;

	public function __construct($connector) {
		$this->connector = $connector;
	}

        public function create($scope, $id, $size = 32, $duration = 3600) {
		return $this->connector->create($scope, $id, $size, $duration);
	}

        public function check($scope, $id, $token) {
		return $this->connector->check($scope, $id, $token);
	}

        public function delete($scope, $id, $token) {
		$this->connector->delete($scope, $id, $token);
	}

        public function clean($scope, $id) {
		$this->connector->clean($scope, $id);
	}
}
