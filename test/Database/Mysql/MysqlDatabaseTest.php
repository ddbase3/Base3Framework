<?php declare(strict_types=1);

namespace Base3Test\Database\Mysql;

use Base3\Database\Mysql\MysqlDatabase;
use Base3\Configuration\Api\IConfiguration;
use PHPUnit\Framework\TestCase;

/**
 * Fake connection object for MysqlDatabase.
 * We must NOT instantiate real \mysqli in tests (ext-mysqli + strict reporting can throw/warn).
 */
final class FakeMysqliConnection {

	public int $connect_errno = 0;
	public string $connect_error = '';
	public int $affected_rows = 0;
	public $insert_id = 0;
	public string $error = '';
	public int $errno = 0;

	public bool $closed = false;
	public ?string $charset = null;

	/** @var array<int, mixed> */
	public array $queryQueue = [];

	public function set_charset(string $charset) {
		$this->charset = $charset;
		return true;
	}

	public function close() {
		$this->closed = true;
		return true;
	}

	public function query(string $query) {
		if (count($this->queryQueue) === 0) return false;
		return array_shift($this->queryQueue);
	}

	public function real_escape_string(string $str): string {
		return addslashes($str);
	}
}

final class FakeMysqliResult {

	public int $num_rows = 0;

	/** @var array<int, array<int, mixed>> */
	private array $rowsNum = [];

	/** @var array<int, array<string, mixed>> */
	private array $rowsAssoc = [];

	private int $posNum = 0;
	private int $posAssoc = 0;

	public bool $freed = false;

	/**
	 * @param array<int, array<int, mixed>> $rowsNum
	 * @param array<int, array<string, mixed>> $rowsAssoc
	 */
	public function __construct(array $rowsNum, array $rowsAssoc) {
		$this->rowsNum = $rowsNum;
		$this->rowsAssoc = $rowsAssoc;
		$this->num_rows = max(count($rowsNum), count($rowsAssoc));
	}

	public function fetch_array(int $mode) {
		if ($this->posNum >= count($this->rowsNum)) return null;
		return $this->rowsNum[$this->posNum++];
	}

	public function fetch_assoc() {
		if ($this->posAssoc >= count($this->rowsAssoc)) return null;
		return $this->rowsAssoc[$this->posAssoc++];
	}

	public function free() {
		$this->freed = true;
	}
}

final class MysqlDatabaseTest extends TestCase {

	private function makeConfig(array $databaseCnf): IConfiguration {
		return new class($databaseCnf) implements IConfiguration {
			private array $cnf;
			public function __construct(array $cnf) { $this->cnf = $cnf; }
			public function get($configuration = "") { return $this->cnf; }
			public function set($data, $configuration = "") {}
			public function save() {}
		};
	}

	private function setPrivate(object $obj, string $prop, $value): void {
		$ref = new \ReflectionClass($obj);
		$p = $ref->getProperty($prop);
		$p->setAccessible(true);
		$p->setValue($obj, $value);
	}

	private function getPrivate(object $obj, string $prop) {
		$ref = new \ReflectionClass($obj);
		$p = $ref->getProperty($prop);
		$p->setAccessible(true);
		return $p->getValue($obj);
	}

	public function testGetInstanceWithInlineConfigCreatesInstance(): void {
		$db = MysqlDatabase::getInstance([
			'host' => 'h',
			'user' => 'u',
			'pass' => 'p',
			'name' => 'n',
		]);

		$this->assertInstanceOf(MysqlDatabase::class, $db);
		$this->assertFalse($db->connected());
	}

	public function testConnectReturnsEarlyWhenAlreadyConnected(): void {
		$db = new MysqlDatabase($this->makeConfig([
			'host' => 'h',
			'user' => 'u',
			'pass' => 'p',
			'name' => 'n',
		]));
		$this->setPrivate($db, 'connected', true);

		$db->connect();

		$this->assertTrue($db->connected());
	}

	public function testConnectReturnsEarlyWhenMissingConfig(): void {
		$db = new MysqlDatabase($this->makeConfig([
			'host' => null,
			'user' => null,
			'pass' => null,
			'name' => null,
		]));
		$db->connect();

		$this->assertFalse($db->connected());
		$this->assertNull($this->getPrivate($db, 'connection'));
	}

	public function testConnectHandlesConnectErrorWithoutDb(): void {
		// No DB assumptions. Ensure connect() does NOT perform any real network call:
		// provide incomplete config so connect() returns early.
		$db = new MysqlDatabase($this->makeConfig([
			'host' => null,
			'user' => null,
			'pass' => null,
			'name' => null,
		]));

		$db->connect();

		$this->assertFalse($db->connected());
	}

