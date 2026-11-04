<?php declare(strict_types=1);

namespace Base3\Test\Microservice;

use Base3\Api\IContainer;
use Base3\Configuration\ArrayConfiguration;
use Base3\Core\ServiceLocator;
use PHPUnit\Framework\TestCase;

final class ConnectorDependencyCoverageTest extends TestCase {

	private function withConfiguration(array $config, callable $callback): void {
		$previous = ServiceLocator::getInstance();
		try {
			$container = new ServiceLocator();
			$container->set('configuration', new ArrayConfiguration(['microservice' => $config]), IContainer::SHARED);
			ServiceLocator::useInstance($container);
			$callback();
		} finally { ServiceLocator::useInstance($previous); }
	}

	private function service(): array {
		return ['name' => 'node', 'methods' => [['name' => 'ping', 'params' => []]]];
	}

	public function testInternalConnectorAcceptsStrongMasterPassAndMatchingName(): void {
		$this->withConfiguration(['masterpass' => str_repeat('a', 32)], function(): void {
			$connector = new InspectableInternalConnectorFixture('https://host.example.test/node.json', $this->service());
			$this->assertSame([
				'microservice_masterpass_defined' => 'Ok',
				'microservice_masterpass_length' => 'Ok',
				'service_available' => 'Ok'
			], $connector->checkDependencies());
			$this->assertSame('getName', $connector->posts[0][1]['call']);
			$this->assertSame('https://host.example.test/node.json', $connector->posts[0][0]);
		});
	}

	public function testExternalConnectorAcceptsStrongMasterPassAndMatchingName(): void {
		$this->withConfiguration(['masterpass' => str_repeat('a', 32)], function(): void {
			$connector = new InspectableExternConnectorFixture('https://host.example.test/node.json', $this->service());
			$this->assertSame([
				'microservice_masterpass_defined' => 'Ok',
				'microservice_masterpass_length' => 'Ok',
				'service_available' => 'Ok'
			], $connector->checkDependencies());
			$this->assertCount(1, $connector->posts);
		});
	}

	public function testInternalConnectorRejectsUnrelatedServiceName(): void {
		$this->withConfiguration(['masterpass' => str_repeat('a', 32)], function(): void {
			$connector = new InspectableInternalConnectorFixture('https://host.example.test/node.json', $this->service());
			$connector->reply = '"another"';
			$this->assertStringContainsString('Failed to call service', $connector->checkDependencies()['service_available']);
		});
	}

	public function testExternalConnectorRejectsUnrelatedServiceName(): void {
		$this->withConfiguration(['masterpass' => str_repeat('a', 32)], function(): void {
			$connector = new InspectableExternConnectorFixture('https://host.example.test/node.json', $this->service());
			$connector->reply = '"another"';
			$this->assertStringContainsString('Failed to call service', $connector->checkDependencies()['service_available']);
		});
	}

	public function testMissingPassphraseIsDetectedByBothConnectors(): void {
		$this->withConfiguration([], function(): void {
			foreach ([new InspectableInternalConnectorFixture('https://host/node.json', $this->service()),
				new InspectableExternConnectorFixture('https://host/node.json', $this->service())] as $connector) {
				$checks = $connector->checkDependencies();
				$this->assertSame('masterpass not defined', $checks['microservice_masterpass_defined']);
				$this->assertSame('masterpass to short', $checks['microservice_masterpass_length']);
			}
		});
	}

	public function testTooShortPassphraseIsReportedWithoutChangingServiceStatus(): void {
		$this->withConfiguration(['masterpass' => 'short'], function(): void {
			foreach ([new InspectableInternalConnectorFixture('https://host/node.json', $this->service()),
				new InspectableExternConnectorFixture('https://host/node.json', $this->service())] as $connector) {
				$checks = $connector->checkDependencies();
				$this->assertSame('Ok', $checks['microservice_masterpass_defined']);
				$this->assertSame('masterpass to short', $checks['microservice_masterpass_length']);
				$this->assertSame('Ok', $checks['service_available']);
			}
		});
	}

	public function testInternalConnectorRejectsEmptyRemoteResult(): void {
		$this->withConfiguration(['masterpass' => str_repeat('a', 32)], function(): void {
			$connector = new InspectableInternalConnectorFixture('https://host/node.json', $this->service());
			$connector->reply = false;
			$this->assertStringContainsString('Failed to call service', $connector->checkDependencies()['service_available']);
		});
	}
}

require_once __DIR__ . '/InspectableInternalConnectorFixture.php';
require_once __DIR__ . '/InspectableExternConnectorFixture.php';
