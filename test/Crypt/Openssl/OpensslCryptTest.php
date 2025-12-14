<?php declare(strict_types=1);

namespace Base3\Test\Crypt\Openssl;

use PHPUnit\Framework\TestCase;
use Base3\Crypt\Openssl\OpensslCrypt;

class OpensslCryptTest extends TestCase
{
	private OpensslCrypt $crypt;

	protected function setUp(): void
	{
		if (!extension_loaded('openssl')) {
			$this->markTestSkipped('OpenSSL extension not loaded.');
		}

		$this->crypt = new OpensslCrypt();
	}

	public function testEncryptReturnsCiphertextWithIvSuffix(): void
	{
		$plain = 'hello world';
		$secret = 'my-secret';

		$enc = $this->crypt->encrypt($plain, $secret);

		$this->assertIsString($enc);
		$this->assertNotSame('', $enc);
		$this->assertStringContainsString(':', $enc);

		$parts = explode(':', $enc, 2);
		$this->assertCount(2, $parts);

		$this->assertNotSame('', $parts[0], 'Ciphertext part must not be empty.');
		$this->assertNotSame('', $parts[1], 'IV part must not be empty.');

		$iv = base64_decode($parts[1], true);
		$this->assertNotFalse($iv, 'IV part must be valid base64.');

		$expectedIvLen = openssl_cipher_iv_length('AES-256-CBC');
		$this->assertSame($expectedIvLen, strlen($iv), 'IV length must match cipher IV length.');
	}

	public function testDecryptRoundTripReturnsOriginalPlaintext(): void
	{
		$plain = 'Sensitive text äöü ß 😄 with symbols !@#$%^&*()';
		$secret = 'correct-horse-battery-staple';

		$enc = $this->crypt->encrypt($plain, $secret);
		$dec = $this->crypt->decrypt($enc, $secret);

		$this->assertSame($plain, $dec);
	}

	public function testTamperedIvDoesNotYieldOriginalPlaintext(): void
	{
		$plain = 'Top secret';
		$secret = 'my-secret';

		$enc = $this->crypt->encrypt($plain, $secret);
		$parts = explode(':', $enc, 2);

		$this->assertCount(2, $parts);

		$iv = base64_decode($parts[1], true);
		$this->assertNotFalse($iv);

		// Flip a bit in IV to tamper without changing format
		$iv[0] = chr(ord($iv[0]) ^ 0x01);
		$tampered = $parts[0] . ':' . base64_encode($iv);

		// decrypt() is declared :string but may TypeError if openssl_decrypt returns false.
		// We prevent the whole suite from erroring by treating that case as "tamper detected".
		try {
			$dec = $this->crypt->decrypt($tampered, $secret);
			$this->assertNotSame($plain, $dec);
		} catch (\TypeError $e) {
			// Tamper caused openssl_decrypt to return false -> acceptable outcome for "not original plaintext".
			$this->assertTrue(true);
		}
	}

	public function testTwoEncryptionsOfSamePlaintextDifferBecauseIvIsRandom(): void
	{
		$plain = 'same input';
		$secret = 'same secret';

		$enc1 = $this->crypt->encrypt($plain, $secret);
		$enc2 = $this->crypt->encrypt($plain, $secret);

		$this->assertNotSame($enc1, $enc2, 'Encryption should differ due to random IV.');
	}

	public function testCheckDependenciesReportsOpensslAvailable(): void
	{
		$deps = $this->crypt->checkDependencies();

		$this->assertIsArray($deps);
		$this->assertArrayHasKey('openssl_available', $deps);
		$this->assertSame('Ok', $deps['openssl_available']);
	}
}
