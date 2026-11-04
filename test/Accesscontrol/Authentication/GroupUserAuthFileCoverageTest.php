<?php declare(strict_types=1);

namespace Base3\Test\Accesscontrol\Authentication;

use Base3\Accesscontrol\Authentication\GroupUserAuth;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class GroupUserAuthFileCoverageTest extends TestCase {

	private function withCredentials(array $users, callable $run): void {
		$this->assertFalse(defined('DIR_LOCAL'));
		$path = sys_get_temp_dir() . '/base3-groupauth-' . bin2hex(random_bytes(8));
		$this->assertTrue(mkdir($path . '/Authentication', 0700, true));
		define('DIR_LOCAL', $path . '/');
		file_put_contents($path . '/Authentication/groupusers.json', json_encode($users));
		$oldRequest = $_REQUEST;
		try { $_REQUEST = []; $run(new GroupUserAuth()); }
		finally {
			$_REQUEST = $oldRequest;
			unlink($path . '/Authentication/groupusers.json');
			rmdir($path . '/Authentication');
			rmdir($path);
		}
	}

	public function testMissingPasswordDoesNotReadCredentialFile(): void {
		$this->withCredentials(['alice' => sha1('secret')], function(GroupUserAuth $auth): void {
			$this->assertNull($auth->login());
			$this->assertSame('groupuserauth', $auth::getName());
		});
	}

	public function testMatchingPasswordFindsFirstRegisteredUser(): void {
		$this->withCredentials(['alice' => sha1('first'), 'bob' => sha1('second')], function(GroupUserAuth $auth): void {
			$_REQUEST['password'] = 'second';
			$this->assertSame('bob', $auth->login());
		});
	}

	public function testIncorrectPasswordIsNotAuthenticated(): void {
		$this->withCredentials(['alice' => sha1('first'), 'bob' => sha1('second')], function(GroupUserAuth $auth): void {
			$_REQUEST['password'] = 'unknown';
			$this->assertNull($auth->login());
		});
	}

	public function testEmptyCredentialsListNeverAuthenticates(): void {
		$this->withCredentials([], function(GroupUserAuth $auth): void {
			$_REQUEST['password'] = 'secret';
			$this->assertNull($auth->login());
		});
	}
}
