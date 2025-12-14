<?php declare(strict_types=1);

namespace Base3\ServiceSelector {
	if (!function_exists(__NAMESPACE__ . '\\header')) {
		function header(string $header, bool $replace = true, int $response_code = 0): void
		{
		}
	}
}

namespace Base3\Test\Route\QueryLegacy {

	use PHPUnit\Framework\TestCase;
	use Base3\Route\QueryLegacy\QueryLegacyRoute;
	use Base3\Api\IContainer;
	use Base3\Api\IRequest;
	use Base3\Configuration\Api\IConfiguration;
	use Base3\Accesscontrol\Api\IAccesscontrol;
	use Base3\Api\IClassMap;

	final class QueryLegacyRouteTest extends TestCase
	{
		private function makeConfig(array $baseConfig = []): IConfiguration
		{
			return new class($baseConfig) implements IConfiguration {
				public function __construct(private array $baseConfig) {}
				public function get($configuration = "") {
					if ($configuration === 'base') return $this->baseConfig;
					return [];
				}
				public function set($data, $configuration = "") {}
				public function save() {}
			};
		}

		private function makeAccesscontrol(string $userId = ''): IAccesscontrol
		{
			return new class($userId) implements IAccesscontrol {
				public function __construct(private string $userId) {}
				public function getUserId() { return $this->userId; }
				public function authenticate(): void {}
			};
		}

		private function makeRequest(array $get): IRequest
		{
			return new class($get) implements IRequest {
				public function __construct(private array $get) {}

				public function get(string $key, $default = null) { return $this->get[$key] ?? $default; }
				public function post(string $key, $default = null) { return $default; }
				public function request(string $key, $default = null) { return $this->get($key, $default); }
				public function allRequest(): array { return $this->get; }
				public function cookie(string $key, $default = null) { return $default; }
				public function session(string $key, $default = null) { return $default; }
				public function server(string $key, $default = null) { return $default; }
				public function files(string $key, $default = null) { return $default; }
				public function allGet(): array { return $this->get; }
				public function allPost(): array { return []; }
				public function allCookie(): array { return []; }
				public function allSession(): array { return []; }
				public function allServer(): array { return []; }
				public function allFiles(): array { return []; }
				public function getJsonBody(): array { return []; }
				public function isCli(): bool { return false; }
				public function getContext(): string { return self::CONTEXT_TEST; }
			};
		}

		private function makeClassMap(object $output): IClassMap
		{
			return new class($output) implements IClassMap {
				public function __construct(private object $output) {}

				public function instantiate(string $class) { return null; }
				public function &getInstances(array $criteria = []) { $x = []; return $x; }
				public function getPlugins() { return []; }

				public function getInstanceByInterfaceName(string $iface, string $name) {
					return $name === 'foo' ? $this->output : null;
				}
				public function getInstanceByAppInterfaceName(string $app, string $iface, string $name) {
					return null;
				}
				public function getInstancesByInterface(string $iface) {
					return [];
				}
			};
		}

		private function makeContainer(IConfiguration $config, IClassMap $classmap, IAccesscontrol $access, IRequest $request): IContainer
		{
			return new class($config, $classmap, $access, $request) implements IContainer {
				private array $services;

				public function __construct($config, $classmap, $access, $request)
				{
					$this->services = [
						'configuration' => $config,
						'classmap' => $classmap,
						'accesscontrol' => $access,
						IRequest::class => $request,
						'middlewares' => [],
					];
				}

				public function getServiceList(): array { return array_keys($this->services); }
				public function set(string $name, $classDefinition, $flags = 0): IContainer { $this->services[$name] = $classDefinition; return $this; }
				public function remove(string $name) { unset($this->services[$name]); }
				public function has(string $name): bool { return array_key_exists($name, $this->services); }
				public function get(string $name) { return $this->services[$name] ?? null; }
			};
		}

		protected function setUp(): void
		{
			$_GET = [];
			$_REQUEST = [];
			putenv('DEBUG=1');
		}

		public function testMatchReturnsNullIfNoLegacyParamsPresent(): void
		{
			$container = $this->makeContainer(
				$this->makeConfig(['url' => '/', 'intern' => '']),
				$this->makeClassMap(new class {}),
				$this->makeAccesscontrol(''),
				$this->makeRequest([])
			);

			$route = new QueryLegacyRoute($container);

			$this->assertNull($route->match('/ignored'));
		}

		public function testMatchReturnsLegacyArrayIfAnyParamPresent(): void
		{
			$_GET['name'] = 'index';

			$container = $this->makeContainer(
				$this->makeConfig(['url' => '/', 'intern' => '']),
				$this->makeClassMap(new class {}),
				$this->makeAccesscontrol(''),
				$this->makeRequest([])
			);

			$route = new QueryLegacyRoute($container);

			$this->assertSame(['legacy' => true], $route->match('/ignored'));
		}

		public function testDispatchDelegatesToAbstractServiceSelectorProcess(): void
		{
			$_GET['name'] = 'foo';
			$_GET['out']  = 'html';
			$_REQUEST = $_GET;

			$output = new class {
				public function getOutput($out = 'html') { return 'OUT:' . $out; }
				public function getHelp() { return 'HELP'; }
			};

			$config = $this->makeConfig(['url' => '/', 'intern' => '']); // avoid redirect+exit
			$access = $this->makeAccesscontrol('');
			$request = $this->makeRequest($_GET);
			$classmap = $this->makeClassMap($output);

			$container = $this->makeContainer($config, $classmap, $access, $request);

			$route = new QueryLegacyRoute($container);

			$result = $route->dispatch(['legacy' => true]);

			$this->assertSame('OUT:html', $result);
		}
	}
}
