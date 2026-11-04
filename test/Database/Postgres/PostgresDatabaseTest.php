<?php declare(strict_types=1);

namespace Base3\Database\Postgres;

// --- pg_* function stubs ------------------------------------------------------
// The class under test uses unqualified pg_* calls.
// Avoid passing object properties by reference (array_shift on property is problematic).



function pg_connect(string $dsn) {
	return PgStubState::$connectOk ? (object)['dsn' => $dsn] : false;
}

function pg_close($conn) {
	return true;
}

function pg_query($conn, string $query) {
	if (count(PgStubState::$queryQueue) === 0) return false;
	return array_shift(PgStubState::$queryQueue);
}

function pg_num_rows($result) {
	return $result->num_rows ?? 0;
}

function pg_fetch_row($result) {
	if (!isset($result->rows) || !is_array($result->rows)) return false;
	if (!isset($result->__posRow)) $result->__posRow = 0;
	$pos = $result->__posRow;
	if (!array_key_exists($pos, $result->rows)) return false;
	$result->__posRow = $pos + 1;
	return $result->rows[$pos];
}

function pg_fetch_assoc($result) {
	if (!isset($result->rowsAssoc) || !is_array($result->rowsAssoc)) return false;
	if (!isset($result->__posAssoc)) $result->__posAssoc = 0;
	$pos = $result->__posAssoc;
	if (!array_key_exists($pos, $result->rowsAssoc)) return false;
	$result->__posAssoc = $pos + 1;
	return $result->rowsAssoc[$pos];
}

function pg_free_result($result) {
	$result->freed = true;
	return true;
}

function pg_affected_rows($conn) {
	return PgStubState::$affectedRows;
}

function pg_escape_string($conn, string $str) {
	return addslashes($str);
}

function pg_last_error($conn) {
	return PgStubState::$lastError;
}

// -----------------------------------------------------------------------------

namespace Base3Test\Database\Postgres;

use Base3\Api\IContainer;
use Base3\Configuration\Api\IConfiguration;
use Base3\Configuration\ArrayConfiguration;
use Base3\Core\ServiceLocator;
use Base3\Database\Postgres\PgStubState;
use Base3\Database\Postgres\PostgresDatabase;
use Base3\Test\Configuration\ConfigurationStub;
use PHPUnit\Framework\TestCase;

final class PostgresDatabaseTest extends TestCase {

	private function makeConfig(array $databaseCnf): IConfiguration {
		return new ArrayConfiguration(['database' => $databaseCnf]);
	}

	protected function setUp(): void {
		PgStubState::reset();
	}

	private function resetStaticServiceLocator(): void {
		$ref = new \ReflectionClass(PostgresDatabase::class);
		$p = $ref->getProperty('servicelocator');
		$p->setAccessible(true);
		$p->setValue(null, null);
	}

	private function getPrivate(object $obj, string $prop) {
		$ref = new \ReflectionClass($obj);
		$p = $ref->getProperty($prop);
		$p->setAccessible(true);
		return $p->getValue($obj);
	}

	public function testGetInstanceUsesServiceLocatorConfigurationWhenAvailable(): void {
		$this->resetStaticServiceLocator();

		$sl = new ServiceLocator();
		$sl->set('configuration', $this->makeConfig([
			'host' => 'h',
			'user' => 'u',
			'pass' => 'p',
			'name' => 'n',
		]), IContainer::PARAMETER);

		ServiceLocator::useInstance($sl);

		$db = PostgresDatabase::getInstance(null);
		$this->assertInstanceOf(PostgresDatabase::class, $db);
		$this->assertFalse($db->connected());
	}

	public function testGetInstanceWithInlineConfigCreatesInstance(): void {
		$db = PostgresDatabase::getInstance([
			'host' => 'h',
			'user' => 'u',
			'pass' => 'p',
			'name' => 'n',
		]);

		$this->assertInstanceOf(PostgresDatabase::class, $db);
		$this->assertFalse($db->connected());
	}

	public function testConnectAndDisconnect(): void {
		$db = new PostgresDatabase($this->makeConfig([
			'host' => 'h',
			'user' => 'u',
			'pass' => 'p',
			'name' => 'n',
		]));

		PgStubState::$connectOk = false;
		$db->connect();
		$this->assertFalse($db->connected());

		PgStubState::$connectOk = true;
		$db->connect();
		$this->assertTrue($db->connected());
		$this->assertNotNull($this->getPrivate($db, 'connection'));

		$db->disconnect();
		$this->assertFalse($db->connected());
	}

