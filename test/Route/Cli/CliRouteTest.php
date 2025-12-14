<?php declare(strict_types=1);

namespace Base3\Route\Cli {
	if (!function_exists(__NAMESPACE__ . '\\header')) {
		function header(string $header, bool $replace = true, int $response_code = 0): void
		{
		}
	}
}

namespace Base3\Test\Route\Cli {

	use PHPUnit\Framework\TestCase;
	use Base3\Route\Cli\CliRoute;
	use Base3\Api\IRequest;
	use Base3\Api\IClassMap;

	final class CliRouteTest extends TestCase
	{
		private function makeRequest(array $values): IRequest
		{
			return new class($values) implements IRequest {
				public function __construct(private array $values) {}

				public function get(string $key, $default = null) { return $this->values[$key] ?? $default; }
				public function post(string $key, $default = null) { return $default; }
				public function request(string $key, $default = null) { return $this->get($key, $default); }
				public function allRequest(): array { return $this->values; }
				public function cookie(string $key, $default = null) { return $default; }
				public function session(string $key, $default = null) { return $default; }
				public function server(string $key, $default = null) { return $default; }
				public function files(string $key, $default = null) { return $default; }
				public function allGet(): array { return $this->values; }
				public function allPost(): array { return []; }
				public function allCookie(): array { return []; }
				public function allSession(): array { return []; }
				public function allServer(): array { return []; }
				public function allFiles(): array { return []; }
				public function getJsonBody(): array { return []; }
				public function isCli(): bool { return true; }
				public function getContext(): string { return self::CONTEXT_TEST; }
			};
		}

		private function makeClassMap(?object $instance): IClassMap
		{
			return new class($instance) implements IClassMap {
				public function __construct(private ?object $instance) {}

				public function instantiate(string $class) { return null; }
				public function &getInstances(array $criteria = []) { $x = []; return $x; }
				public function getPlugins() { return []; }

				public function getInstanceByInterfaceName(string $iface, string $name) {
					return $this->instance;
				}
			};
		}

		protected function setUp(): void
		{
			$_GET = [];
			$_REQUEST = [];
		}

		public function testMatchReturnsNullIfNoNameProvided(): void
		{
			$request = $this->makeRequest(['name' => '']);
			$route = new CliRoute($request, $this->makeClassMap(null));

			$this->assertNull($route->match('/anything'));
		}

		public function testMatchReturnsArrayWithDefaults(): void
		{
			$request = $this->makeRequest(['name' => 'phpinfo']); // out/data default handling is in route
			$route = new CliRoute($request, $this->makeClassMap(null));

			$match = $route->match('/ignored');

			$this->assertSame([
				'name' => 'phpinfo',
				'out'  => 'html',
				'data' => '',
			], $match);
		}

		public function testDispatchReturns404IfNoOutputFound(): void
		{
			$request = $this->makeRequest([]);
			$route = new CliRoute($request, $this->makeClassMap(null));

			$out = $route->dispatch(['name' => 'missing', 'out' => 'html', 'data' => '']);

			$this->assertSame("404 Not Found\n", $out);
		}

		public function testDispatchSetsGlobalsAndCallsOutput(): void
		{
			$request = $this->makeRequest([]);

			$output = new class {
				public function getOutput($out = 'html') { return 'OUT:' . $out; }
				public function getHelp() { return 'HELP'; }
			};

			$route = new CliRoute($request, $this->makeClassMap($output));

			$result = $route->dispatch(['name' => 'dummy', 'out' => 'json', 'data' => 'xx']);

			$this->assertSame('dummy', $_GET['name']);
			$this->assertSame('xx', $_GET['data']);
			$this->assertSame('json', $_GET['out']);

			$this->assertSame('OUT:json', $result);
		}
	}
}
