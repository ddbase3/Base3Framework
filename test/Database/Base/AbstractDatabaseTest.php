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

namespace Base3\Test\Database\Base;

use Base3\Database\Api\IDatabase;
use Base3\Database\Base\AbstractDatabase;
use Base3\Database\Mysql\MysqlDatabase;
use Base3\Database\Postgres\PostgresDatabase;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use LogicException;

final class AbstractDatabaseTest extends TestCase {

	public function testEmptySubclassInheritsCompleteInterface(): void {
		$database = new EmptyDatabaseBaseFixture();
		$this->assertInstanceOf(IDatabase::class, $database);
		foreach ((new ReflectionClass(IDatabase::class))->getMethods() as $method) {
			$this->assertTrue(method_exists($database, $method->getName()));
			$this->assertSame(AbstractDatabase::class, (new ReflectionMethod($database, $method->getName()))->getDeclaringClass()->getName());
		}
		$this->assertTrue((new ReflectionMethod(AbstractDatabase::class, 'listQuery'))->returnsReference());
		$this->assertTrue((new ReflectionMethod(AbstractDatabase::class, 'multiQuery'))->returnsReference());
	}

	public function testEmptyBaseHasOnlyDisconnectedInspectionDefaults(): void {
		$database = new EmptyDatabaseBaseFixture();
		$this->assertFalse($database->connected());
		$database->disconnect();
		$this->assertSame(0, $database->affectedRows());
		$this->assertSame(0, $database->insertId());
		$this->assertFalse($database->isError());
		$this->assertSame(0, $database->errorNumber());
		$this->assertSame('', $database->errorMessage());
	}

	public function testOperationsRequiringBackendFailExplicitly(): void {
		$database = new EmptyDatabaseBaseFixture();
		$operations = [
			'connect' => static fn() => $database->connect(),
			'beginTransaction' => static fn() => $database->beginTransaction(),
			'commit' => static fn() => $database->commit(),
			'rollback' => static fn() => $database->rollback(),
			'nonQuery' => static fn() => $database->nonQuery('UPDATE demo SET x = 1'),
			'scalarQuery' => static fn() => $database->scalarQuery('SELECT 1'),
			'singleQuery' => static fn() => $database->singleQuery('SELECT 1'),
			'listQuery' => static fn() => $database->listQuery('SELECT 1'),
			'multiQuery' => static fn() => $database->multiQuery('SELECT 1'),
			'escape' => static fn() => $database->escape("'"),
		];
		foreach ($operations as $name => $operation) {
			try {
				$operation();
				$this->fail($name . ' must throw without a backend.');
			} catch (LogicException $exception) {
				$this->assertStringContainsString($name . '()', $exception->getMessage());
			}
		}
	}

	public function testExistingBackendsContinueOverridingEveryContractMethod(): void {
		foreach ([MysqlDatabase::class, PostgresDatabase::class] as $class) {
			$this->assertTrue(is_subclass_of($class, AbstractDatabase::class));
			foreach ((new ReflectionClass(IDatabase::class))->getMethods() as $method) {
				$this->assertSame($class, (new ReflectionMethod($class, $method->getName()))->getDeclaringClass()->getName());
			}
		}
	}
}
