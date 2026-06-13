<?php declare(strict_types=1);

namespace Base3\Test\Middleware\ExecutionTime;

use PHPUnit\Framework\TestCase;
use Base3\Middleware\ExecutionTime\ExecutionTimeMiddleware;
use Base3\Middleware\Api\IMiddleware;

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
