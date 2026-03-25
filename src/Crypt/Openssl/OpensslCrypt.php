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

namespace Base3\Crypt\Openssl;

use Base3\Crypt\Api\ICrypt;
use Base3\Api\ICheck;

class OpensslCrypt implements ICrypt, ICheck {

	private $method = "AES-256-CBC";

	// Implementation of ICrypt

	public function encrypt(string $str, string $secret): string {
		$key = hash('sha256', $secret);
		$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->method));
		$crypt = openssl_encrypt($str, $this->method, $key, 0, $iv);
		return $crypt . ':' . base64_encode($iv);
	}

	public function decrypt(string $str, string $secret): string {
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
