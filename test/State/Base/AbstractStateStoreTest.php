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

namespace Base3\Test\State\Base;

use Base3\State\Api\IStateStore;
use Base3\State\Base\AbstractStateStore;
use Base3\State\Database\DatabaseStateStore;
use Base3\State\No\NoStateStore;
use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class AbstractStateStoreTest extends TestCase {

	public function testEmptySubclassInheritsSafeEmptyReadDefaults(): void {
		$store = new EmptyStateBaseFixture();
		$this->assertInstanceOf(IStateStore::class, $store);
		$this->assertNull($store->get('key'));
		$this->assertSame('fallback', $store->get('key', 'fallback'));
		$this->assertFalse($store->has('key'));
		$this->assertFalse($store->delete('key'));
		$this->assertSame([], $store->listKeys('jobs.'));
		$store->flush();
		foreach ((new ReflectionClass(IStateStore::class))->getMethods() as $method) {
			$this->assertSame(AbstractStateStore::class, (new ReflectionMethod($store, $method->getName()))->getDeclaringClass()->getName());
		}
	}

	public function testWriteAndAtomicLockDefaultsCannotPretendSuccess(): void {
		$store = new EmptyStateBaseFixture();
		foreach ([
			'set' => static fn() => $store->set('key', 1),
			'setIfNotExists' => static fn() => $store->setIfNotExists('locks.job', 1, 60),
		] as $name => $operation) {
			try {
				$operation();
				$this->fail($name . ' must throw without storage.');
			} catch (LogicException $exception) {
				$this->assertStringContainsString($name . '()', $exception->getMessage());
			}
		}
	}

	public function testExistingStateStoresPreserveTheirOwnMethods(): void {
		foreach ([DatabaseStateStore::class, NoStateStore::class] as $class) {
			$this->assertTrue(is_subclass_of($class, AbstractStateStore::class));
			foreach ((new ReflectionClass(IStateStore::class))->getMethods() as $method) {
				$this->assertSame($class, (new ReflectionMethod($class, $method->getName()))->getDeclaringClass()->getName());
			}
		}
		$no = new NoStateStore();
		$this->assertTrue($no->setIfNotExists('key', 'value'));
		$this->assertFalse($no->has('key'));
	}
}
