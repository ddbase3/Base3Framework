<?php declare(strict_types=1);

namespace Base3Test\Core;

use Base3\Api\IRequest;
use Base3\Core\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase {

	protected function tearDown(): void {
		unset($_GET, $_POST, $_COOKIE, $_SESSION, $_SERVER, $_FILES);
		putenv('CRON_JOB');
		putenv('IS_CRON');
		putenv('TEST_ENV');
	}

	private function setProtected(object $obj, string $prop, $value): void {
		$ref = new \ReflectionClass($obj);
		$p = $ref->getProperty($prop);
		$p->setAccessible(true);
		$p->setValue($obj, $value);
	}

	private function setPrivate(object $obj, string $prop, $value): void {
		$ref = new \ReflectionClass($obj);
		$p = $ref->getProperty($prop);
		$p->setAccessible(true);
		$p->setValue($obj, $value);
	}

	public function testInitFromGlobalsCopiesSuperglobals(): void {
		$_GET = ['a' => '1'];
		$_POST = ['b' => '2'];
		$_COOKIE = ['c' => '3'];
		$_SESSION = ['d' => '4'];
		$_SERVER = ['REQUEST_METHOD' => 'GET'];
		$_FILES = ['f' => ['name' => 'x']];

		$req = new Request();
		$req->initFromGlobals();

		$this->assertSame('1', $req->get('a'));
		$this->assertSame('2', $req->post('b'));
		$this->assertSame('3', $req->cookie('c'));
		$this->assertSame('4', $req->session('d'));
		$this->assertSame('GET', $req->server('REQUEST_METHOD'));
		$this->assertSame(['name' => 'x'], $req->files('f'));
	}

	public function testFromGlobalsCreatesInitializedInstance(): void {
		$_GET = ['x' => 'y'];
		$_POST = [];
		$_COOKIE = [];
		$_SESSION = [];
		$_SERVER = [];
		$_FILES = [];

		$req = Request::fromGlobals();

		$this->assertInstanceOf(Request::class, $req);
		$this->assertSame('y', $req->get('x'));
	}

	public function testResolveSupportsArrayNotationAcrossSources(): void {
		$req = new Request();

		$this->setProtected($req, 'get', ['options' => ['type' => 'a', 'nested' => ['x' => 5]]]);
		$this->setProtected($req, 'post', ['p' => ['q' => 'z']]);
		$this->setProtected($req, 'cookie', ['c' => ['d' => 'e']]);
		$this->setProtected($req, 'session', ['s' => ['t' => 'u']]);
		$this->setProtected($req, 'server', ['sv' => ['k' => 'v']]);
		$this->setProtected($req, 'files', ['f' => ['name' => 'n']]);

		$this->assertSame('a', $req->get('options[type]'));
		$this->assertSame(5, $req->get('options[nested][x]'));
		$this->assertSame('z', $req->post('p[q]'));
		$this->assertSame('e', $req->cookie('c[d]'));
		$this->assertSame('u', $req->session('s[t]'));
		$this->assertSame('v', $req->server('sv[k]'));
		$this->assertSame('n', $req->files('f[name]'));

		$this->assertSame('def', $req->get('options[missing]', 'def'));
		$this->assertSame('def', $req->post('p[missing]', 'def'));
	}

	public function testRequestStrictPostPrecedenceFlatKeyIncludingNull(): void {
		$req = new Request();

		$this->setProtected($req, 'get', ['k' => 'get']);
		$this->setProtected($req, 'post', ['k' => null]);

		$this->assertNull($req->request('k', 'default'));
	}

	public function testRequestStrictPostPrecedenceNestedKeyIncludingNull(): void {
		$req = new Request();

		$this->setProtected($req, 'get', ['options' => ['type' => 'getType']]);
		$this->setProtected($req, 'post', ['options' => ['type' => null]]);

		$this->assertNull($req->request('options[type]', 'default'));
	}

	public function testRequestFallsBackToGetWhenPostPathMissing(): void {
		$req = new Request();

		$this->setProtected($req, 'get', ['options' => ['type' => 'getType']]);
		$this->setProtected($req, 'post', ['options' => []]);

		$this->assertSame('getType', $req->request('options[type]', 'default'));
	}

	public function testAllRequestDeepMergesPostOverGet(): void {
		$req = new Request();

		$this->setProtected($req, 'get', [
			'a' => 1,
			'n' => ['x' => 1, 'y' => 2],
		]);

		$this->setProtected($req, 'post', [
			'b' => 2,
			'n' => ['y' => 9, 'z' => 3],
		]);

		$all = $req->allRequest();

		$this->assertSame(1, $all['a']);
		$this->assertSame(2, $all['b']);
		$this->assertSame(['x' => 1, 'y' => 9, 'z' => 3], $all['n']);
	}

	public function testAllAccessorsReturnFullArrays(): void {
		$req = new Request();

		$this->setProtected($req, 'get', ['g' => 1]);
		$this->setProtected($req, 'post', ['p' => 2]);
		$this->setProtected($req, 'cookie', ['c' => 3]);
		$this->setProtected($req, 'session', ['s' => 4]);
		$this->setProtected($req, 'server', ['sv' => 5]);
		$this->setProtected($req, 'files', ['f' => 6]);

		$this->assertSame(['g' => 1], $req->allGet());
		$this->assertSame(['p' => 2], $req->allPost());
		$this->assertSame(['c' => 3], $req->allCookie());
		$this->assertSame(['s' => 4], $req->allSession());
		$this->assertSame(['sv' => 5], $req->allServer());
		$this->assertSame(['f' => 6], $req->allFiles());
	}

	public function testToArraySupportsTraversable(): void {
		$req = new Request();

		$src = new class implements \ArrayAccess, \IteratorAggregate {

			private array $data = ['a' => 1, 'b' => 2];

			public function offsetExists($offset): bool {
				return array_key_exists($offset, $this->data);
			}

			public function offsetGet($offset): mixed {
				return $this->data[$offset] ?? null;
			}

			public function offsetSet($offset, $value): void {
				$this->data[$offset] = $value;
			}

			public function offsetUnset($offset): void {
				unset($this->data[$offset]);
			}

			public function getIterator(): \Traversable {
				return new \ArrayIterator($this->data);
			}
		};

		$this->setProtected($req, 'get', $src);

		$this->assertSame(1, $req->get('a'));
		$this->assertSame(['a' => 1, 'b' => 2], $req->allGet());
	}

	public function testParseCliArgsAddsToGetWhenInitFromGlobalsRunsInCli(): void {
		$req = new class extends Request {
			protected function parseCliArgs(): void {
				parent::parseCliArgs();
			}
			public function isCli(): bool {
				return true;
			}
		};

		$_GET = [];
		$_POST = [];
		$_COOKIE = [];
		$_SESSION = [];
		$_FILES = [];
		$_SERVER = [
			'argv' => ['script.php', '--foo=bar', '--flag'],
		];

		$req->initFromGlobals();

		$this->assertSame('bar', $req->get('foo'));
		$this->assertTrue($req->get('flag'));
	}

	public function testGetJsonBodyCachingViaReflection(): void {
		$req = new Request();

		$this->setPrivate($req, 'jsonBody', ['x' => 1]);
		$this->assertSame(['x' => 1], $req->getJsonBody());
		$this->assertSame(['x' => 1], $req->getJsonBody());
	}

	public function testGetContextCliBuiltinServerAndCronAndTestAndDefaultCli(): void {
		$req = new Request();

		$_SERVER = ['REQUEST_METHOD' => 'GET'];
		putenv('CRON_JOB');
		putenv('IS_CRON');
		putenv('TEST_ENV');
		$this->assertSame(IRequest::CONTEXT_BUILTIN_SERVER, $req->getContext());

		$_SERVER = [];
		putenv('CRON_JOB=1');
		putenv('IS_CRON');
		putenv('TEST_ENV');
		$this->assertSame(IRequest::CONTEXT_CRON, $req->getContext());

		$_SERVER = [];
		putenv('CRON_JOB');
		putenv('IS_CRON=1');
		putenv('TEST_ENV');
		$this->assertSame(IRequest::CONTEXT_CRON, $req->getContext());

		$_SERVER = [];
		putenv('CRON_JOB');
		putenv('IS_CRON');
		putenv('TEST_ENV=1');
		$this->assertSame(IRequest::CONTEXT_TEST, $req->getContext());

		$_SERVER = [];
		putenv('CRON_JOB');
		putenv('IS_CRON');
		putenv('TEST_ENV');
		$this->assertSame(IRequest::CONTEXT_CLI, $req->getContext());
	}

	public function testGetContextWebBranchesViaIsCliOverride(): void {
		$req = new class extends Request {
			public function isCli(): bool {
				return false;
			}
		};

		$this->setProtected($req, 'server', ['REQUEST_METHOD' => 'POST']);
		$this->setProtected($req, 'files', ['f' => ['name' => 'x']]);
		$this->assertSame(IRequest::CONTEXT_WEB_UPLOAD, $req->getContext());

		$this->setProtected($req, 'server', ['REQUEST_METHOD' => 'POST']);
		$this->setProtected($req, 'files', []);
		$this->assertSame(IRequest::CONTEXT_WEB_POST, $req->getContext());

		$this->setProtected($req, 'server', [
			'REQUEST_METHOD' => 'GET',
			'HTTP_X_REQUESTED_WITH' => 'xmlhttprequest',
		]);
		$this->setProtected($req, 'files', []);
		$this->assertSame(IRequest::CONTEXT_WEB_AJAX, $req->getContext());

		$this->setProtected($req, 'server', [
			'REQUEST_METHOD' => 'GET',
			'HTTP_ACCEPT' => 'application/json, text/plain',
		]);
		$this->setProtected($req, 'files', []);
		$this->assertSame(IRequest::CONTEXT_WEB_API, $req->getContext());

		$this->setProtected($req, 'server', ['REQUEST_METHOD' => 'GET']);
		$this->setProtected($req, 'files', []);
		$this->assertSame(IRequest::CONTEXT_WEB_GET, $req->getContext());
	}
}
