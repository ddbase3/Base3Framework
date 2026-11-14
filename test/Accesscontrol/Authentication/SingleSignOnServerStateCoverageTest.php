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

use Base3\Core\Accesscontrol\Authentication\SingleSignOnServerAuth;
use Base3\CoreFoundation\Api\IContainer;
use Base3\Core\ServiceLocator;
use Base3\Core\Session\NoSession\NoSession;
use Base3\Test\Accesscontrol\RecordingAuthToken;
use PHPUnit\Framework\TestCase;

final class SingleSignOnServerStateCoverageTest extends TestCase {

	private function withServer(array $request, array $server, callable $run): void {
		$previousRequest = $_REQUEST;
		$previousServer = $_SERVER;
		$previousSession = $_SESSION ?? null;
		$previousLocator = ServiceLocator::getInstance();
		try {
			$_REQUEST = $request;
			$_SERVER = $server;
			$_SESSION = [];
			$container = new ServiceLocator();
			$container->set('session', new NoSession(), IContainer::SHARED);
			$container->set('ssotoken', new RecordingAuthToken(), IContainer::SHARED);
			ServiceLocator::useInstance($container);
			$run(new SingleSignOnServerAuth());
		} finally {
			ServiceLocator::useInstance($previousLocator);
			$_REQUEST = $previousRequest;
			$_SERVER = $previousServer;
			$_SESSION = $previousSession;
		}
	}

	public function testConstructorStoresRequestedContinuationUrl(): void {
		$this->withServer(['ssocont' => 'https://client.example.test/return'], [], function(SingleSignOnServerAuth $auth): void {
			$this->assertSame('https://client.example.test/return', $_SESSION['ssocont']);
			$this->assertSame('https://client.example.test/return', $_SESSION['ssoreferer']);
			$this->assertSame(['depending_services' => 'Ok'], $auth->checkDependencies());
		});
	}

	public function testHttpRefererIsPreservedSeparatelyFromContinuation(): void {
		$this->withServer(['ssocont' => 'https://client.example.test/return'], ['HTTP_REFERER' => 'https://start.example.test/page'], function(SingleSignOnServerAuth $auth): void {
			$this->assertSame('https://start.example.test/page', $_SESSION['ssoreferer']);
			$this->assertSame('https://client.example.test/return', $_SESSION['ssocont']);
			$auth->finish(null);
		});
	}

	public function testMissingContinuationDoesNotWriteSSOState(): void {
		$this->withServer([], [], function(SingleSignOnServerAuth $auth): void {
			$this->assertSame([], $_SESSION);
			$this->assertNull($auth->finish(null));
		});
	}
}

require_once __DIR__ . '/../RecordingAuthToken.php';
