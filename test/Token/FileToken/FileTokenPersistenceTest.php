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

namespace Base3\Test\Token\FileToken;

use Base3\Core\Token\FileToken\FileToken;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class FileTokenPersistenceTest extends TestCase {

	private string $localDir;

	protected function setUp(): void {
		$this->localDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
			. DIRECTORY_SEPARATOR . 'base3_filetoken_test_' . bin2hex(random_bytes(8)) . DIRECTORY_SEPARATOR;
		if (!mkdir($this->localDir, 0700, true) && !is_dir($this->localDir)) {
			self::fail('Could not create an isolated FileToken test directory');
		}
		define('Base3\\Core\\Token\\FileToken\\DIR_LOCAL', $this->localDir);
	}

	protected function tearDown(): void {
		$tokenDir = $this->localDir . 'FileToken';
		if (is_dir($tokenDir)) {
			foreach (glob($tokenDir . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
				if (is_file($path)) unlink($path);
			}
			rmdir($tokenDir);
		}
		if (is_dir($this->localDir)) rmdir($this->localDir);
	}

	private function uniqueScope(): string {
		return 'base3test_' . bin2hex(random_bytes(8));
	}

	public function testSavedTokenCanBeVerifiedAndRevoked(): void {
		$store = new FileToken();
		$scope = $this->uniqueScope();
		$id = 'user1';
		try {
			$this->assertFalse($store->check($scope,$id,'secret'));
			$store->save($scope,$id,'secret',60);
			$this->assertTrue($store->check($scope,$id,'secret'));
			$this->assertFalse($store->check($scope,$id,'wrong'));
			$store->delete($scope,$id,'secret');
			$this->assertFalse($store->check($scope,$id,'secret'));
		} finally {
			$store->clean($scope,$id);
		}
	}

	public function testGeneratedTokenHasRequestedEntropyAndIsNotStoredInPlainText(): void {
		$store = new FileToken();
		$scope = $this->uniqueScope();
		$id = 'user2';
		try {
			$token = $store->create($scope,$id,16,60);
			$this->assertSame(32, strlen($token));
			$this->assertTrue($store->check($scope,$id,$token));
			$path = $this->localDir . 'FileToken' . DIRECTORY_SEPARATOR . $scope . '-' . md5($id) . '.json';
			$contents = (string) file_get_contents($path);
			$this->assertStringContainsString(sha1($token), $contents);
			$this->assertFalse(str_contains($contents, '"token":"' . $token . '"'));
		} finally {
			$store->clean($scope,$id);
		}
	}

	public function testExpiredTokensAreNotAccepted(): void {
		$store = new FileToken();
		$scope = $this->uniqueScope();
		$id = 'user3';
		try {
			$store->save($scope,$id,'already-expired',-60);
			$this->assertFalse($store->check($scope,$id,'already-expired'));
			$store->save($scope,$id,'current',60);
			$this->assertTrue($store->check($scope,$id,'current'));
			$this->assertFalse($store->check($scope,$id,'already-expired'));
		} finally {
			$store->clean($scope,$id);
		}
	}

	public function testDistinctIdsRemainIsolatedEvenForEqualTokens(): void {
		$store = new FileToken();
		$scope = $this->uniqueScope();
		try {
			$store->save($scope,'one','same',60);
			$this->assertFalse($store->check($scope,'two','same'));
			$store->save($scope,'two','same',60);
			$store->delete($scope,'one','same');
			$this->assertFalse($store->check($scope,'one','same'));
			$this->assertTrue($store->check($scope,'two','same'));
		} finally {
			$store->clean($scope,'one');
			$store->clean($scope,'two');
		}
	}
}
