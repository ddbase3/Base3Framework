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

namespace Base3\Test\Middleware\Session;

use PHPUnit\Framework\TestCase;
use Base3\Core\Middleware\Session\SessionMiddleware;
use Base3\CoreFoundation\Middleware\Api\IMiddleware;
use Base3\CoreFoundation\Session\Api\ISession;

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