	public function testQueryHelpers(): void {
		$db = new PostgresDatabase($this->makeConfig([
			'host' => 'h',
			'user' => 'u',
			'pass' => 'p',
			'name' => 'n',
		]));
		$db->connect();

		// scalarQuery
		PgStubState::$queryQueue[] = (object)['num_rows' => 0, 'rows' => [], 'freed' => false];
		PgStubState::$queryQueue[] = (object)['num_rows' => 1, 'rows' => [[42]], 'freed' => false];

		// singleQuery
		PgStubState::$queryQueue[] = (object)['num_rows' => 0, 'rowsAssoc' => [], 'freed' => false];
		PgStubState::$queryQueue[] = (object)['num_rows' => 1, 'rowsAssoc' => [['a' => 1]], 'freed' => false];

		// listQuery
		PgStubState::$queryQueue[] = (object)['num_rows' => 0, 'rows' => [], 'freed' => false];
		PgStubState::$queryQueue[] = (object)['num_rows' => 3, 'rows' => [[1], [2], [3]], 'freed' => false];

		// multiQuery
		PgStubState::$queryQueue[] = (object)['num_rows' => 0, 'rowsAssoc' => [], 'freed' => false];
		PgStubState::$queryQueue[] = (object)['num_rows' => 2, 'rowsAssoc' => [['x' => 'y'], ['x' => 'z']], 'freed' => false];

		// insertId (LASTVAL)
		PgStubState::$queryQueue[] = (object)['num_rows' => 1, 'rows' => [[123]], 'freed' => false];

		$this->assertNull($db->scalarQuery('SELECT 1'));
		$this->assertSame(42, $db->scalarQuery('SELECT 42'));

		$this->assertNull($db->singleQuery('SELECT 1'));
		$this->assertSame(['a' => 1], $db->singleQuery('SELECT a'));

		$list = $db->listQuery('SELECT a');
		$this->assertSame([], $list);
		$list = $db->listQuery('SELECT a');
		$this->assertSame([1, 2, 3], $list);

		$rows = $db->multiQuery('SELECT *');
		$this->assertSame([], $rows);
		$rows = $db->multiQuery('SELECT *');
		$this->assertSame([['x' => 'y'], ['x' => 'z']], $rows);

		$this->assertSame(123, $db->insertId());

		PgStubState::$affectedRows = 7;
		$this->assertSame(0, $db->affectedRows()); // PostgreSQL IDatabase adapter cannot report last affected rows.

		$this->assertSame(addslashes("a'b"), $db->escape("a'b"));

		// nonQuery consumes one result just as the native PostgreSQL driver does.
		PgStubState::$queryQueue[] = (object)['num_rows' => 0, 'freed' => false];
		$db->nonQuery('UPDATE t SET a=1');
	}

	public function testFailedQueriesThrowInsteadOfReturningEmptyResults(): void {
		$db = new PostgresDatabase($this->makeConfig([
			'host' => 'h',
			'user' => 'u',
			'pass' => 'p',
			'name' => 'n',
		]));
		$db->connect();

		foreach (['scalarQuery', 'singleQuery', 'listQuery', 'multiQuery', 'nonQuery'] as $method) {
			PgStubState::$queryQueue[] = false;
			try {
				$db->$method('SELECT invalid');
				$this->fail($method . ' should reject a failed PostgreSQL query.');
			} catch (\RuntimeException $e) {
				$this->assertStringContainsString('PostgreSQL', $e->getMessage());
			}
		}
	}

	public function testErrorHelpersAndCheckDependencies(): void {
		$this->resetStaticServiceLocator();

		$sl = new ServiceLocator();
		$sl->set('configuration', $this->makeConfig([
			'host' => 'h',
			'user' => 'u',
			'pass' => 'p',
			'name' => 'n',
		]), IContainer::PARAMETER);

		ServiceLocator::useInstance($sl);

		$db = PostgresDatabase::getInstance(null);
		$db->connect();

		PgStubState::$lastError = '';
		$this->assertFalse($db->isError());
		$this->assertSame(0, $db->errorNumber());
		$this->assertSame('', $db->errorMessage());

		PgStubState::$lastError = 'boom';
		$this->assertTrue($db->isError());
		$this->assertSame('boom', $db->errorMessage());

		PgStubState::$lastError = '';
		$res = $db->checkDependencies();
		$this->assertSame('Ok', $res['depending_services']);
		$this->assertSame('Ok', $res['postgres_connected']);

		PgStubState::$lastError = 'Connection failed';
		$res = $db->checkDependencies();
		$this->assertSame('Connection failed', $res['postgres_connected']);

		// Missing configuration: reset PostgresDatabase::$servicelocator and switch ServiceLocator instance
		$this->resetStaticServiceLocator();

		$sl2 = new ServiceLocator();
		ServiceLocator::useInstance($sl2);

		$db2 = PostgresDatabase::getInstance(null);
		$res2 = $db2->checkDependencies();
		$this->assertSame('Fail', $res2['depending_services']);
	}
	private function database(array $values = []): PostgresDatabase {
		PgStubState::reset();
		return new PostgresDatabase(new ArrayConfiguration([
			'database' => $values ?: ['host' => 'localhost', 'user' => 'tester', 'pass' => 'secret', 'name' => 'demo']
		]));
	}

