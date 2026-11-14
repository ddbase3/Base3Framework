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
use Base3\Core\Configuration\ArrayConfiguration;
use Base3\Core\ServiceLocator;
use Base3\CoreFoundation\Microservice\Api\IMicroserviceReceiver;
use Base3\Core\Microservice\Microservice\MicroserviceHelper;
use Base3\Core\Microservice\Microservice\MicroserviceConnector;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class MicroserviceHelperRegistryCoverageTest extends TestCase {

	private function withRegistry(array $config, callable $run): void {
		$this->assertFalse(defined('DIR_TMP'));
		$path = sys_get_temp_dir() . '/base3-microservice-registry-' . bin2hex(random_bytes(8));
		$this->assertTrue(mkdir($path, 0700));
		define('DIR_TMP', $path . '/');
		$previous = ServiceLocator::getInstance();
		try {
			$container = new ServiceLocator();
			$container->set('configuration', new ArrayConfiguration($config), IContainer::SHARED);
			ServiceLocator::useInstance($container);
			$run(new MicroserviceHelper(), DIR_TMP . 'microservices.json');
		} finally {
			ServiceLocator::useInstance($previous);
			if (is_file(DIR_TMP . 'microservices.json')) unlink(DIR_TMP . 'microservices.json');
			rmdir($path);
		}
	}

	public function testConnectPersistsLocallyAdvertisedServiceWithoutMaster(): void {
		$this->withRegistry(['base' => ['url' => 'https://local.example.test'], 'microservice' => ['name' => 'Local']], function(MicroserviceHelper $helper, string $path): void {
			$this->assertSame(['Local' => 'https://local.example.test'], $helper->getMicroserviceList());
			$this->assertSame(['depending_services' => 'Ok'], $helper->checkDependencies());
			$helper->connect();
			$this->assertTrue(is_file($path));
			$this->assertSame(['Local' => 'https://local.example.test'], json_decode(file_get_contents($path), true));
		});
	}

	public function testSetMergesIncomingServicesAndPersistsThem(): void {
		$this->withRegistry(['base' => ['url' => 'https://local.example.test'], 'microservice' => ['name' => 'Local']], function(MicroserviceHelper $helper, string $path): void {
			$this->assertSame(['Local' => 'https://local.example.test', 'Remote' => 'https://remote.example.test'], $helper->set(['Remote' => 'https://remote.example.test']));
			$this->assertSame(['Local' => 'https://local.example.test', 'Remote' => 'https://remote.example.test'], json_decode(file_get_contents($path), true));
		});
	}

	public function testExistingRegistryOverridesConfigurationWhenResolvingService(): void {
		$this->withRegistry(['base' => ['url' => 'https://local.example.test'], 'microservice' => ['name' => 'Local']], function(MicroserviceHelper $helper, string $path): void {
			file_put_contents($path, json_encode(['Local' => 'https://override.example.test']));
			$connector = $helper->get('Local', IMicroserviceReceiver::class, 'microservicereceiver');
			$this->assertInstanceOf(MicroserviceConnector::class, $connector);
			$this->assertSame('https://override.example.test/microservicereceiver.json', $connector->getMicroserviceUrl());
		});
	}

	public function testMalformedRegistryCannotBeTrusted(): void {
		$this->withRegistry([], function(MicroserviceHelper $helper, string $path): void {
			file_put_contents($path, '{invalid json');
			$this->assertSame([], $helper->getMicroserviceList());
			$this->assertNull($helper->get('Missing', IMicroserviceReceiver::class, 'receiver', true));
		});
	}

	public function testResetDeletesManualRegistryAndFallsBackToConfiguredService(): void {
		$this->withRegistry(['base' => ['url' => 'https://local.example.test'], 'microservice' => ['name' => 'Local']], function(MicroserviceHelper $helper, string $path): void {
			file_put_contents($path, json_encode(['Remote' => 'https://remote.example.test']));
			$helper->reset();
			$this->assertFalse(is_file($path));
			$this->assertSame(['Local' => 'https://local.example.test'], $helper->getMicroserviceList());
		});
	}

	public function testBlankNameOrUrlDoesNotAnnounceService(): void {
		$this->withRegistry(['base' => ['url' => ''], 'microservice' => ['name' => 'Local']], function(MicroserviceHelper $helper, string $path): void {
			$this->assertSame([], $helper->getMicroserviceList());
			$helper->connect();
			$this->assertFalse(is_file($path));
		});
	}
}
