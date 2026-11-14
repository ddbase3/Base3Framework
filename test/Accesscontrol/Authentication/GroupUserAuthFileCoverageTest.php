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

namespace Base3\Test\Accesscontrol\Authentication;

use Base3\Core\Accesscontrol\Authentication\GroupUserAuth;
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
