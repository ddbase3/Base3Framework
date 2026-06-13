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

namespace Base3\Crypt\Api;

/**
 * Interface ICrypt
 *
 * Defines methods for encrypting and decrypting strings using a shared secret.
 */
interface ICrypt {

	/**
	 * Encrypts a string using the given secret.
	 *
	 * @param string $str The plaintext string to encrypt
	 * @param string $secret The secret key used for encryption
	 * @return string The encrypted string (e.g. base64-encoded)
	 */
	public function encrypt(string $str, string $secret): string;

	/**
	 * Decrypts a string using the given secret.
	 *
	 * @param string $str The encrypted string
	 * @param string $secret The secret key used for decryption
	 * @return string The decrypted plaintext string
	 */
	public function decrypt(string $str, string $secret): string;

}

