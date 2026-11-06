<?php declare(strict_types=1);

namespace Base3\Test\ServiceSelector;

use Base3\Accesscontrol\Api\IAccesscontrol;
use Base3\Api\IClassMap;
use Base3\Api\IContainer;
use Base3\Api\IOutput;
use Base3\Api\IRequest;
use Base3\Configuration\Api\IConfiguration;
use Base3\ServiceSelector\LangBased\LangBasedServiceSelector;
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
