<?php declare(strict_types=1);

namespace Base3\Route\GenericOutput {
	if (!function_exists(__NAMESPACE__ . '\\header')) {
		function header(string $header, bool $replace = true, int $response_code = 0): void
		{
		}
	}
}

namespace Base3\Test\Route\GenericOutput {

	use PHPUnit\Framework\TestCase;
	use Base3\Route\GenericOutput\GenericOutputRoute;
	use Base3\Configuration\Api\IConfiguration;
	use Base3\Accesscontrol\Api\IAccesscontrol;
	use Base3\Api\IClassMap;

	final class GenericOutputRouteTest extends TestCase
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

		private function makeClassMap(object $output): IClassMap
		{
			return new class($output) implements IClassMap {
				public function __construct(private object $output) {}

				public function instantiate(string $class) { return null; }
				public function &getInstances(array $criteria = []) { $x = []; return $x; }
				public function getPlugins() { return []; }

				public function getInstanceByInterfaceName(string $iface, string $name) {
					return ($name === 'index' || $name === 'foo') ? $this->output : null;
				}
			};
		}

		private function makeRouteWithOutput(object $output, array $baseConfig = [], ?object $language = null): GenericOutputRoute
		{
			return new GenericOutputRoute(
				$this->makeClassMap($output),
				$this->makeConfig($baseConfig),
				$this->makeAccesscontrol(''), // avoid redirect+exit path
				$language
			);
		}

		protected function setUp(): void
		{
			$_GET = [];
			$_REQUEST = [];
			putenv('DEBUG=1');
		}

		public function testMatchRootMapsToIndexPhp(): void
		{
			$output = new class {
				public function getOutput($out = 'html') { return 'INDEX'; }
				public function getHelp() { return 'HELP'; }
			};

			$route = $this->makeRouteWithOutput($output);

			$match = $route->match('/');
			$this->assertSame(['data' => '', 'name' => 'index', 'out' => 'php'], $match);
		}

		public function testMatchNameOutWithoutLanguage(): void
		{
			$output = new class {
				public function getOutput($out = 'html') { return 'FOO'; }
				public function getHelp() { return 'HELP'; }
			};

			$route = $this->makeRouteWithOutput($output);

			$match = $route->match('/foo.json');
			$this->assertSame(['data' => '', 'name' => 'foo', 'out' => 'json'], $match);
		}

		public function testMatchLanguageNameOut(): void
		{
			$output = new class {
				public function getOutput($out = 'html') { return 'FOO'; }
				public function getHelp() { return 'HELP'; }
			};

			$route = $this->makeRouteWithOutput($output);

			$match = $route->match('/de/foo.html');
			$this->assertSame(['data' => 'de', 'name' => 'foo', 'out' => 'html'], $match);
		}

		public function testDispatchMapsPhpToHtmlAndCallsOutput(): void
		{
			$output = new class {
				public function getOutput($out = 'html') { return 'OUT:' . $out; }
				public function getHelp() { return 'HELP'; }
			};

			$route = $this->makeRouteWithOutput($output);

			$result = $route->dispatch(['data' => '', 'name' => 'index', 'out' => 'php']);

			$this->assertSame('index', $_GET['name']);
			$this->assertSame('OUT:html', $result);
		}

		public function testDispatchHelpReturnsHelpWhenDebugEnabled(): void
		{
			$output = new class {
				public function getOutput($out = 'html') { return 'OUT:' . $out; }
				public function getHelp() { return 'THE HELP'; }
			};

			$route = $this->makeRouteWithOutput($output);

			$result = $route->dispatch(['data' => '', 'name' => 'foo', 'out' => 'help']);

			$this->assertSame('THE HELP', $result);
		}

		public function testDispatchHelpReturnsEmptyWhenDebugDisabled(): void
		{
			putenv('DEBUG=');

			$output = new class {
				public function getOutput($out = 'html') { return 'OUT:' . $out; }
				public function getHelp() { return 'THE HELP'; }
			};

			$route = $this->makeRouteWithOutput($output);

			$result = $route->dispatch(['data' => '', 'name' => 'foo', 'out' => 'help']);

			$this->assertSame('', $result);
		}
	}
}
