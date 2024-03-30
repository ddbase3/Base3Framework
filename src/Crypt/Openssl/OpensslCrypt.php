<?php

namespace Crypt\Openssl;

use Crypt\Api\ICrypt;
use Api\ICheck;

class OpensslCrypt implements ICrypt, ICheck {

	private $method = "AES-256-CBC";

	// Implementation of ICrypt

	public function encrypt($str, $secret) {
		$key = hash('sha256', $secret);
		$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->method));
		$crypt = openssl_encrypt($str, $this->method, $key, 0, $iv);
		return $crypt . ':' . base64_encode($iv);
	}

	public function decrypt($str, $secret) {
		$output = false;
		$key = hash('sha256', $secret);
		$parts = explode(':' , $str);
		return openssl_decrypt($parts[0], $this->method, $key, 0, base64_decode($parts[1]));
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"openssl_available" => extension_loaded('openssl') ? "Ok" : "OpenSSL extension not loaded"
		);
	}

}
