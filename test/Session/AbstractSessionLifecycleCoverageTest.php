<?php declare(strict_types=1);

namespace Base3\Test\Session;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class AbstractSessionLifecycleCoverageTest extends TestCase {

	private function withRealSession(callable $assertions): void {
		$previousCookieSetting = ini_get('session.use_cookies');
		ini_set('session.use_cookies', '0');
		$sessionId = 'base3test' . bin2hex(random_bytes(8));
		try {
			session_id($sessionId);
			if (!session_start()) $this->fail('Cannot open temporary PHP session.');
			$assertions();
		} finally {
			if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
			$_SESSION = [];
			ini_set('session.use_cookies', (string)$previousCookieSetting);
		}
	}

	#[RunInSeparateProcess]
	public function testActivePhpSessionIsNotAccessibleBeforeOwnerStarts(): void {
		$this->withRealSession(function(): void {
			$_SESSION['secret'] = 'keep';
			$session = new ActiveSessionFixture();
			$this->assertFalse($session->started());
			$this->assertSame('', $session->getId());
			$this->assertSame('fallback', $session->get('secret', 'fallback'));
			$this->assertFalse($session->has('secret'));
			$session->set('secret', 'changed');
			$session->remove('secret');
			$this->assertSame('keep', $_SESSION['secret']);
			$this->assertFalse($session->destroy());
		});
	}

	#[RunInSeparateProcess]
	public function testActiveSessionReadsWritesAndRemovesValues(): void {
		$this->withRealSession(function(): void {
			$session = new ActiveSessionFixture();
			$this->assertTrue($session->start());
			$this->assertTrue($session->started());
			$this->assertSame(session_id(), $session->getId());
			$session->set('counter', 1);
			$this->assertTrue($session->has('counter'));
			$this->assertSame(1, $session->get('counter'));
			$session->set('counter', 2);
			$this->assertSame(2, $session->get('counter'));
			$session->remove('counter');
			$this->assertFalse($session->has('counter'));
			$this->assertSame(99, $session->get('counter', 99));
		});
	}

	#[RunInSeparateProcess]
	public function testStoredNullIsDistinctFromMissingKey(): void {
		$this->withRealSession(function(): void {
			$session = new ActiveSessionFixture();
			$session->start();
			$session->set('nil', null);
			$this->assertTrue($session->has('nil'));
			$this->assertNull($session->get('nil', 'fallback'));
			$this->assertSame('fallback', $session->get('unknown', 'fallback'));
			$session->remove('nil');
			$this->assertFalse($session->has('nil'));
		});
	}

	#[RunInSeparateProcess]
	public function testStoredObjectsRetainIdentityAcrossReads(): void {
		$this->withRealSession(function(): void {
			$session = new ActiveSessionFixture();
			$session->start();
			$object = (object)['id' => 7];
			$session->set('object', $object);
			$this->assertSame($object, $session->get('object'));
			$this->assertTrue($session->has('object'));
		});
	}

	#[RunInSeparateProcess]
	public function testDestroyClearsDataAndSubsequentCallsCannotDestroyAgain(): void {
		$this->withRealSession(function(): void {
			$session = new ActiveSessionFixture();
			$session->start();
			$session->set('secret', 'value');
			$this->assertTrue($session->destroy());
			$this->assertFalse($session->started());
			$this->assertSame('', $session->getId());
			$this->assertSame([], $_SESSION);
			$this->assertFalse($session->destroy());
			$this->assertSame('default', $session->get('secret','default'));
		});
	}

	#[RunInSeparateProcess]
	public function testFlagAloneCannotMakePhpSessionActive(): void {
		$session = new ActiveSessionFixture();
		$this->assertFalse($session->start());
		$this->assertFalse($session->started());
		$this->assertSame('', $session->getId());
		$session->set('secret', 'value');
		$this->assertFalse($session->has('secret'));
	}
}

require_once __DIR__ . '/ActiveSessionFixture.php';
