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

namespace Base3\Test\ServiceSelector;

use Base3\CoreFoundation\Accesscontrol\Api\IAccesscontrol;
use Base3\CoreFoundation\Api\IClassMap;
use Base3\CoreFoundation\Api\IContainer;
use Base3\CoreFoundation\Api\IOutput;
use Base3\CoreFoundation\Api\IRequest;
use Base3\CoreFoundation\Configuration\Api\IConfiguration;
use Base3\Core\ServiceSelector\LangBased\LangBasedServiceSelector;
use PHPUnit\Framework\TestCase;

final class AbstractServiceSelectorDefaultIndexTest extends TestCase {

	private ?array $previousRequest;

	protected function setUp(): void {
		$this->previousRequest = $_REQUEST ?? null;
		$_REQUEST = [];
	}

	protected function tearDown(): void {
		if ($this->previousRequest === null) unset($_REQUEST);
		else $_REQUEST = $this->previousRequest;
	}

	public function testRootRouteExposesTheResolvedIndexNameToPageModules(): void {
		$output = $this->createStub(IOutput::class);
		$output->method('getOutput')->willReturnCallback(static function(string $out, bool $final): string {
			self::assertSame('index', $_REQUEST['name']);
			self::assertSame('html', $out);
			self::assertTrue($final);
			return '<html>Index</html>';
		});
		self::assertSame('<html>Index</html>', $this->selector($output, null)->process());
		self::assertSame('index', $_REQUEST['name']);
	}

	public function testNamedRouteRetainsItsExistingPageName(): void {
		$_REQUEST['name'] = 'settings';
		$output = $this->createStub(IOutput::class);
		$output->method('getOutput')->willReturn('<html>Settings</html>');
		self::assertSame('<html>Settings</html>', $this->selector($output, 'settings')->process());
		self::assertSame('settings', $_REQUEST['name']);
	}

	private function selector(IOutput $output, ?string $name): LangBasedServiceSelector {
		$request = $this->createStub(IRequest::class);
		$request->method('get')->willReturnCallback(
			static fn(string $key, $default = null) => $key === 'name' && $name !== null ? $name : $default
		);
		$configuration = $this->createStub(IConfiguration::class);
		$configuration->method('get')->willReturn(['url' => '/', 'intern' => '']);
		$accesscontrol = $this->createStub(IAccesscontrol::class);
		$accesscontrol->method('getUserId')->willReturn(null);
		$classmap = $this->createStub(IClassMap::class);
		$classmap->method('getInstanceByInterfaceName')->willReturn($output);
		$services = [
			'configuration' => $configuration,
			'classmap' => $classmap,
			'accesscontrol' => $accesscontrol,
			IRequest::class => $request
		];
		$container = $this->createStub(IContainer::class);
		$container->method('get')->willReturnCallback(
			static fn(string $key) => $services[$key] ?? null
		);
		return new LangBasedServiceSelector($container);
	}
}
