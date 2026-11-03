<?php declare(strict_types=1);

namespace Base3\Test\Microservice;

use Base3\Api\IContainer;
use Base3\Core\ServiceLocator;
use Base3\Microservice\Api\IMicroserviceReceiver;
use Base3\Microservice\Microservice\MicroserviceConnector;
use Base3\Microservice\Microservice\MicroserviceHelper;
use Base3\Test\Configuration\ConfigurationStub;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Regression coverage for resolving services through a simulated master registry.
 * A separate process is required because MicroserviceHelper uses the immutable DIR_TMP constant.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class MicroserviceHelperMasterTest extends TestCase {

	private string $tmpDir = '';
	private string $registry = '';

	protected function setUp(): void {
		self::assertFalse(defined('DIR_TMP'), 'DIR_TMP must not be defined before an isolated microservice test.');

		$this->tmpDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
			. 'base3-microservice-master-test-' . bin2hex(random_bytes(8)) . DIRECTORY_SEPARATOR;
		self::assertTrue(mkdir($this->tmpDir, 0700, true), 'Could not create the isolated test directory.');
		define('DIR_TMP', $this->tmpDir);
		$this->registry = DIR_TMP . 'microservices.json';
	}

	protected function tearDown(): void {
		if ($this->registry !== '' && is_file($this->registry)) unlink($this->registry);
		if ($this->tmpDir !== '' && is_dir($this->tmpDir)) rmdir($this->tmpDir);
	}

	public function testMissingServiceIsResolvedFromMasterWithoutOverwritingManualRegistryOnFailure(): void {
		$container = new ServiceLocator();
		ServiceLocator::useInstance($container);
		$container->set('configuration', new ConfigurationStub([
			'base' => ['url' => 'https://client.example.test'],
			'microservice' => [
				'name' => 'Client',
				'masterurl' => 'https://master.example.test',
				'masterpass' => 'test-only'
			]
		]), IContainer::SHARED);

		$connections = 0;
		$advertised = [];
		$masterResponse = ['Client' => 'https://client.example.test', 'Remote' => 'https://remote.example.test'];

		$helper = $this->getMockBuilder(MicroserviceHelper::class)
			->onlyMethods(['connect'])
			->getMock();
		$helper->expects(self::exactly(2))->method('connect')->willReturnCallback(
			function() use ($helper, &$connections, &$advertised, &$masterResponse): void {
				$connections++;
				$advertised = $helper->getMicroserviceList();
				if ($masterResponse === null) return;
				file_put_contents($this->registry, json_encode($masterResponse));
			}
		);

		self::assertSame(0, $connections, 'Constructor contacted the master.');
		self::assertFileDoesNotExist($this->registry, 'Constructor created a registry.');

		$remote = $helper->get('Remote', IMicroserviceReceiver::class, 'microservicereceiver');
		self::assertSame(1, $connections, 'Missing service did not trigger exactly one connect attempt.');
		self::assertSame(['Client' => 'https://client.example.test'], $advertised, 'Wrong local services offered for registration.');
		self::assertInstanceOf(MicroserviceConnector::class, $remote, 'Resolution did not use the production connector.');
		self::assertSame(
			realpath(dirname(__DIR__, 2) . '/src/Microservice/Microservice/MicroserviceConnector.php'),
			realpath((new \ReflectionClass(MicroserviceConnector::class))->getFileName()),
			'Production connector was shadowed by a test class.'
		);
		self::assertSame('https://remote.example.test/microservicereceiver.json', $remote->getMicroserviceUrl(), 'Remote service was not resolved.');
		self::assertSame($masterResponse, json_decode(file_get_contents($this->registry), true), 'Simulated master registry was not persisted.');

		$manual = ['Manual' => 'https://manual.example.test'];
		file_put_contents($this->registry, json_encode($manual));
		$masterResponse = null;
		self::assertNull($helper->get('Unavailable', IMicroserviceReceiver::class, 'microservicereceiver'), 'Unexpected service from failed master.');
		self::assertSame(2, $connections, 'A missing service triggered an unexpected number of connections.');
		self::assertSame($manual, json_decode(file_get_contents($this->registry), true), 'Failed simulated response overwrote the manual registry.');
	}

}