	public function testQueryHelpersAndErrorHelpersUsingInjectedConnection(): void {
		if (!defined('MYSQLI_NUM')) define('MYSQLI_NUM', 1);

		$db = new MysqlDatabase($this->makeConfig([
			'host' => 'h',
			'user' => 'u',
			'pass' => 'p',
			'name' => 'n',
		]));

		$conn = new FakeMysqliConnection();
		$conn->affected_rows = 7;
		$conn->insert_id = 123;
		$conn->error = 'err';
		$conn->errno = 99;

		// scalarQuery: false -> null
		$conn->queryQueue[] = false;
		// scalarQuery: empty -> null
		$conn->queryQueue[] = new FakeMysqliResult([], []);
		// scalarQuery: returns 42
		$conn->queryQueue[] = new FakeMysqliResult([[42]], []);

		// singleQuery: false -> null
		$conn->queryQueue[] = false;
		// singleQuery: empty -> null
		$conn->queryQueue[] = new FakeMysqliResult([], []);
		// singleQuery: returns assoc row
		$conn->queryQueue[] = new FakeMysqliResult([], [['a' => 1]]);

		// listQuery: false -> []
		$conn->queryQueue[] = false;
		// listQuery: empty -> []
		$conn->queryQueue[] = new FakeMysqliResult([], []);
		// listQuery: returns [1,2,3]
		$conn->queryQueue[] = new FakeMysqliResult([[1], [2], [3]], []);

		// multiQuery: false -> []
		$conn->queryQueue[] = false;
		// multiQuery: empty -> []
		$conn->queryQueue[] = new FakeMysqliResult([], []);
		// multiQuery: returns rows
		$conn->queryQueue[] = new FakeMysqliResult([], [['x' => 'y'], ['x' => 'z']]);

		$this->setPrivate($db, 'connection', $conn);
		$this->setPrivate($db, 'connected', true);

		$this->assertNull($db->scalarQuery('SELECT 1 WHERE 0'));
		$this->assertNull($db->scalarQuery('SELECT 1'));
		$this->assertSame(42, $db->scalarQuery('SELECT 42'));

		$this->assertNull($db->singleQuery('SELECT 1 WHERE 0'));
		$this->assertNull($db->singleQuery('SELECT 1'));
		$this->assertSame(['a' => 1], $db->singleQuery('SELECT a'));

		$list = $db->listQuery('SELECT a FROM t');
		$this->assertSame([], $list);
		$list = $db->listQuery('SELECT a FROM t');
		$this->assertSame([], $list);
		$list = $db->listQuery('SELECT a FROM t');
		$this->assertSame([1, 2, 3], $list);

		$rows = $db->multiQuery('SELECT * FROM t');
		$this->assertSame([], $rows);
		$rows = $db->multiQuery('SELECT * FROM t');
		$this->assertSame([], $rows);
		$rows = $db->multiQuery('SELECT * FROM t');
		$this->assertSame([['x' => 'y'], ['x' => 'z']], $rows);

		$this->assertSame(7, $db->affectedRows());
		$this->assertSame(123, $db->insertId());
		$this->assertSame(addslashes("a'b"), $db->escape("a'b"));

		$this->assertTrue($db->isError());
		$this->assertSame(99, $db->errorNumber());
		$this->assertSame('err', $db->errorMessage());

		// nonQuery does not return anything; call at the end so it cannot shift the queue
		$db->nonQuery('UPDATE t SET a=1');

		$db->disconnect();
		$this->assertFalse($db->connected());
		$this->assertTrue($conn->closed);
	}

	public function testCheckDependenciesMessages(): void {
		$db = new MysqlDatabase($this->makeConfig([
			'host' => 'h',
			'user' => 'u',
			'pass' => 'p',
			'name' => 'n',
		]));

		// 1) Not connected: prevent connect() from doing anything by removing config
		$this->setPrivate($db, 'host', null);
		$this->setPrivate($db, 'user', null);
		$this->setPrivate($db, 'pass', null);
		$this->setPrivate($db, 'name', null);
		$this->setPrivate($db, 'connection', null);
		$this->setPrivate($db, 'connected', false);

		$res = $db->checkDependencies();
		$this->assertSame('Not connected', $res['mysql_connected']);

		// 2) Has connection with connect errno -> message is connect_error
		$conn = new FakeMysqliConnection();
		$conn->connect_errno = 1;
		$conn->connect_error = 'Nope';

		$this->setPrivate($db, 'connection', $conn);
		$this->setPrivate($db, 'connected', true); // avoid real connect()
		$res = $db->checkDependencies();
		$this->assertSame('Nope', $res['mysql_connected']);

		// 3) Ok (connect_errno == 0)
		$conn->connect_errno = 0;
		$conn->connect_error = '';
		$res = $db->checkDependencies();
		$this->assertSame('Ok', $res['mysql_connected']);
	}
}
