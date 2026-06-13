<?php declare(strict_types=1);

namespace Base3\Event;

if (!class_exists(\Base3\Event\StoppableEvent::class)) {
	/**
	 * Test fallback for EventManager's instanceof-check.
	 * Only defined when the project does not provide it.
	 */
	class StoppableEvent extends BaseEvent {}
}

namespace Base3\Test\Event;

use PHPUnit\Framework\TestCase;
use Base3\Event\EventManager;

class DummyEvent {}

class EventManagerTest extends TestCase
{
	private EventManager $em;

	protected function setUp(): void
	{
		$this->em = new EventManager();
	}

	public function testOnAndFireWithStringEvent(): void
	{
		$this->em->on('test.event', function ($event, $arg) {
			$this->assertSame('test.event', $event);
			$this->assertSame(123, $arg);
			return 'ok';
		});

		$result = $this->em->fire('test.event', 123);

		$this->assertSame(['ok'], $result);
	}

	public function testFireWithObjectUsesClassNameAndMatchesViaWildcardPattern(): void
	{
		$event = new DummyEvent();

		// Do not use namespace patterns with "\" for fnmatch; match by suffix instead.
		$this->em->on('*DummyEvent', function ($received) use ($event) {
			$this->assertSame($event, $received);
			return 'handled';
		});

		$result = $this->em->fire($event);

		$this->assertSame(['handled'], $result);
	}

	public function testPriorityOrderHigherFirst(): void
	{
		$order = [];

		$this->em->on('prio', function () use (&$order) {
			$order[] = 'low';
			return 'low';
		}, 0);

		$this->em->on('prio', function () use (&$order) {
			$order[] = 'high';
			return 'high';
		}, 10);

		$result = $this->em->fire('prio');

		$this->assertSame(['high', 'low'], $order);
		$this->assertSame(['high', 'low'], $result);
	}

	public function testWildcardMatching(): void
	{
		$this->em->on('User*', function ($event) {
			$this->assertSame('UserCreated', $event);
			return 'matched';
		});

		$result = $this->em->fire('UserCreated');

		$this->assertSame(['matched'], $result);
	}

	public function testOffRemovesListenerAndCleansUp(): void
	{
		$calls = 0;

		$listener1 = function () use (&$calls) {
			$calls++;
			return 'one';
		};

		$listener2 = function () use (&$calls) {
			$calls++;
			return 'two';
		};

		$this->em->on('ev', $listener1);
		$this->em->on('ev', $listener2);

		$this->em->off('does.not.exist', $listener1); // should do nothing
		$this->em->off('ev', $listener1);

		$result = $this->em->fire('ev');

		$this->assertSame(1, $calls);
		$this->assertSame(['two'], $result);

		$this->em->off('ev', $listener2);
		$result2 = $this->em->fire('ev');

		$this->assertSame([], $result2);
	}

	public function testOnceIsExecutedOnlyOnce(): void
	{
		$count = 0;

		$this->em->once('once.test', function () use (&$count) {
			$count++;
			return $count;
		});

		$r1 = $this->em->fire('once.test');
		$r2 = $this->em->fire('once.test');

		$this->assertSame([1], $r1);
		$this->assertSame([], $r2);
		$this->assertSame(1, $count);
	}

	public function testStoppableEventStopsPropagation(): void
	{
		$event = new \Base3\Event\StoppableEvent();
		$called = [];

		$this->em->on('*StoppableEvent', function ($e) use (&$called) {
			$called[] = 'first';
			$e->stopPropagation();
			return 'first';
		}, 10);

		$this->em->on('*StoppableEvent', function () use (&$called) {
			$called[] = 'second';
			return 'second';
		}, 0);

		$result = $this->em->fire($event);

		$this->assertSame(['first'], $called);
		$this->assertSame(['first'], $result);
	}
}
