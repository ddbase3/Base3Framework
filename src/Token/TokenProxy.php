<?php declare(strict_types=1);

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