	private function disconnectedDatabase(): PostgresDatabase {
		return new PostgresDatabase(new ArrayConfiguration(['database' => []]));
	}

	public function testConnectingTwiceKeepsTheOriginalConnection(): void {
		$db = $this->database();
		$db->connect();
		$this->assertTrue($db->connected());
		PgStubState::$connectOk = false;
		$db->connect();
		$this->assertTrue($db->connected());
	}

	public function testMissingIndividualCredentialsPreventConnections(): void {
		foreach (['host', 'user', 'pass', 'name'] as $field) {
			$values = ['host' => 'h', 'user' => 'u', 'pass' => 'p', 'name' => 'n'];
			$values[$field] = '';
			$db = $this->database($values);
			$db->connect();
			$this->assertFalse($db->connected(), $field);
		}
	}

	public function testFailedConnectCanBeRetried(): void {
		$db = $this->database();
		PgStubState::$connectOk = false;
		$db->connect();
		$this->assertFalse($db->connected());
		PgStubState::$connectOk = true;
		$db->connect();
		$this->assertTrue($db->connected());
	}

	public function testDisconnectWithoutConnectionIsIdempotent(): void {
		$db = $this->disconnectedDatabase();
		$db->disconnect();
		$db->disconnect();
		$this->assertFalse($db->connected());
	}

	public function testBeginTransactionExecutesBeginAndReleasesResult(): void {
		$db = $this->database();
		$result = (object)['freed' => false];
		PgStubState::$queryQueue[] = $result;
		$db->beginTransaction();
		$this->assertTrue($result->freed);
		$this->assertSame([], PgStubState::$queryQueue);
	}

	public function testCommitExecutesCommitAndReleasesResult(): void {
		$db = $this->database();
		$result = (object)['freed' => false];
		PgStubState::$queryQueue[] = $result;
		$db->commit();
		$this->assertTrue($result->freed);
		$this->assertSame([], PgStubState::$queryQueue);
	}

	public function testRollbackExecutesRollbackAndReleasesResult(): void {
		$db = $this->database();
		$result = (object)['freed' => false];
		PgStubState::$queryQueue[] = $result;
		$db->rollback();
		$this->assertTrue($result->freed);
		$this->assertSame([], PgStubState::$queryQueue);
	}

	public function testFailedBeginIncludesDriverError(): void {
		$db = $this->database();
		PgStubState::$lastError = 'begin denied';
		PgStubState::$queryQueue[] = false;
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Failed to begin transaction: begin denied');
		$db->beginTransaction();
	}

	public function testFailedCommitIncludesDriverError(): void {
		$db = $this->database();
		PgStubState::$lastError = 'commit denied';
		PgStubState::$queryQueue[] = false;
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Failed to commit transaction: commit denied');
		$db->commit();
	}

	public function testFailedRollbackIncludesDriverError(): void {
		$db = $this->database();
		PgStubState::$lastError = 'rollback denied';
		PgStubState::$queryQueue[] = false;
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Failed to rollback transaction: rollback denied');
		$db->rollback();
	}

	public function testBeginWithoutConnectionIsRejected(): void {
		$db = $this->disconnectedDatabase();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('PostgreSQL connection not available.');
		$db->beginTransaction();
	}

	public function testCommitWithoutConnectionIsRejected(): void {
		$db = $this->disconnectedDatabase();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('PostgreSQL connection not available.');
		$db->commit();
	}

	public function testRollbackWithoutConnectionIsRejected(): void {
		$db = $this->disconnectedDatabase();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('PostgreSQL connection not available.');
		$db->rollback();
	}

	public function testNonQueryWithoutConnectionIsRejected(): void {
		$db = $this->disconnectedDatabase();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('PostgreSQL connection not available.');
		$db->nonQuery('UPDATE demo SET x = 1');
	}

