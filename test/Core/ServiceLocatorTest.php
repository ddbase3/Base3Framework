<?php declare(strict_types=1);

namespace Base3\Core;

use Base3\Api\IContainer;
use PHPUnit\Framework\TestCase;

final class ServiceLocatorTest extends TestCase {

	protected function tearDown(): void {
		$this->resetSingletons();
	}

	private function resetSingletons(): void {
		$ref = new \ReflectionClass(ServiceLocator::class);

		foreach (['instance', 'externalInstance'] as $propName) {
			$prop = $ref->getProperty($propName);
			$prop->setAccessible(true);
			$prop->setValue(null, null);
		}
	}

	private function freshLocatorAsExternal(): ServiceLocator {
		$this->resetSingletons();
		$locator = new ServiceLocator();
		ServiceLocator::useInstance($locator);
		return $locator;
	}

	public function testGetInstanceReturnsExternalInstanceWhenSet(): void {
		$external = $this->freshLocatorAsExternal();

		self::assertSame($external, ServiceLocator::getInstance());
	}

	public function testGetInstanceCreatesSingletonWhenNoExternalInstance(): void {
		$this->resetSingletons();

		$a = ServiceLocator::getInstance();
		$b = ServiceLocator::getInstance();

		self::assertSame($a, $b);
	}

	public function testArrayAccessOffsetExistsAndGetDelegatesToHasAndGet(): void {
		$sl = $this->freshLocatorAsExternal();

		self::assertFalse(isset($sl['foo']));
		self::assertNull($sl['foo']);

		$sl->set('foo', 'bar', IContainer::PARAMETER);

		self::assertTrue(isset($sl['foo']));
		self::assertSame('bar', $sl['foo']);
	}

	public function testArrayAccessOffsetSetThrows(): void {
		$sl = $this->freshLocatorAsExternal();

		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage('Setting services via array access is not supported.');

		$sl['x'] = 'y';
	}

	public function testArrayAccessOffsetUnsetThrows(): void {
		$sl = $this->freshLocatorAsExternal();

		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage('Unsetting services via array access is not supported.');

		unset($sl['x']);
	}

	public function testGetServiceListIncludesServicesAliasesAndParameters(): void {
		$sl = $this->freshLocatorAsExternal();

		$sl->set('svc', function() { return new DummyService(); });
		$sl->set('param', 123, IContainer::PARAMETER);
		$sl->set('alias', 'svc', IContainer::ALIAS);

		$list = $sl->getServiceList();

		self::assertContains('svc', $list);
		self::assertContains('param', $list);
		self::assertContains('alias', $list);
	}

	public function testHasReturnsTrueForServiceAliasAndParameter(): void {
		$sl = $this->freshLocatorAsExternal();

		$sl->set('svc', function() { return new DummyService(); });
		$sl->set('param', 'x', IContainer::PARAMETER);
		$sl->set('alias', 'svc', IContainer::ALIAS);

		self::assertTrue($sl->has('svc'));
		self::assertTrue($sl->has('param'));
		self::assertTrue($sl->has('alias'));
		self::assertFalse($sl->has('missing'));
	}

	public function testRemoveRemovesFromAllContainers(): void {
		$sl = $this->freshLocatorAsExternal();

		$sl->set('svc', function() { return new DummyService(); });
		$sl->set('param', 'x', IContainer::PARAMETER);
		$sl->set('alias', 'svc', IContainer::ALIAS);

		$sl->remove('svc');
		self::assertFalse($sl->has('svc'));
		self::assertTrue($sl->has('alias')); // alias still exists, but points to removed target

		$sl->remove('alias');
		self::assertFalse($sl->has('alias'));

		$sl->remove('param');
		self::assertFalse($sl->has('param'));
	}

	public function testSetWithNoOverwriteDoesNotReplaceExistingEntry(): void {
		$sl = $this->freshLocatorAsExternal();

		$sl->set('p', 'first', IContainer::PARAMETER);
		$sl->set('p', 'second', IContainer::PARAMETER | IContainer::NOOVERWRITE);

		self::assertSame('first', $sl->get('p'));
	}

	public function testSetRemovesExistingEntriesAcrossContainersBeforeRegistering(): void {
		$sl = $this->freshLocatorAsExternal();

		$sl->set('x', 'value', IContainer::PARAMETER);
		self::assertSame('value', $sl->get('x'));

		$sl->set('x', function() { return new DummyService(); });
		$got = $sl->get('x');

		self::assertInstanceOf(DummyService::class, $got);
	}

	public function testSetParameterStoresPlainValue(): void {
		$sl = $this->freshLocatorAsExternal();

		$sl->set('param', ['a' => 1], IContainer::PARAMETER);

		self::assertSame(['a' => 1], $sl->get('param'));
	}

	public function testSetAliasResolvesToTargetService(): void {
		$sl = $this->freshLocatorAsExternal();

		$sl->set('svc', function() { return new DummyService(); }, IContainer::SHARED);
		$sl->set('alias', 'svc', IContainer::ALIAS);

		$a = $sl->get('svc');
		$b = $sl->get('alias');

		self::assertInstanceOf(DummyService::class, $a);
		self::assertSame($a, $b);
	}

