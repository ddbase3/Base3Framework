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

namespace Base3\Test\Settings\Base;

use Base3\Settings\Api\ISettingsStore;
use Base3\Settings\Base\AbstractSettingsStore;
use Base3\Settings\Database\DatabaseSettingsStore;
use Base3\Settings\Json\JsonSettingsStore;
use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class AbstractSettingsStoreTest extends TestCase {

	public function testEmptySubclassHasContractCompleteEmptyReadDefaults(): void {
		$store = new EmptySettingsBaseFixture();
		$this->assertInstanceOf(ISettingsStore::class, $store);
		$this->assertSame(['fallback' => true], $store->get('group', 'missing', ['fallback' => true]));
		$this->assertSame([], $store->get('group', 'missing'));
		$this->assertFalse($store->has('group', 'missing'));
		$this->assertSame([], $store->getGroup('group'));
		$store->save();
		$store->reload();
		foreach ((new ReflectionClass(ISettingsStore::class))->getMethods() as $method) {
			$this->assertSame(AbstractSettingsStore::class, (new ReflectionMethod($store, $method->getName()))->getDeclaringClass()->getName());
		}
	}

	public function testWritesCannotSilentlyDiscardSettings(): void {
		$store = new EmptySettingsBaseFixture();
		foreach ([
			'set' => static fn() => $store->set('group', 'name', ['x' => 1]),
			'remove' => static fn() => $store->remove('group', 'name'),
		] as $name => $operation) {
			try {
				$operation();
				$this->fail($name . ' must throw without storage.');
			} catch (LogicException $exception) {
				$this->assertStringContainsString($name . '()', $exception->getMessage());
			}
		}
	}

	public function testExistingStoresContinueOverridingEntireContract(): void {
		foreach ([JsonSettingsStore::class, DatabaseSettingsStore::class] as $class) {
			$this->assertTrue(is_subclass_of($class, AbstractSettingsStore::class));
			foreach ((new ReflectionClass(ISettingsStore::class))->getMethods() as $method) {
				$this->assertSame($class, (new ReflectionMethod($class, $method->getName()))->getDeclaringClass()->getName());
			}
		}
	}
}
