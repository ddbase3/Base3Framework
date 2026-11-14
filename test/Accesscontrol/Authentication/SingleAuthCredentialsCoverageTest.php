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

use Base3\Core\Accesscontrol\Authentication\SingleAuth;
use Base3\CoreFoundation\Api\IContainer;
use Base3\Core\Configuration\ArrayConfiguration;
use Base3\Core\ServiceLocator;
use PHPUnit\Framework\TestCase;

final class SingleAuthCredentialsCoverageTest extends TestCase {

	private function withConfiguration(?array $data, callable $run): void {
		$previous = ServiceLocator::getInstance();
		$request = $_REQUEST;
		try {
			$container = new ServiceLocator();
			if ($data !== null) $container->set('configuration', new ArrayConfiguration($data), IContainer::SHARED);
			ServiceLocator::useInstance($container);
			$_REQUEST = [];
			$run(new SingleAuth());
		} finally { ServiceLocator::useInstance($previous); $_REQUEST = $request; }
	}

	public function testMissingConfigurationRefusesAuthentication(): void {
		$this->withConfiguration(null, function(SingleAuth $auth): void {
			$_REQUEST = ['login' => 1, 'username' => 'alice', 'password' => 'secret'];
			$this->assertNull($auth->login());
			$this->assertSame(['depending_services' => 'Fail'], $auth->checkDependencies());
		});
	}

	public function testMissingSingleAuthSettingsAreRejected(): void {
		$this->withConfiguration([], function(SingleAuth $auth): void {
			$_REQUEST = ['login' => 1, 'username' => 'alice', 'password' => 'secret'];
			$this->assertNull($auth->login());
		});
	}

	public function testCorrectCredentialsAreAccepted(): void {
		$this->withConfiguration(['singleauth' => ['user' => 'alice', 'pass' => sha1('secret')]], function(SingleAuth $auth): void {
			$_REQUEST = ['login' => 1, 'username' => 'alice', 'password' => 'secret'];
			$this->assertSame('alice', $auth->login());
			$this->assertSame(['depending_services' => 'Ok'], $auth->checkDependencies());
		});
	}

	public function testWrongPasswordAndUnknownUserAreBothRejected(): void {
		$this->withConfiguration(['singleauth' => ['user' => 'alice', 'pass' => sha1('secret')]], function(SingleAuth $auth): void {
			$_REQUEST = ['login' => 1, 'username' => 'alice', 'password' => 'wrong'];
			$this->assertNull($auth->login());
			$_REQUEST['username'] = 'other';
			$_REQUEST['password'] = 'secret';
			$this->assertNull($auth->login());
		});
	}

	public function testIncompleteCredentialsNeverAuthenticate(): void {
		$this->withConfiguration(['singleauth' => ['user' => 'alice', 'pass' => sha1('secret')]], function(SingleAuth $auth): void {
			$this->assertNull($auth->login());
			$_REQUEST = ['login' => 1, 'username' => 'alice'];
			$this->assertNull($auth->login());
			$_REQUEST = ['login' => 1, 'password' => 'secret'];
			$this->assertNull($auth->login());
		});
	}
}
