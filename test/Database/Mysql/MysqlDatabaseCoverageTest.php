<?php declare(strict_types=1);

namespace Base3Test\Database\Mysql;

use Base3\Configuration\ArrayConfiguration;
use Base3\Database\Mysql\MysqlDatabase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class MysqlDatabaseCoverageTest extends TestCase {

	private function database(array $credentials = []): MysqlDatabase {
		return new MysqlDatabase(new ArrayConfiguration(['database' => $credentials]));
	}

	private function property(object $object, string $name): mixed {
		return (new \ReflectionProperty($object, $name))->getValue($object);
	}

	private function setProperty(object $object, string $name, mixed $value): void {
		(new \ReflectionProperty($object, $name))->setValue($object, $value);
	}

	/** @return array{0: MysqlDatabase, 1: \mysqli} */
	private function databaseWithConnection(): array {
		$db = $this->database();
		$connection = $this->getMockBuilder(\mysqli::class)
			->disableOriginalConstructor()
			->onlyMethods(['query', 'begin_transaction', 'commit', 'rollback', 'close', 'real_escape_string'])
			->getMock();
		$this->setProperty($db, 'connection', $connection);
		$this->setProperty($db, 'connected', true);
		return [$db, $connection];
	}

	public function testFlatConfigurationIsAssignedToPrivateConnectionParameters(): void {
		$db = MysqlDatabase::getInstance(['host' => 'db.example', 'user' => 'alice', 'pass' => 'secret', 'name' => 'demo']);
		$this->assertSame('db.example', $this->property($db, 'host'));
		$this->assertSame('alice', $this->property($db, 'user'));
		$this->assertSame('secret', $this->property($db, 'pass'));
		$this->assertSame('demo', $this->property($db, 'name'));
		$this->assertFalse($db->connected());
	}

	public function testNestedConfigurationIsNotWrappedTwice(): void {
		$db = MysqlDatabase::getInstance(['database' => ['host' => 'nested', 'user' => 'u', 'pass' => 'p', 'name' => 'n']]);
		$this->assertSame('nested', $this->property($db, 'host'));
		$this->assertSame('n', $this->property($db, 'name'));
	}

	public function testMissingConfigurationValuesRemainNull(): void {
		$db = MysqlDatabase::getInstance([]);
		$this->assertNull($this->property($db, 'host'));
		$this->assertNull($this->property($db, 'user'));
		$this->assertNull($this->property($db, 'pass'));
		$this->assertNull($this->property($db, 'name'));
	}

	public function testConstructorAcceptsAnIndependentConfiguration(): void {
		$db = $this->database(['host' => 'h', 'user' => 'u', 'pass' => 'p', 'name' => 'n']);
		$this->assertSame('h', $this->property($db, 'host'));
		$this->assertSame('u', $this->property($db, 'user'));
		$this->assertSame('p', $this->property($db, 'pass'));
		$this->assertSame('n', $this->property($db, 'name'));
	}

	public function testEveryMissingRequiredCredentialPreventsNetworkConnection(): void {
		foreach (['host', 'user', 'pass', 'name'] as $field) {
			$credentials = ['host' => 'h', 'user' => 'u', 'pass' => 'p', 'name' => 'n'];
			$credentials[$field] = '';
			$db = $this->database($credentials);
			$db->connect();
			$this->assertFalse($db->connected(), $field);
			$this->assertNull($this->property($db, 'connection'));
		}
	}

	public function testConnectingWhenAlreadyConnectedDoesNotReplaceConnection(): void {
		[$db, $connection] = $this->databaseWithConnection();
		$db->connect();
		$this->assertTrue($db->connected());
		$this->assertSame($connection, $this->property($db, 'connection'));
	}

	public function testDisconnectWithNoConnectionIsHarmless(): void {
		$db = $this->database();
		$db->disconnect();
		$this->assertFalse($db->connected());
		$this->assertNull($this->property($db, 'connection'));
	}

	public function testDisconnectDelegatesCloseToTheConnection(): void {
		[$db, $connection] = $this->databaseWithConnection();
		$connection->expects($this->once())->method('close')->willReturn(true);
		$db->disconnect();
		$this->assertFalse($db->connected());
	}

	public function testBeginWithoutConnectionFailsImmediately(): void {
		$db = $this->database();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('MySQL connection not available.');
		$db->beginTransaction();
	}

	public function testCommitWithoutConnectionFailsImmediately(): void {
		$db = $this->database();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('MySQL connection not available.');
		$db->commit();
	}

	public function testRollbackWithoutConnectionFailsImmediately(): void {
		$db = $this->database();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('MySQL connection not available.');
		$db->rollback();
	}

	public function testBeginTransactionDelegatesToMysqli(): void {
		[$db, $connection] = $this->databaseWithConnection();
		$connection->expects($this->once())->method('begin_transaction')->willReturn(true);
		$db->beginTransaction();
		$this->assertTrue($db->connected());
	}

	public function testCommitDelegatesToMysqli(): void {
		[$db, $connection] = $this->databaseWithConnection();
		$connection->expects($this->once())->method('commit')->willReturn(true);
		$db->commit();
		$this->assertTrue($db->connected());
	}

	public function testRollbackDelegatesToMysqli(): void {
		[$db, $connection] = $this->databaseWithConnection();
		$connection->expects($this->once())->method('rollback')->willReturn(true);
		$db->rollback();
		$this->assertTrue($db->connected());
	}




	public function testNonQueryDelegatesSqlWithoutImplicitConnection(): void {
		[$db, $connection] = $this->databaseWithConnection();
		$connection->expects($this->once())->method('query')->with('UPDATE demo SET x = 3')->willReturn(true);
		$db->nonQuery('UPDATE demo SET x = 3');
		$this->assertTrue($db->connected());
	}

	public function testNonQueryLeavesDriverFailureForErrorInspection(): void {
		[$db, $connection] = $this->databaseWithConnection();
		$connection->expects($this->once())->method('query')->with('INVALID QUERY')->willReturn(false);
		$db->nonQuery('INVALID QUERY');
		$this->assertTrue($db->connected());
	}

	public function testScalarQueryReturnsNullWhenDriverFails(): void {
		[$db, $connection] = $this->databaseWithConnection();
		$connection->expects($this->once())->method('query')->with('SELECT value')->willReturn(false);
		$this->assertNull($db->scalarQuery('SELECT value'));
	}

	public function testSingleQueryReturnsNullWhenDriverFails(): void {
		[$db, $connection] = $this->databaseWithConnection();
		$connection->expects($this->once())->method('query')->with('SELECT *')->willReturn(false);
		$this->assertNull($db->singleQuery('SELECT *'));
	}

	public function testListQueryReturnsEmptyListWhenDriverFails(): void {
		[$db, $connection] = $this->databaseWithConnection();
		$connection->expects($this->once())->method('query')->with('SELECT id')->willReturn(false);
		$list = $db->listQuery('SELECT id');
		$this->assertSame([], $list);
	}

	public function testMultiQueryReturnsEmptyListWhenDriverFails(): void {
		[$db, $connection] = $this->databaseWithConnection();
		$connection->expects($this->once())->method('query')->with('SELECT *')->willReturn(false);
		$rows = $db->multiQuery('SELECT *');
		$this->assertSame([], $rows);
	}

	public function testEscapingIsDelegatedWithoutChangingTheInput(): void {
		[$db, $connection] = $this->databaseWithConnection();
		$connection->expects($this->once())->method('real_escape_string')
			->with("O'Reilly")->willReturn("O\\'Reilly");
		$this->assertSame("O\\'Reilly", $db->escape("O'Reilly"));
	}


	public function testDependencyCheckReportsUnconfiguredDatabase(): void {
		$db = $this->database();
		$this->assertSame(['mysql_connected' => 'Not connected'], $db->checkDependencies());
	}

	public function testDependencyCheckAcceptsAnInjectedConnection(): void {
		[$db, $connection] = $this->databaseWithConnection();
		$this->assertSame([
			'mysql_connected' => $connection->connect_errno ? $connection->connect_error : 'Ok'
		], $db->checkDependencies());
	}
}
