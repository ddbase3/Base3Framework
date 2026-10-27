<?php declare(strict_types=1);

/**
 * Standalone master-registration regression test.
 * Uses a test-only connector class, so no HTTP request is sent.
 * Run with: php test/Microservice/MicroserviceHelperMasterSmoke.php
 */

namespace Base3\Microservice\Microservice {
	class MicroserviceConnector {
		public static int $connections = 0;
		public static array $advertised = [];
		public static ?array $response = [];
		private string $url;

		public function __construct(string $url, array $service, int $flags = 0) {
			$this->url = $url;
		}

		public function connect(array $services): ?array {
			self::$connections++;
			self::$advertised = $services;
			return self::$response;
		}

		public function getMicroserviceUrl(): string {
			return $this->url;
		}
	}
}

namespace {
	use Base3\Api\IContainer;
	use Base3\Core\Autoloader;
	use Base3\Core\ServiceLocator;
	use Base3\Microservice\Api\IMicroserviceReceiver;
	use Base3\Microservice\Microservice\MicroserviceConnector;
	use Base3\Microservice\Microservice\MicroserviceHelper;
	use Base3\Test\Configuration\ConfigurationStub;

	$root = dirname(__DIR__, 2);
	$tmp = sys_get_temp_dir() . '/base3-microservice-master-test-' . bin2hex(random_bytes(8)) . '/';
	if (!mkdir($tmp, 0700, true)) throw new \RuntimeException('Could not create test directory.');

	if (defined('DIR_TMP')) throw new \RuntimeException('Run this test as a standalone CLI script.');
	define('DIR_TMP', $tmp);
	if (!defined('DIR_ROOT')) define('DIR_ROOT', $root . '/');
	if (!defined('DIR_SRC')) define('DIR_SRC', DIR_ROOT . 'src/');
	if (!defined('DIR_PLUGIN')) define('DIR_PLUGIN', DIR_ROOT . 'plugin/');
	if (!defined('DIR_TEST')) define('DIR_TEST', DIR_ROOT . 'test/');

	require_once DIR_SRC . 'Core/Autoloader.php';
	Autoloader::register();

	$registry = DIR_TMP . 'microservices.json';
	$checks = 0;
	$verify = static function(bool $condition, string $message) use (&$checks): void {
		$checks++;
		if (!$condition) throw new \RuntimeException($message);
	};

	$container = new ServiceLocator();
	ServiceLocator::useInstance($container);
	$container->set('configuration', new ConfigurationStub([
		'base' => ['url' => 'https://client.example.test'],
		'microservice' => ['name' => 'Client', 'masterurl' => 'https://master.example.test', 'masterpass' => 'test-only']
	]), IContainer::SHARED);

	try {
		MicroserviceConnector::$response = ['Client' => 'https://client.example.test', 'Remote' => 'https://remote.example.test'];
		$helper = new MicroserviceHelper();
		$verify(MicroserviceConnector::$connections === 0, 'Constructor contacted the master.');
		$verify(!file_exists($registry), 'Constructor created a registry.');

		$remote = $helper->get('Remote', IMicroserviceReceiver::class, 'microservicereceiver');
		$verify(MicroserviceConnector::$connections === 1, 'Missing service did not register at master exactly once.');
		$verify(MicroserviceConnector::$advertised === ['Client' => 'https://client.example.test'], 'Wrong local services advertised to master.');
		$verify($remote instanceof MicroserviceConnector && $remote->getMicroserviceUrl() === 'https://remote.example.test/microservicereceiver.json', 'Remote service was not resolved.');
		$verify(json_decode(file_get_contents($registry), true) === MicroserviceConnector::$response, 'Master registry response was not persisted.');

		// When the master fails, previously registered (including manual) services remain intact.
		$manual = ['Manual' => 'https://manual.example.test'];
		file_put_contents($registry, json_encode($manual));
		MicroserviceConnector::$response = null;
		$verify($helper->get('Unavailable', IMicroserviceReceiver::class, 'microservicereceiver') === null, 'Unexpected service from failed master.');
		$verify(MicroserviceConnector::$connections === 2, 'A missing service triggered an unexpected number of connections.');
		$verify(json_decode(file_get_contents($registry), true) === $manual, 'Failed master response overwrote the manual registry.');

		echo 'MicroserviceHelper master option A: OK (' . $checks . " checks)\n";
	} finally {
		if (file_exists($registry)) unlink($registry);
		if (is_dir($tmp)) rmdir($tmp);
	}
}
