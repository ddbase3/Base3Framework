<?php declare(strict_types=1);

namespace Base3\Test\Accesscontrol\Authentication;

use Base3\Accesscontrol\Authentication\SingleAuth;
use Base3\Api\IContainer;
use Base3\Configuration\ArrayConfiguration;
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
