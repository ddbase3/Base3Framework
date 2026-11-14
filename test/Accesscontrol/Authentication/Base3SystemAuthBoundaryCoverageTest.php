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

use Base3\Core\Accesscontrol\Authentication\Base3SystemAuth;
use Base3\CoreFoundation\Api\IContainer;
use Base3\Core\ServiceLocator;
use Base3\Test\Usermanager\RecordingUsermanagerDatabaseFixture;
use PHPUnit\Framework\TestCase;

final class Base3SystemAuthBoundaryCoverageTest extends TestCase {

	private function withDatabase(?RecordingUsermanagerDatabaseFixture $database, callable $run): void {
		$beforeLocator = ServiceLocator::getInstance();
		$beforeRequest = $_REQUEST;
		try {
			$container = new ServiceLocator();
			if ($database !== null) $container->set('database', $database, IContainer::SHARED);
			ServiceLocator::useInstance($container);
			$_REQUEST = [];
			$run(new Base3SystemAuth());
		} finally { ServiceLocator::useInstance($beforeLocator); $_REQUEST = $beforeRequest; }
	}

	public function testMissingDatabaseRefusesLogin(): void {
		$this->withDatabase(null, function(Base3SystemAuth $auth): void {
			$_REQUEST = ['login' => 1, 'username' => 'alice', 'password' => 'correct'];
			$this->assertNull($auth->login());
			$this->assertSame(['depending_services' => 'Fail'], $auth->checkDependencies());
		});
	}

	public function testMissingFieldsDoNotQueryDatabase(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$this->withDatabase($db, function(Base3SystemAuth $auth): void {
			$this->assertNull($auth->login());
			$_REQUEST = ['login' => 1];
			$this->assertNull($auth->login());
			$_REQUEST = ['login' => 1, 'username' => 'alice'];
			$this->assertNull($auth->login());
			$this->assertSame(['depending_services' => 'Ok'], $auth->checkDependencies());
		});
		$this->assertSame([], $db->queries);
	}

	public function testFailedPasswordLookupReturnsNullAndEscapesUserId(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$db->singleRows = [null];
		$this->withDatabase($db, function(Base3SystemAuth $auth): void {
			$_REQUEST = ['login' => 1, 'username' => "o'hara", 'password' => 'guess'];
			$this->assertNull($auth->login());
		});
		$this->assertSame(1, $db->connections);
		$this->assertStringContainsString("o''hara", $db->queries[0]);
		$this->assertStringContainsString(md5('guess'), $db->queries[0]);
	}

	public function testAuthenticatedUserNameIsReturnedFromDatabase(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$db->singleRows = [['name' => 'alice', 'mode' => 1]];
		$this->withDatabase($db, function(Base3SystemAuth $auth): void {
			$_REQUEST = ['login' => 1, 'username' => 'alice', 'password' => 'correct'];
			$this->assertSame('alice', $auth->login());
		});
	}

	public function testSwitchUserOnlyAllowedForAuthenticatedAdminRow(): void {
		foreach ([[1, 'alice'], [2, 'target']] as [$mode, $expected]) {
			$db = new RecordingUsermanagerDatabaseFixture();
			$db->singleRows = [['name' => 'alice', 'mode' => $mode]];
			$this->withDatabase($db, function(Base3SystemAuth $auth) use ($expected): void {
				$_REQUEST = ['login' => 1, 'username' => 'alice', 'password' => 'correct', 'switchuser' => 'target'];
				$this->assertSame($expected, $auth->login());
			});
		}
	}
}

require_once __DIR__ . '/../../Usermanager/RecordingUsermanagerDatabaseFixture.php';
