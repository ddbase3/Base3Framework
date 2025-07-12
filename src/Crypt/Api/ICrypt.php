<?php declare(strict_types=1);

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

