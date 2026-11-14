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

namespace Base3\Test\Microservice;

use Base3\CoreFoundation\Api\IContainer;
use Base3\Core\ServiceLocator;
use Base3\CoreFoundation\Microservice\Api\IMicroserviceReceiver;
use Base3\Core\Microservice\Microservice\MicroserviceHelper;
use Base3\Core\Microservice\MicroserviceReceiver;
use Base3\Test\Configuration\ConfigurationStub;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Regression coverage for lazy microservice registration and local registry ownership.
 * A separate process is required because MicroserviceHelper uses the immutable DIR_TMP constant.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class MicroserviceHelperTest extends TestCase {

	private string $tmpDir = '';
	private string $registry = '';

	protected function setUp(): void {
		self::assertFalse(defined('DIR_TMP'), 'DIR_TMP must not be defined before an isolated microservice test.');

		$this->tmpDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
			. 'base3-microservice-test-' . bin2hex(random_bytes(8)) . DIRECTORY_SEPARATOR;
		self::assertTrue(mkdir($this->tmpDir, 0700, true), 'Could not create the isolated test directory.');
		define('DIR_TMP', $this->tmpDir);
		$this->registry = DIR_TMP . 'microservices.json';
	}

	protected function tearDown(): void {
		if ($this->registry !== '' && is_file($this->registry)) unlink($this->registry);
		if ($this->tmpDir !== '' && is_dir($this->tmpDir)) rmdir($this->tmpDir);
	}

	private function setConfiguration(array $data): ServiceLocator {
		$container = new ServiceLocator();
		ServiceLocator::useInstance($container);
		$container->set('configuration', new ConfigurationStub($data), IContainer::SHARED);
		return $container;
	}

	public function testLazyRegistrationAndManualRegistryRemainAuthoritative(): void {
		// Unconfigured instances must not register services or write an invalid name.
		$this->setConfiguration(['base' => ['url' => 'https://node.example.test']]);
		$helper = new MicroserviceHelper();
		self::assertFileDoesNotExist($this->registry, 'Constructor created a registry without microservice configuration.');
		self::assertSame([], $helper->getMicroserviceList(), 'Unconfigured helper returned a fake service.');
		$helper->connect();
		self::assertFileDoesNotExist($this->registry, 'Explicit connect without services created a registry.');
		self::assertNull($helper->get('unknown', IMicroserviceReceiver::class, 'receiver'), 'Unknown service was resolved.');
		self::assertFileDoesNotExist($this->registry, 'A missing lookup wrote an invalid registry.');

		// Local services remain available without creating the registry at construction.
		$local = [
			'base' => ['url' => 'https://node.example.test'],
			'microservice' => ['name' => 'Local', 'masterurl' => '']
		];
		$this->setConfiguration($local);
		$helper = new MicroserviceHelper();
		self::assertFileDoesNotExist($this->registry, 'Configured constructor wrote a registry.');
		self::assertSame(['Local' => 'https://node.example.test'], $helper->getMicroserviceList(), 'Local registration was lost.');
		$connector = $helper->get('Local', IMicroserviceReceiver::class, 'microservicereceiver');
		self::assertNotNull($connector, 'Local service was not resolved.');
		self::assertSame('https://node.example.test/microservicereceiver.json', $connector->getMicroserviceUrl(), 'Local service URL is wrong.');
		self::assertFileDoesNotExist($this->registry, 'Local resolution unexpectedly wrote a registry.');
		self::assertNull($helper->get('Missing', IMicroserviceReceiver::class, 'microservicereceiver'), 'Unknown service incorrectly resolved.');
		self::assertFileExists($this->registry, 'Missing lookup did not trigger lazy registration.');
		self::assertSame(['Local' => 'https://node.example.test'], json_decode(file_get_contents($this->registry), true), 'Lazy registration wrote unexpected services.');

		// A manually maintained registry is authoritative and must not be overwritten at construction.
		$manual = ['Master' => 'https://master.example.test', 'Photos' => 'https://photos.example.test'];
		file_put_contents($this->registry, json_encode($manual));
		$original = file_get_contents($this->registry);
		$this->setConfiguration($local);
		$helper = new MicroserviceHelper();
		self::assertSame($original, file_get_contents($this->registry), 'Constructor changed manually maintained registry.');
		self::assertSame($manual, $helper->getMicroserviceList(), 'Manual services were not loaded.');
		$connector = $helper->get('Photos', IMicroserviceReceiver::class, 'microservicereceiver');
		self::assertNotNull($connector, 'Manual service was not resolved.');
		self::assertSame('https://photos.example.test/microservicereceiver.json', $connector->getMicroserviceUrl(), 'Manual service URL is wrong.');
		self::assertSame($original, file_get_contents($this->registry), 'Reading a manual service rewrote the registry.');

		// Incoming registrations still merge with the manually maintained registry.
		$container = $this->setConfiguration($local);
		$container->set('microservicehelper', $helper, IContainer::SHARED);
		$receiver = new MicroserviceReceiver();
		$registered = $receiver->connect(['NewProvider' => 'https://new.example.test']);
		self::assertSame(array_merge($manual, ['NewProvider' => 'https://new.example.test']), $registered, 'Receiver did not merge the registry.');
		self::assertSame($registered, json_decode(file_get_contents($this->registry), true), 'Receiver registration was not persisted.');

		// A client configured with a master must not contact that master during construction.
		unlink($this->registry);
		$this->setConfiguration([
			'base' => ['url' => 'https://client.example.test'],
			'microservice' => ['name' => 'Client', 'masterurl' => 'http://127.0.0.1:1']
		]);
		$helper = new MicroserviceHelper();
		self::assertFileDoesNotExist($this->registry, 'Client constructor tried to register at its master.');
		self::assertSame(['Client' => 'https://client.example.test'], $helper->getMicroserviceList(), 'Client configuration was not preserved.');

		$connections = 0;
		$helper = $this->getMockBuilder(MicroserviceHelper::class)
			->onlyMethods(['connect'])
			->getMock();
		$helper->expects(self::once())->method('connect')->willReturnCallback(static function() use (&$connections): void {
			$connections++;
		});

		self::assertSame(0, $connections, 'Constructor invoked connect.');
		self::assertNull($helper->get('Master', IMicroserviceReceiver::class, 'microservicereceiver'), 'Missing remote service unexpectedly resolved.');
		self::assertSame(1, $connections, 'Missing service did not trigger exactly one connection attempt.');
		self::assertFileDoesNotExist($this->registry, 'Counting helper unexpectedly wrote a registry.');

		$helper->reset();
		self::assertFileDoesNotExist($this->registry, 'Reset left a registry behind.');
	}

}
