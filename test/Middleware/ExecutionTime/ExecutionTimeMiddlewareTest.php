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

namespace Base3\Test\Middleware\ExecutionTime;

use PHPUnit\Framework\TestCase;
use Base3\Core\Middleware\ExecutionTime\ExecutionTimeMiddleware;
use Base3\CoreFoundation\Middleware\Api\IMiddleware;

class ExecutionTimeMiddlewareTest extends TestCase
{
	public function testProcessAppendsExecutionTimeHtmlComment(): void
	{
		$next = $this->createMock(IMiddleware::class);
		$next->expects($this->once())
			->method('process')
			->willReturn('BODY');

		$mw = new ExecutionTimeMiddleware();
		$mw->setNext($next);

		$out = $mw->process();

		$this->assertStringStartsWith('BODY', $out);
		$this->assertStringContainsString('<!-- execution time ', $out);
		$this->assertStringEndsWith(" ms -->\n", $out);

		// Extract number and ensure it's numeric (not negative)
		$this->assertMatchesRegularExpression('/<!-- execution time \d+ ms -->\n$/', $out);
	}

	public function testDelegatesToNextExactlyOnce(): void
	{
		$next = $this->createMock(IMiddleware::class);
		$next->expects($this->once())
			->method('process')
			->willReturn('OK');

		$mw = new ExecutionTimeMiddleware();
		$mw->setNext($next);

		$this->assertStringContainsString('OK', $mw->process());
	}
}
