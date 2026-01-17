<?php declare(strict_types=1);

namespace Base3Test\Database\Mysql;

use Base3\Configuration\Api\IConfiguration;
use Base3\Database\Mysql\MysqlDatabase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
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

	/**
	 * mysqli::query has strict return type mysqli_result|bool and may be called with (string, int).
	 *
	 * @param array<int, mixed> $queryQueue must only contain bool or \mysqli_result
	 */
	private function makeMysqliMock(array &$queryQueue, bool &$closed, ?string &$charset): \mysqli {
		$closed = false;
		$charset = null;

		$mysqli = $this->getMockBuilder(\mysqli::class)
			->disableOriginalConstructor()
			->onlyMethods(['query', 'set_charset', 'close', 'real_escape_string'])
			->getMock();

		$mysqli->method('query')->willReturnCallback(function(...$args) use (&$queryQueue) {
			if (count($queryQueue) === 0) return false;

			$v = array_shift($queryQueue);

			if ($v === false || $v === true) return $v;
			if ($v instanceof \mysqli_result) return $v;

			throw new \RuntimeException('Queued query result must be bool or mysqli_result');
		});

		$mysqli->method('set_charset')->willReturnCallback(function(string $cs) use (&$charset) {
			$charset = $cs;
			return true;
		});

		$mysqli->method('close')->willReturnCallback(function() use (&$closed) {
			$closed = true;
			return true;
		});

		$mysqli->method('real_escape_string')->willReturnCallback(function(string $str) {
			return addslashes($str);
		});

		return $mysqli;
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
		$db = new MysqlDatabase($this->makeConfig([
			'host' => null,
			'user' => null,
			'pass' => null,
			'name' => null,
		]));

		$db->connect();

		$this->assertFalse($db->connected());
	}

	public function testEscapeUsingInjectedConnection(): void {
		$db = new MysqlDatabase($this->makeConfig([
			'host' => 'h',
			'user' => 'u',
			'pass' => 'p',
			'name' => 'n',
		]));

		$queryQueue = [];
		$closed = false;
		$charset = null;
		$conn = $this->makeMysqliMock($queryQueue, $closed, $charset);

		$this->setPrivate($db, 'connection', $conn);
		$this->setPrivate($db, 'connected', true);

		$this->assertSame(addslashes("a'b"), $db->escape("a'b"));

		$db->disconnect();
		$this->assertFalse($db->connected());
		$this->assertTrue($closed);
	}

	public function testDisconnectClosesConnectionWhenPresent(): void {
		$db = new MysqlDatabase($this->makeConfig([
			'host' => 'h',
			'user' => 'u',
			'pass' => 'p',
			'name' => 'n',
		]));

		$queryQueue = [];
		$closed = false;
		$charset = null;
		$conn = $this->makeMysqliMock($queryQueue, $closed, $charset);

		$this->setPrivate($db, 'connection', $conn);
		$this->setPrivate($db, 'connected', true);

		$db->disconnect();

		$this->assertFalse($db->connected());
		$this->assertTrue($closed);
	}

	public function testCheckDependenciesMessages(): void {
		$db = new MysqlDatabase($this->makeConfig([
			'host' => 'h',
			'user' => 'u',
			'pass' => 'p',
			'name' => 'n',
		]));

		// 1) Not connected
		$this->setPrivate($db, 'host', null);
		$this->setPrivate($db, 'user', null);
		$this->setPrivate($db, 'pass', null);
		$this->setPrivate($db, 'name', null);
		$this->setPrivate($db, 'connection', null);
		$this->setPrivate($db, 'connected', false);

		$res = $db->checkDependencies();
		$this->assertSame('Not connected', $res['mysql_connected']);

		// 2) With injected connection: connect_errno/connect_error are readonly; assert behavior based on real values.
		$queryQueue = [];
		$closed = false;
		$charset = null;
		$conn = $this->makeMysqliMock($queryQueue, $closed, $charset);

		$this->setPrivate($db, 'connection', $conn);
		$this->setPrivate($db, 'connected', true); // avoid real connect()

		$res = $db->checkDependencies();
		$expected = ($conn->connect_errno ? $conn->connect_error : 'Ok');
		$this->assertSame($expected, $res['mysql_connected']);
	}
}
