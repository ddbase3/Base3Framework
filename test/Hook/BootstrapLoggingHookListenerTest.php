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

namespace Base3\Test\Hook;

use PHPUnit\Framework\TestCase;
use Base3\Core\Hook\BootstrapLoggingHookListener;

class BootstrapLoggingHookListenerTest extends TestCase
{
	private BootstrapLoggingHookListener $listener;

	protected function setUp(): void
	{
		$this->listener = new BootstrapLoggingHookListener();
	}

	public function testIsActiveReturnsFalse(): void
	{
		$this->assertFalse($this->listener->isActive());
	}

	public function testGetSubscribedHooksReturnsExpectedArray(): void
	{
		$this->assertSame([
			'bootstrap.init' => 10,
			'bootstrap.start' => 10,
			'bootstrap.finish' => 10,
		], BootstrapLoggingHookListener::getSubscribedHooks());
	}

	public function testHandleReturnsTrue(): void
	{
		$result = $this->listener->handle('bootstrap.init');
		$this->assertTrue($result);
	}

	public function testHandleAcceptsAdditionalArguments(): void
	{
		$result = $this->listener->handle('bootstrap.start', ['foo' => 'bar'], 123, true);
		$this->assertTrue($result);
	}

	public function testHandleDoesNotWriteToErrorLog(): void
	{
		$logFile = tempnam(sys_get_temp_dir(), 'base3-listener-');
		$this->assertNotFalse($logFile);
		$previousLogFile = ini_get('error_log');

		try {
			ini_set('error_log', $logFile);
			$this->assertTrue($this->listener->handle('bootstrap.init'));
			$this->assertSame('', file_get_contents($logFile));
		} finally {
			ini_set('error_log', $previousLogFile === false ? '' : $previousLogFile);
			unlink($logFile);
		}
	}
}
