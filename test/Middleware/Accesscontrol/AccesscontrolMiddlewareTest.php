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

namespace Base3\Test\Middleware\Accesscontrol;

use PHPUnit\Framework\TestCase;
use Base3\Core\Middleware\Accesscontrol\AccesscontrolMiddleware;
use Base3\CoreFoundation\Middleware\Api\IMiddleware;
use Base3\CoreFoundation\Accesscontrol\Api\IAccesscontrol;

class AccesscontrolMiddlewareTest extends TestCase
{
	public function testProcessCallsAuthenticateThenDelegatesToNext(): void
	{
		$accesscontrol = $this->createMock(IAccesscontrol::class);
		$next = $this->createMock(IMiddleware::class);

		$accesscontrol->expects($this->once())
			->method('authenticate');

		$next->expects($this->once())
			->method('process')
			->willReturn('OK');

		$mw = new AccesscontrolMiddleware($accesscontrol);
		$mw->setNext($next);

		$this->assertSame('OK', $mw->process());
	}

	public function testAuthenticateIsCalledBeforeNextProcess(): void
	{
		$accesscontrol = $this->createMock(IAccesscontrol::class);
		$next = $this->createMock(IMiddleware::class);

		$callOrder = [];

		$accesscontrol->expects($this->once())
			->method('authenticate')
			->willReturnCallback(function () use (&$callOrder): void {
				$callOrder[] = 'auth';
			});

		$next->expects($this->once())
			->method('process')
			->willReturnCallback(function () use (&$callOrder): string {
				$callOrder[] = 'next';
				return 'OK';
			});

		$mw = new AccesscontrolMiddleware($accesscontrol);
		$mw->setNext($next);
		$mw->process();

		$this->assertSame(['auth', 'next'], $callOrder);
	}
}
