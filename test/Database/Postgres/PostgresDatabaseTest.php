<?php declare(strict_types=1);

namespace Base3\Database\Postgres;

// --- pg_* function stubs ------------------------------------------------------
// The class under test uses unqualified pg_* calls.
// Avoid passing object properties by reference (array_shift on property is problematic).

final class PgStubState {

	public static bool $connectOk = true;
	public static string $lastError = '';
	public static int $affectedRows = 0;

	/** @var array<int, mixed> */
	public static array $queryQueue = [];

	public static function reset(): void {
		self::$connectOk = true;
		self::$lastError = '';
		self::$affectedRows = 0;
		self::$queryQueue = [];
	}
}

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
use Base3\Core\ServiceLocator;
use Base3\Database\Postgres\PgStubState;
use Base3\Database\Postgres\PostgresDatabase;
use Base3\Test\Configuration\ConfigurationStub;
use PHPUnit\Framework\TestCase;

final class PostgresDatabaseTest extends TestCase {

	private function makeConfig(array $databaseCnf): IConfiguration {
		return new ConfigurationStub();
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
		PgStubState::$queryQueue[] = false;
		PgStubState::$queryQueue[] = (object)['num_rows' => 0, 'rows' => [], 'freed' => false];
		PgStubState::$queryQueue[] = (object)['num_rows' => 1, 'rows' => [[42]], 'freed' => false];

		// singleQuery
		PgStubState::$queryQueue[] = false;
		PgStubState::$queryQueue[] = (object)['num_rows' => 0, 'rowsAssoc' => [], 'freed' => false];
		PgStubState::$queryQueue[] = (object)['num_rows' => 1, 'rowsAssoc' => [['a' => 1]], 'freed' => false];

		// listQuery
		PgStubState::$queryQueue[] = false;
		PgStubState::$queryQueue[] = (object)['num_rows' => 0, 'rows' => [], 'freed' => false];
		PgStubState::$queryQueue[] = (object)['num_rows' => 3, 'rows' => [[1], [2], [3]], 'freed' => false];

		// multiQuery
		PgStubState::$queryQueue[] = false;
		PgStubState::$queryQueue[] = (object)['num_rows' => 0, 'rowsAssoc' => [], 'freed' => false];
		PgStubState::$queryQueue[] = (object)['num_rows' => 2, 'rowsAssoc' => [['x' => 'y'], ['x' => 'z']], 'freed' => false];

		// insertId (LASTVAL)
		PgStubState::$queryQueue[] = (object)['num_rows' => 1, 'rows' => [[123]], 'freed' => false];

		$this->assertNull($db->scalarQuery('SELECT 1'));
		$this->assertNull($db->scalarQuery('SELECT 1'));
		$this->assertSame(42, $db->scalarQuery('SELECT 42'));

		$this->assertNull($db->singleQuery('SELECT 1'));
		$this->assertNull($db->singleQuery('SELECT 1'));
		$this->assertSame(['a' => 1], $db->singleQuery('SELECT a'));

		$list = $db->listQuery('SELECT a');
		$this->assertSame([], $list);
		$list = $db->listQuery('SELECT a');
		$this->assertSame([], $list);
		$list = $db->listQuery('SELECT a');
		$this->assertSame([1, 2, 3], $list);

		$rows = $db->multiQuery('SELECT *');
		$this->assertSame([], $rows);
		$rows = $db->multiQuery('SELECT *');
		$this->assertSame([], $rows);
		$rows = $db->multiQuery('SELECT *');
		$this->assertSame([['x' => 'y'], ['x' => 'z']], $rows);

		$this->assertSame(123, $db->insertId());

		PgStubState::$affectedRows = 7;
		$this->assertSame(7, $db->affectedRows());

		$this->assertSame(addslashes("a'b"), $db->escape("a'b"));

		// nonQuery: no return value; call at the end so it cannot shift the queue
		$db->nonQuery('UPDATE t SET a=1');
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

		PgStubState::$lastError = '';
		$this->assertFalse($db->isError());
		$this->assertSame(0, $db->errorNumber());
		$this->assertSame('', $db->errorMessage());

		PgStubState::$lastError = 'boom';
		$this->assertTrue($db->isError());
		$this->assertSame('boom', $db->errorMessage());

		PgStubState::$connectOk = true;
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
}
