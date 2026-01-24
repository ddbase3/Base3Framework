<?php declare(strict_types=1);

namespace Base3\Test\Route\QueryLegacy;

use PHPUnit\Framework\TestCase;
use Base3\Route\QueryLegacy\QueryLegacyRoute;
use Base3\Api\IContainer;
use Base3\Api\IRequest;
use Base3\Configuration\Api\IConfiguration;
use Base3\Accesscontrol\Api\IAccesscontrol;
use Base3\Api\IClassMap;
use Base3\Api\IOutput;
use Base3\Test\Core\ClassMapStub;
use Base3\Test\Core\ContainerStub;
use Base3\Test\Configuration\ConfigurationStub;
use Base3\Test\Core\OutputStub;

final class QueryLegacyRouteTest extends TestCase {

	private function makeConfig(array $baseConfig = []): IConfiguration {
		$cnf = new ConfigurationStub();
		$cnf->setGroup('base', array_merge(['url' => '/', 'intern' => ''], $baseConfig), true);
		return $cnf;
	}

	private function makeAccesscontrol(string $userId = ''): IAccesscontrol {
		return new class($userId) implements IAccesscontrol {
			public function __construct(private string $userId) {}
			public function getUserId() { return $this->userId; }
			public function authenticate(): void {}
		};
	}

	private function makeRequest(array $get): IRequest {
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

	private function makeClassMap(IOutput $output): IClassMap {
		$cm = new ClassMapStub();
		$cm->registerInterface(IOutput::class, get_class($output));
		$cm->registerName($output->getRegisteredName(), get_class($output));
		return $cm;
	}

	private function makeContainer(IConfiguration $config, IClassMap $classmap, IAccesscontrol $access, IRequest $request): IContainer {
		$c = new ContainerStub();
		$c->set('configuration', $config, IContainer::SHARED);
		$c->set('classmap', $classmap, IContainer::SHARED);
		$c->set('accesscontrol', $access, IContainer::SHARED);
		$c->set(IRequest::class, $request, IContainer::SHARED);
		$c->set('middlewares', [], IContainer::SHARED | IContainer::PARAMETER);
		return $c;
	}

	protected function setUp(): void {
		$_GET = [];
		$_REQUEST = [];
		putenv('DEBUG=1');
	}

	public function testMatchReturnsNullIfNoLegacyParamsPresent(): void {
		$container = $this->makeContainer(
			$this->makeConfig(['url' => '/', 'intern' => '']),
			$this->makeClassMap(new OutputStub('index', fn(string $out): string => 'INDEX')),
			$this->makeAccesscontrol(''),
			$this->makeRequest([])
		);

		$route = new QueryLegacyRoute($container);

		$this->assertNull($route->match('/ignored'));
	}

	public function testMatchReturnsLegacyArrayIfAnyParamPresent(): void {
		$_GET['name'] = 'index';

		$container = $this->makeContainer(
			$this->makeConfig(['url' => '/', 'intern' => '']),
			$this->makeClassMap(new OutputStub('index', fn(string $out): string => 'INDEX')),
			$this->makeAccesscontrol(''),
			$this->makeRequest([])
		);

		$route = new QueryLegacyRoute($container);

		$this->assertSame(['legacy' => true], $route->match('/ignored'));
	}

	public function testDispatchDelegatesToAbstractServiceSelectorProcess(): void {
		$_GET['name'] = 'foo';
		$_GET['out']  = 'html';
		$_REQUEST = $_GET;

		$output = new OutputStub('foo', fn(string $out): string => 'OUT:' . $out);

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
