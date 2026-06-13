<?php declare(strict_types=1);

namespace Base3\Test\Middleware\Session;

use PHPUnit\Framework\TestCase;
use Base3\Middleware\Session\SessionMiddleware;
use Base3\Middleware\Api\IMiddleware;
use Base3\Session\Api\ISession;

class SessionMiddlewareTest extends TestCase
{
	public function testProcessStartsSessionThenDelegatesToNext(): void
	{
		$session = $this->createMock(ISession::class);
		$next = $this->createMock(IMiddleware::class);

		$session->expects($this->once())
			->method('start')
			->willReturn(true);

		$next->expects($this->once())
			->method('process')
			->willReturn('OK');

		$mw = new SessionMiddleware($session);
		$mw->setNext($next);

		$this->assertSame('OK', $mw->process());
	}

	public function testStartIsCalledBeforeNextProcess(): void
	{
		$session = $this->createMock(ISession::class);
		$next = $this->createMock(IMiddleware::class);

		$callOrder = [];

		$session->expects($this->once())
			->method('start')
			->willReturnCallback(function () use (&$callOrder): bool {
				$callOrder[] = 'start';
				return true;
			});

		$next->expects($this->once())
			->method('process')
			->willReturnCallback(function () use (&$callOrder): string {
				$callOrder[] = 'next';
				return 'OK';
			});

		$mw = new SessionMiddleware($session);
		$mw->setNext($next);
		$mw->process();

		$this->assertSame(['start', 'next'], $callOrder);
	}
}
