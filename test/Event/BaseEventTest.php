<?php declare(strict_types=1);

namespace Base3\Test\Event;

use PHPUnit\Framework\TestCase;
use Base3\Event\BaseEvent;

class BaseEventTest extends TestCase
{
	public function testIsPropagationStoppedIsFalseByDefault(): void
	{
		$event = new BaseEvent();
		$this->assertFalse($event->isPropagationStopped());
	}

	public function testStopPropagationStopsEvent(): void
	{
		$event = new BaseEvent();

		$event->stopPropagation();

		$this->assertTrue($event->isPropagationStopped());
	}
}
