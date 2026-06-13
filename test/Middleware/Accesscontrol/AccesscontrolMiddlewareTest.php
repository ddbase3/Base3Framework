<?php declare(strict_types=1);

namespace Base3\Test\Middleware\Accesscontrol;

use PHPUnit\Framework\TestCase;
use Base3\Middleware\Accesscontrol\AccesscontrolMiddleware;
use Base3\Middleware\Api\IMiddleware;
use Base3\Accesscontrol\Api\IAccesscontrol;

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
