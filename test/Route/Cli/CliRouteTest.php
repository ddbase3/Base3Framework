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

namespace Base3\Test\Route\Cli;

use PHPUnit\Framework\TestCase;
use Base3\Core\Route\Cli\CliRoute;
use Base3\CoreFoundation\Api\IRequest;
use Base3\CoreFoundation\Api\IClassMap;
use Base3\CoreFoundation\Api\IOutput;
use Base3\Test\Core\ClassMapStub;
use Base3\Test\Core\OutputStub;

final class CliRouteTest extends TestCase {

	private function makeRequest(array $values): IRequest {
		return new CliRouteTestDouble1($values);
	}

	private function makeClassMap(?IOutput $instance): IClassMap {
		$cm = new ClassMapStub();

		if ($instance !== null) {
			$cm->registerInterface(IOutput::class, get_class($instance));
			$cm->registerName($instance->getRegisteredName(), get_class($instance));
			$cm->registerInstance($instance, $instance->getRegisteredName(), [IOutput::class]);
		}

		return $cm;
	}

	protected function setUp(): void {
		$_GET = [];
		$_REQUEST = [];
	}

	public function testMatchReturnsNullIfNoNameProvided(): void {
		$request = $this->makeRequest(['name' => '']);
		$route = new CliRoute($request, $this->makeClassMap(null));

		$this->assertNull($route->match('/anything'));
	}

	public function testMatchReturnsArrayWithDefaults(): void {
		$request = $this->makeRequest(['name' => 'phpinfo']); // out/data default handling is in route
		$route = new CliRoute($request, $this->makeClassMap(null));

		$match = $route->match('/ignored');

		$this->assertSame([
			'name' => 'phpinfo',
			'out'  => 'html',
			'data' => '',
		], $match);
	}

	public function testDispatchReturns404IfNoOutputFound(): void {
		$request = $this->makeRequest([]);
		$route = new CliRoute($request, $this->makeClassMap(null));

		$out = $route->dispatch(['name' => 'missing', 'out' => 'html', 'data' => '']);

		$this->assertSame("404 Not Found\n", $out);
	}

	public function testDispatchSetsGlobalsAndCallsOutput(): void {
		$request = $this->makeRequest([]);

		$output = new OutputStub('dummy', function(string $out): string {
			return 'OUT:' . $out;
		});

		$route = new CliRoute($request, $this->makeClassMap($output));

		$result = $route->dispatch(['name' => 'dummy', 'out' => 'json', 'data' => 'xx']);

		$this->assertSame('dummy', $_GET['name']);
		$this->assertSame('xx', $_GET['data']);
		$this->assertSame('json', $_GET['out']);

		$this->assertSame('OUT:json', $result);
	}
}

require_once __DIR__ . '/CliRouteTestDouble1.php';
