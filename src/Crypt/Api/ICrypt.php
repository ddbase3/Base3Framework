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

use Base3\Api\IBase;

/**
 * Defines discoverable, reversible string encryption implementations.
 *
 * Implementations are part of the BASE3 framework. Callers select a method
 * through its stable getMethod() value and supply the secret separately.
 * Secret lookup, key rotation, and authorization are outside this contract.
 */
interface ICrypt extends IBase {

	/**
	 * Returns the canonical, versioned identifier for the encrypted data format.
	 *
	 * The identifier must be non-empty, stable across deployments, and unique
	 * among installed ICrypt implementations. It must change when the wire
	 * format or decryption semantics become incompatible. Callers must persist
	 * it with encrypted values to select the correct implementation on read.
	 * It is distinct from the class-map name returned by getName().
	 *
	 * @return string Stable, lowercase, versioned encryption method identifier
	 */
	public function getMethod(): string;

	/**
	 * Encrypts a string with the supplied secret.
	 *
	 * The result must contain all per-value data required for decryption, such
	 * as a random nonce or IV, but must never contain the supplied secret.
	 * Repeated encryption of the same input should produce different results.
	 * This method does not store or rotate secrets, nor change persistent data.
	 *
	 * @param string $str Plaintext; may be empty and may contain binary data
	 * @param string $secret Secret obtained from the configured key source
	 * @return string Encoded ciphertext in this implementation's stable format
	 * @throws \RuntimeException When encryption or a required extension fails
	 * @throws \InvalidArgumentException When the supplied secret is invalid
	 */
	public function encrypt(string $str, string $secret): string;

	/**
	 * Decrypts ciphertext written by this implementation and method version.
	 *
	 * Decryption must not silently return the input on failure. Authenticated
	 * implementations must reject tampered ciphertext or an incorrect secret.
	 * Legacy unauthenticated formats may not offer that guarantee and must be
	 * explicitly documented by the implementation.
	 * This method performs no persistence or secret lookups.
	 *
	 * @param string $str Encoded ciphertext produced by this implementation
	 * @param string $secret Original secret used for encryption
	 * @return string Original plaintext, including possible binary content
	 * @throws \RuntimeException When ciphertext cannot be decrypted or verified
	 * @throws \InvalidArgumentException When the supplied secret is invalid
	 */
	public function decrypt(string $str, string $secret): string;

}