	public function testScalarQueryWithoutConnectionIsRejected(): void {
		$db = $this->disconnectedDatabase();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('PostgreSQL connection not available.');
		$db->scalarQuery('SELECT x FROM demo');
	}

	public function testSingleQueryWithoutConnectionIsRejected(): void {
		$db = $this->disconnectedDatabase();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('PostgreSQL connection not available.');
		$db->singleQuery('SELECT * FROM demo');
	}

	public function testListQueryWithoutConnectionIsRejected(): void {
		$db = $this->disconnectedDatabase();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('PostgreSQL connection not available.');
		$db->listQuery('SELECT x FROM demo');
	}

	public function testMultiQueryWithoutConnectionIsRejected(): void {
		$db = $this->disconnectedDatabase();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('PostgreSQL connection not available.');
		$db->multiQuery('SELECT * FROM demo');
	}

	public function testInsertIdWithoutConnectionIsRejected(): void {
		$db = $this->disconnectedDatabase();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('PostgreSQL connection not available.');
		$db->insertId();
	}

	public function testEscapeWithoutConnectionIsRejected(): void {
		$db = $this->disconnectedDatabase();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('PostgreSQL connection not available.');
		$db->escape('value');
	}

	public function testLastvalQueryFailureReturnsZero(): void {
		$db = $this->database();
		PgStubState::$queryQueue[] = false;
		$this->assertSame(0, $db->insertId());
	}

	public function testLastvalWithNoRowReturnsZeroAndFreesResult(): void {
		$db = $this->database();
		$result = (object)['rows' => [], 'freed' => false];
		PgStubState::$queryQueue[] = $result;
		$this->assertSame(0, $db->insertId());
		$this->assertTrue($result->freed);
	}

	public function testLastvalNumericStringBecomesInteger(): void {
		$db = $this->database();
		$result = (object)['rows' => [['780']], 'freed' => false];
		PgStubState::$queryQueue[] = $result;
		$this->assertSame(780, $db->insertId());
		$this->assertTrue($result->freed);
	}

	public function testLastvalNonNumericStringIsPreserved(): void {
		$db = $this->database();
		$result = (object)['rows' => [['record-key']], 'freed' => false];
		PgStubState::$queryQueue[] = $result;
		$this->assertSame('record-key', $db->insertId());
		$this->assertTrue($result->freed);
	}

	public function testScalarQueryWithMissingFirstRowReturnsNullAndFreesResult(): void {
		$db = $this->database();
		$result = (object)['num_rows' => 1, 'rows' => [], 'freed' => false];
		PgStubState::$queryQueue[] = $result;
		$this->assertNull($db->scalarQuery('SELECT x'));
		$this->assertTrue($result->freed);
	}

	public function testSingleQueryWithMissingFirstRowReturnsNullAndFreesResult(): void {
		$db = $this->database();
		$result = (object)['num_rows' => 1, 'rowsAssoc' => [], 'freed' => false];
		PgStubState::$queryQueue[] = $result;
		$this->assertNull($db->singleQuery('SELECT x'));
		$this->assertTrue($result->freed);
	}

	public function testErrorHelpersReportDisconnectedState(): void {
		$db = $this->disconnectedDatabase();
		$this->assertTrue($db->isError());
		$this->assertSame('Not connected', $db->errorMessage());
		$this->assertSame(0, $db->errorNumber());
		$this->assertSame(0, $db->affectedRows());
	}

	public function testNestedConfigurationShapeIsAccepted(): void {
		$db = PostgresDatabase::getInstance(['database' => [
			'host' => 'server', 'user' => 'u', 'pass' => 'p', 'name' => 'n'
		]]);
		PgStubState::reset();
		$db->connect();
		$this->assertTrue($db->connected());
	}

	public function testScalarQueryReturnsFirstColumnAndFreesResult(): void {
		$db = $this->database();
		$result = (object)['num_rows' => 2, 'rows' => [['first', 'second'], ['other']], 'freed' => false];
		PgStubState::$queryQueue[] = $result;
		$this->assertSame('first', $db->scalarQuery('SELECT x, y'));
		$this->assertTrue($result->freed);
	}

	public function testSingleQueryReturnsAssociativeRowAndFreesResult(): void {
		$db = $this->database();
		$result = (object)['num_rows' => 1, 'rowsAssoc' => [['id' => '9']], 'freed' => false];
		PgStubState::$queryQueue[] = $result;
		$this->assertSame(['id' => '9'], $db->singleQuery('SELECT *'));
		$this->assertTrue($result->freed);
	}
}

require_once __DIR__ . '/PgStubState.php';
