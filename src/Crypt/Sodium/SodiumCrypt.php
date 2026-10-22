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

namespace Base3\Crypt\Sodium;

use Base3\Crypt\Api\ICrypt;
use InvalidArgumentException;
use RuntimeException;

/**
 * Authenticated encryption using XChaCha20-Poly1305 (IETF) from libsodium.
 *
 * The caller must supply a high-entropy secret containing at least 32 bytes.
 * Key derivation uses BLAKE2b, not a password-stretching KDF; passwords must
 * not be used as encryption secrets. Ciphertexts use the stable v1: format.
 */
class SodiumCrypt implements ICrypt {

	private const METHOD = 'xchacha20poly1305-v1';
	private const PREFIX = 'v1:';

	public static function getName(): string {
		return 'sodiumcrypt';
	}

	public function getMethod(): string {
		return self::METHOD;
	}

	public function encrypt(string $str, string $secret): string {
		$this->requireSodium();
		$key = $this->deriveKey($secret);
		$nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);

		try {
			$ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($str, '', $nonce, $key);
			return self::PREFIX . base64_encode($nonce . $ciphertext);
		} finally {
			sodium_memzero($key);
		}
	}

	public function decrypt(string $str, string $secret): string {
		$this->requireSodium();
		$key = $this->deriveKey($secret);

		try {
			if (!str_starts_with($str, self::PREFIX)) {
				throw new RuntimeException('Invalid Sodium ciphertext version.');
			}

			$data = base64_decode(substr($str, strlen(self::PREFIX)), true);
			$nonceSize = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
			$tagSize = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_ABYTES;
			if ($data === false || strlen($data) < $nonceSize + $tagSize) {
				throw new RuntimeException('Invalid Sodium ciphertext payload.');
			}

			$nonce = substr($data, 0, $nonceSize);
			$ciphertext = substr($data, $nonceSize);
			$plaintext = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($ciphertext, '', $nonce, $key);
			if ($plaintext === false) {
				throw new RuntimeException('Sodium ciphertext authentication failed.');
			}

			return $plaintext;
		} finally {
			sodium_memzero($key);
		}
	}

	private function requireSodium(): void {
		if (!extension_loaded('sodium')) {
			throw new RuntimeException('The Sodium extension is required for this encryption method.');
		}
	}

	private function deriveKey(string $secret): string {
		if (strlen($secret) < 32) {
			throw new InvalidArgumentException('A random encryption secret of at least 32 bytes is required.');
		}

		return sodium_crypto_generichash($secret, '', SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES);
	}
}
