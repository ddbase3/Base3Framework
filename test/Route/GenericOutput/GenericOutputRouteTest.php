<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of BASE3 Framework.
 *
 * BASE3 Framework is a lightweight, modular PHP framework for scalable
 * and maintainable web applications. Built for extensibility,
 * performance, and modern development, it can run standalone or
 * integrate as a subsystem within a host system.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/Base3Framework
 **********************************************************************/

namespace Base3\Test\Route\GenericOutput;

use PHPUnit\Framework\TestCase;
use Base3\Core\Route\GenericOutput\GenericOutputRoute;
use Base3\CoreFoundation\Configuration\Api\IConfiguration;
use Base3\CoreFoundation\Accesscontrol\Api\IAccesscontrol;
use Base3\CoreFoundation\Api\IClassMap;
use Base3\CoreFoundation\Api\IOutput;
use Base3\Test\Core\ClassMapStub;
use Base3\Test\Configuration\ConfigurationStub;
use Base3\Test\Core\OutputStub;

final class GenericOutputRouteTest extends TestCase {

	private function makeConfig(array $baseConfig = []): IConfiguration {
		$cnf = new ConfigurationStub();

		if (!empty($baseConfig)) {
			$cnf->setGroup('base', $baseConfig, true);
		}

		return $cnf;
	}

	private function makeAccesscontrol(string $userId = ''): IAccesscontrol {
		return new GenericOutputRouteTestDouble1($userId);
	}

	private function makeClassMap(IOutput $output): IClassMap {
		$cm = new ClassMapStub();

		// IMPORTANT: bind the *instance* so constructor callbacks are preserved.
		$cm->registerInstance($output, $output->getRegisteredName(), [IOutput::class]);

		return $cm;
	}

	private function makeRouteWithOutput(IOutput $output, array $baseConfig = [], ?object $language = null): GenericOutputRoute {
		return new GenericOutputRoute(
			$this->makeClassMap($output),
			$this->makeConfig($baseConfig),
			$this->makeAccesscontrol(''), // avoid redirect+exit path
			$language
		);
	}

	protected function setUp(): void {
		$_GET = [];
		$_REQUEST = [];
		putenv('DEBUG=1');
	}

	public function testMatchRootMapsToIndexPhp(): void {
		$output = new OutputStub('index', function(string $out): string {
			return 'INDEX';
		});

		$route = $this->makeRouteWithOutput($output);

		$match = $route->match('/');
		$this->assertSame(['data' => '', 'name' => 'index', 'out' => 'php'], $match);
	}

	public function testMatchNameOutWithoutLanguage(): void {
		$output = new OutputStub('foo', function(string $out): string {
			return 'FOO';
		});

		$route = $this->makeRouteWithOutput($output);

		$match = $route->match('/foo.json');
		$this->assertSame(['data' => '', 'name' => 'foo', 'out' => 'json'], $match);
	}

	public function testMatchLanguageNameOut(): void {
		$output = new OutputStub('foo', function(string $out): string {
			return 'FOO';
		});

		$route = $this->makeRouteWithOutput($output);

		$match = $route->match('/de/foo.html');
		$this->assertSame(['data' => 'de', 'name' => 'foo', 'out' => 'html'], $match);
	}

	public function testDispatchMapsPhpToHtmlAndCallsOutput(): void {
		$output = new OutputStub('index', function(string $out): string {
			return 'OUT:' . $out;
		});

		$route = $this->makeRouteWithOutput($output);

		$result = $route->dispatch(['data' => '', 'name' => 'index', 'out' => 'php']);

		$this->assertSame('index', $_GET['name']);
		$this->assertSame('OUT:html', $result);
	}

	public function testDispatchHelpReturnsHelpWhenDebugEnabled(): void {
		$output = new OutputStub('foo', function(string $out): string {
			return 'OUT:' . $out;
		}, function(): string {
			return 'THE HELP';
		});

		$route = $this->makeRouteWithOutput($output);

		$result = $route->dispatch(['data' => '', 'name' => 'foo', 'out' => 'help']);

		$this->assertSame('THE HELP', $result);
	}

	public function testDispatchHelpReturnsEmptyWhenDebugDisabled(): void {
		putenv('DEBUG=');

		$output = new OutputStub('foo', function(string $out): string {
			return 'OUT:' . $out;
		}, function(): string {
			return 'THE HELP';
		});

		$route = $this->makeRouteWithOutput($output);

		$result = $route->dispatch(['data' => '', 'name' => 'foo', 'out' => 'help']);

		$this->assertSame('', $result);
	}
}

require_once __DIR__ . '/GenericOutputRouteTestDouble1.php';