	public function testSetAliasThrowsIfTargetMissing(): void {
		$sl = $this->freshLocatorAsExternal();

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage("Cannot create alias: Target service 'missing' not found.");

		$sl->set('alias', 'missing', IContainer::ALIAS);
	}

	public function testGetReturnsNullForUnknownService(): void {
		$sl = $this->freshLocatorAsExternal();

		self::assertNull($sl->get('does_not_exist'));
	}

	public function testGetReturnsScalarNullOrArrayDefinitionsDirectly(): void {
		$sl = $this->freshLocatorAsExternal();

		$sl->set('nullv', null);
		$sl->set('scalar', 42);
		$sl->set('arr', ['x' => 1]);

		self::assertNull($sl->get('nullv'));
		self::assertSame(42, $sl->get('scalar'));
		self::assertSame(['x' => 1], $sl->get('arr'));
	}

	public function testNonSharedServiceCreatesNewInstanceEachTimeFromCallable(): void {
		$sl = $this->freshLocatorAsExternal();

		$sl->set('svc', function() { return new DummyService(); });

		$a = $sl->get('svc');
		$b = $sl->get('svc');

		self::assertInstanceOf(DummyService::class, $a);
		self::assertInstanceOf(DummyService::class, $b);
		self::assertNotSame($a, $b);
	}

	public function testSharedServiceReturnsSameInstanceFromCallable(): void {
		$sl = $this->freshLocatorAsExternal();

		$sl->set('svc', function() { return new DummyService(); }, IContainer::SHARED);

		$a = $sl->get('svc');
		$b = $sl->get('svc');

		self::assertInstanceOf(DummyService::class, $a);
		self::assertSame($a, $b);
	}

	public function testSharedServiceWithObjectDefinitionReturnsSameObject(): void {
		$sl = $this->freshLocatorAsExternal();

		$obj = new DummyService();
		$sl->set('svc', $obj, IContainer::SHARED);

		self::assertSame($obj, $sl->get('svc'));
		self::assertSame($obj, $sl->get('svc'));
	}

	public function testNonSharedServiceWithObjectDefinitionClonesByRecreatingClass(): void {
		$sl = $this->freshLocatorAsExternal();

		$obj = new DummyService();
		$sl->set('svc', $obj);

		$a = $sl->get('svc');
		$b = $sl->get('svc');

		self::assertInstanceOf(DummyService::class, $a);
		self::assertInstanceOf(DummyService::class, $b);
		self::assertNotSame($obj, $a);
		self::assertNotSame($a, $b);
	}

	public function testCallableDefinitionClosureWithContainerParameterReceivesContainer(): void {
		$sl = $this->freshLocatorAsExternal();

		$sl->set('svc', function(ServiceLocator $c) {
			return new DummyWithContainer($c);
		});

		$inst = $sl->get('svc');

		self::assertInstanceOf(DummyWithContainer::class, $inst);
		self::assertSame($sl, $inst->container);
	}

	public function testCallableDefinitionClosureWithoutParametersIsCalledWithoutArguments(): void {
		$sl = $this->freshLocatorAsExternal();

		$sl->set('svc', function() {
			return new DummyService();
		});

		$inst = $sl->get('svc');

		self::assertInstanceOf(DummyService::class, $inst);
	}

	public function testInvokableCallableWithContainerParameterReceivesContainer(): void {
		$sl = $this->freshLocatorAsExternal();

		$sl->set('svc', new InvokableWithParam(), IContainer::SHARED);

		$inst = $sl->get('svc');

		self::assertInstanceOf(DummyWithContainer::class, $inst);
		self::assertSame($sl, $inst->container);
		self::assertSame($inst, $sl->get('svc')); // shared
	}

	public function testInvokableCallableWithoutParametersIsCalledWithoutArguments(): void {
		$sl = $this->freshLocatorAsExternal();

		$sl->set('svc', new InvokableNoParam());

		$inst = $sl->get('svc');

		self::assertInstanceOf(DummyService::class, $inst);
	}

	public function testDeprecatedBooleanFlagsEnableSharedServices(): void {
		$sl = $this->freshLocatorAsExternal();

		$sl->set('svc', function() { return new DummyService(); }, true);

		$a = $sl->get('svc');
		$b = $sl->get('svc');

		self::assertSame($a, $b);
	}

	public function testDeprecatedBooleanFlagsDisableSharedServices(): void {
		$sl = $this->freshLocatorAsExternal();

		$sl->set('svc', function() { return new DummyService(); }, false);

		$a = $sl->get('svc');
		$b = $sl->get('svc');

		self::assertNotSame($a, $b);
	}
}

final class DummyService {}

final class DummyWithContainer {
	public ServiceLocator $container;

	public function __construct(ServiceLocator $container) {
		$this->container = $container;
	}
}

final class InvokableWithParam {
	public function __invoke(ServiceLocator $container): DummyWithContainer {
		return new DummyWithContainer($container);
	}
}

final class InvokableNoParam {
	public function __invoke(): DummyService {
		return new DummyService();
	}
}
