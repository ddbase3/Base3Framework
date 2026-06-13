<?php declare(strict_types=1);

namespace Base3\Test\Hook;

use PHPUnit\Framework\TestCase;
use Base3\Hook\BootstrapLoggingHookListener;

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
}
