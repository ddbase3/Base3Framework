<?php declare(strict_types=1);

namespace Base3\Test\Crypt\Sodium;

use Base3\Api\IBase;
use Base3\Crypt\Api\ICrypt;
use Base3\Crypt\Sodium\SodiumCrypt;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SodiumCryptTest extends TestCase {

	private SodiumCrypt $crypt;
	private string $secret;

	protected function setUp(): void {
		if (!extension_loaded('sodium')) {
			$this->markTestSkipped('Sodium extension not loaded.');
		}

		$this->crypt = new SodiumCrypt();
		$this->secret = base64_encode(random_bytes(32));
	}

	public function testMethodIsDiscoverableAndVersioned(): void {
		$this->assertInstanceOf(ICrypt::class, $this->crypt);
		$this->assertInstanceOf(IBase::class, $this->crypt);
		$this->assertSame('sodiumcrypt', SodiumCrypt::getName());
		$this->assertSame('xchacha20poly1305-v1', $this->crypt->getMethod());
	}

	public function testEncryptDecryptRoundTripWithUnicodeAndBinary(): void {
		$plain = "German äöü 😄\0extra";
		$encrypted = $this->crypt->encrypt($plain, $this->secret);
		$this->assertStringStartsWith('v1:', $encrypted);
		$this->assertSame($plain, $this->crypt->decrypt($encrypted, $this->secret));
	}

	public function testEmptyPlaintextRoundTrip(): void {
		$this->assertSame('', $this->crypt->decrypt($this->crypt->encrypt('', $this->secret), $this->secret));
	}

	public function testRandomNonceChangesCiphertext(): void {
		$this->assertNotSame(
			$this->crypt->encrypt('same text', $this->secret),
			$this->crypt->encrypt('same text', $this->secret)
		);
	}

	public function testTamperedCiphertextIsRejected(): void {
		$encrypted = $this->crypt->encrypt('private chat', $this->secret);
		$payload = base64_decode(substr($encrypted, 3), true);
		$this->assertIsString($payload);
		$payload[strlen($payload) - 1] = chr(ord($payload[strlen($payload) - 1]) ^ 1);

		$this->expectException(RuntimeException::class);
		$this->crypt->decrypt('v1:' . base64_encode($payload), $this->secret);
	}

	public function testWrongSecretIsRejected(): void {
		$encrypted = $this->crypt->encrypt('private chat', $this->secret);

		$this->expectException(RuntimeException::class);
		$this->crypt->decrypt($encrypted, base64_encode(random_bytes(32)));
	}

	public function testUnknownVersionIsRejected(): void {
		$this->expectException(RuntimeException::class);
		$this->crypt->decrypt('v2:' . base64_encode(random_bytes(40)), $this->secret);
	}

	public function testMalformedPayloadIsRejected(): void {
		$this->expectException(RuntimeException::class);
		$this->crypt->decrypt('v1:!!!!', $this->secret);
	}

	public function testShortSecretIsRejected(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->crypt->encrypt('private chat', 'weak-secret');
	}
}
