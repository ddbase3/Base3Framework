<?php declare(strict_types=1);

namespace Base3Test\Core;

use Base3\Api\IContainer;
use Base3\Api\ICheck;
use Base3\Core\Check;
use PHPUnit\Framework\TestCase;

final class CheckTest extends TestCase {

	protected function setUp(): void {
		if (!defined('DIR_TMP')) {
			$tmp = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'base3-tests' . DIRECTORY_SEPARATOR;
			if (!is_dir($tmp)) @mkdir($tmp, 0777, true);
			define('DIR_TMP', $tmp);
		}
	}

	public function testGetNameAndHelpAndCheckDependencies(): void {
		$container = $this->makeContainer([]);
		$check = new Check($container);

		$this->assertSame('check', Check::getName());
		$this->assertSame("Help of Check\n", $check->getHelp());

		$deps = $check->checkDependencies();
		$this->assertArrayHasKey('tmp_dir_writable', $deps);
		$this->assertTrue(in_array($deps['tmp_dir_writable'], ['Ok', 'tmp dir not writable'], true));
	}

	public function testGetOutputReturnsEmptyStringWhenDebugIsOff(): void {
		putenv('DEBUG=0');

		$container = $this->makeContainer([
			'a' => null,
		]);

		$check = new Check($container);
		$this->assertSame('', $check->getOutput('html'));
	}

	public function testGetOutputBuildsHtmlAndCoversAllBranchesWhenDebugIsOn(): void {
		putenv('DEBUG=1');

		$services = [];

		// null service -> "no service"
		$services['null_service'] = null;

		// array service -> recursion into array entries
		$services['array_service'] = [
			'ok' => new class implements ICheck {
				public function checkDependencies(): array {
					return ['dep_ok' => 'Ok'];
				}
			},
			'fail' => new class implements ICheck {
				public function checkDependencies(): array {
					return ['dep_fail' => 'Fail'];
				}
			},
		];

		// closure service with optional param -> triggers "third party service" branch safely
		$services['closure_third_party'] = function ($container = null) {
			return new class implements ICheck {
				public function checkDependencies(): array {
					return ['closure_dep' => 'Ok'];
				}
			};
		};

		// ICheck returning empty array -> "empty check"
		$services['icheck_empty'] = new class implements ICheck {
			public function checkDependencies(): array {
				return [];
			}
		};

		// service without check -> "no check"
		$services['no_check'] = new \stdClass();

		// container->get throws -> "exception: ..."
		$services['throws'] = new class {};

		$container = $this->makeContainer($services, [
			'throws' => new \RuntimeException('boom'),
		]);

		$check = new Check($container);

		// The implementation compares an array of ReflectionParameters to int:
		// "$params > 0" can trigger a warning. We suppress warnings only for this call.
		set_error_handler(function () {
			return true;
		});

		$html = $check->getOutput('html');

		restore_error_handler();

		$this->assertStringContainsString('<h1>Check</h1>', $html);

		// null branch
		$this->assertStringContainsString('null_service', $html);
		$this->assertStringContainsString('no service', $html);

		// array recursion branch
		$this->assertStringContainsString('array_service[ok]', $html);
		$this->assertStringContainsString('dep_ok', $html);
		$this->assertStringContainsString('Ok', $html);

		$this->assertStringContainsString('array_service[fail]', $html);
		$this->assertStringContainsString('dep_fail', $html);
		$this->assertStringContainsString('Fail', $html);

		// closure branch: third party + check instance
		$this->assertStringContainsString('closure_third_party', $html);
		$this->assertStringContainsString('third party service', $html);
		$this->assertStringContainsString('closure_dep', $html);

		// ICheck -> empty check
		$this->assertStringContainsString('icheck_empty', $html);
		$this->assertStringContainsString('empty check', $html);

		// default "no check"
		$this->assertStringContainsString('no_check', $html);
		$this->assertStringContainsString('no check', $html);

		// exception branch (container->get throws)
		$this->assertStringContainsString('throws', $html);
		$this->assertStringContainsString('exception: boom', $html);

		// "not defined" branch is unreachable with current implementation (no entry sets data === null).
		$this->assertStringNotContainsString('not defined', $html);
	}

	/**
	 * @param array<string, mixed> $services
	 * @param array<string, \Throwable> $throwOnGet
	 */
	private function makeContainer(array $services, array $throwOnGet = []): IContainer {
		return new class($services, $throwOnGet) implements IContainer {

			private array $services;
			private array $throwOnGet;

			public function __construct(array $services, array $throwOnGet) {
				$this->services = $services;
				$this->throwOnGet = $throwOnGet;
			}

			public function getServiceList(): array {
				return array_keys($this->services);
			}

			public function set(string $name, $classDefinition, $flags = 0): IContainer {
				$this->services[$name] = $classDefinition;
				return $this;
			}

			public function remove(string $name) {
				unset($this->services[$name]);
			}

			public function has(string $name): bool {
				return array_key_exists($name, $this->services);
			}

			public function get(string $name) {
				if (isset($this->throwOnGet[$name])) {
					throw $this->throwOnGet[$name];
				}
				return $this->services[$name] ?? null;
			}
		};
	}
}
