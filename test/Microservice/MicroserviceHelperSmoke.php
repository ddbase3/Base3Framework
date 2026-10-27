<?php declare(strict_types=1);

/**
 * Standalone regression test for MicroserviceHelper option A.
 * Run with: php test/Microservice/MicroserviceHelperSmoke.php
 * Uses an isolated temporary registry and never changes the application's registry.
 */

use Base3\Api\IContainer;
use Base3\Core\Autoloader;
use Base3\Core\ServiceLocator;
use Base3\Microservice\Api\IMicroserviceReceiver;
use Base3\Microservice\Microservice\MicroserviceHelper;
use Base3\Microservice\MicroserviceReceiver;
use Base3\Test\Configuration\ConfigurationStub;

$root = dirname(__DIR__, 2);
$tmp = sys_get_temp_dir() . '/base3-microservice-test-' . bin2hex(random_bytes(8)) . '/';
if (!mkdir($tmp, 0700, true)) throw new \RuntimeException('Could not create test directory.');

if (defined('DIR_TMP')) throw new \RuntimeException('Run this test as a standalone CLI script.');
define('DIR_TMP', $tmp);
if (!defined('DIR_ROOT')) define('DIR_ROOT', $root . '/');
if (!defined('DIR_SRC')) define('DIR_SRC', DIR_ROOT . 'src/');
if (!defined('DIR_PLUGIN')) define('DIR_PLUGIN', DIR_ROOT . 'plugin/');
if (!defined('DIR_TEST')) define('DIR_TEST', DIR_ROOT . 'test/');

require_once DIR_SRC . 'Core/Autoloader.php';
Autoloader::register();

class CountingMicroserviceHelper extends MicroserviceHelper {
	public int $connections = 0;

	public function connect() {
		$this->connections++;
	}
}

$registry = DIR_TMP . 'microservices.json';
$checks = 0;

$verify = static function(bool $condition, string $message) use (&$checks): void {
	$checks++;
	if (!$condition) throw new \RuntimeException($message);
};

$setConfiguration = static function(array $data): ServiceLocator {
	$container = new ServiceLocator();
	ServiceLocator::useInstance($container);
	$container->set('configuration', new ConfigurationStub($data), IContainer::SHARED);
	return $container;
};

try {
	// Unconfigured instances must neither register services nor write an invalid name.
	$setConfiguration(['base' => ['url' => 'https://node.example.test']]);
	$helper = new MicroserviceHelper();
	$verify(!file_exists($registry), 'Constructor created a registry without microservice configuration.');
	$verify($helper->getMicroserviceList() === [], 'Unconfigured helper returned a fake service.');
	$helper->connect();
	$verify(!file_exists($registry), 'Explicit connect without services created a registry.');
	$verify($helper->get('unknown', IMicroserviceReceiver::class, 'receiver') === null, 'Unknown service was resolved.');
	$verify(!file_exists($registry), 'A missing lookup wrote an invalid registry.');

	// Local services remain available without creating the registry at construction.
	$local = ['base' => ['url' => 'https://node.example.test'], 'microservice' => ['name' => 'Local', 'masterurl' => '']];
	$setConfiguration($local);
	$helper = new MicroserviceHelper();
	$verify(!file_exists($registry), 'Configured constructor wrote a registry.');
	$verify($helper->getMicroserviceList() === ['Local' => 'https://node.example.test'], 'Local registration was lost.');
	$connector = $helper->get('Local', IMicroserviceReceiver::class, 'microservicereceiver');
	$verify($connector !== null && $connector->getMicroserviceUrl() === 'https://node.example.test/microservicereceiver.json', 'Local service was not resolved.');
	$verify(!file_exists($registry), 'Local resolution unexpectedly wrote a registry.');
	$verify($helper->get('Missing', IMicroserviceReceiver::class, 'microservicereceiver') === null, 'Unknown service incorrectly resolved.');
	$verify(file_exists($registry), 'Missing lookup did not trigger lazy registration.');
	$verify(json_decode(file_get_contents($registry), true) === ['Local' => 'https://node.example.test'], 'Lazy registration wrote unexpected services.');

	// A manually maintained registry must remain authoritative and must not be overwritten at construction.
	$manual = ['Master' => 'https://master.example.test', 'Photos' => 'https://photos.example.test'];
	file_put_contents($registry, json_encode($manual));
	$original = file_get_contents($registry);
	$setConfiguration($local);
	$helper = new MicroserviceHelper();
	$verify(file_get_contents($registry) === $original, 'Constructor changed manually maintained registry.');
	$verify($helper->getMicroserviceList() === $manual, 'Manual services were not loaded.');
	$connector = $helper->get('Photos', IMicroserviceReceiver::class, 'microservicereceiver');
	$verify($connector !== null && $connector->getMicroserviceUrl() === 'https://photos.example.test/microservicereceiver.json', 'Manual service was not resolved.');
	$verify(file_get_contents($registry) === $original, 'Reading a manual service rewrote the registry.');

	// Incoming registrations still merge with the manually maintained registry.
	$container = $setConfiguration($local);
	$container->set('microservicehelper', $helper, IContainer::SHARED);
	$receiver = new MicroserviceReceiver();
	$registered = $receiver->connect(['NewProvider' => 'https://new.example.test']);
	$verify($registered === array_merge($manual, ['NewProvider' => 'https://new.example.test']), 'Receiver did not merge the registry.');
	$verify(json_decode(file_get_contents($registry), true) === $registered, 'Receiver registration was not persisted.');

	// A client configured with a master must not contact that master during construction.
	unlink($registry);
	$setConfiguration(['base' => ['url' => 'https://client.example.test'], 'microservice' => ['name' => 'Client', 'masterurl' => 'http://127.0.0.1:1']]);
	$helper = new MicroserviceHelper();
	$verify(!file_exists($registry), 'Client constructor tried to register at its master.');
	$verify($helper->getMicroserviceList() === ['Client' => 'https://client.example.test'], 'Client configuration was not preserved.');

	$helper = new CountingMicroserviceHelper();
	$verify($helper->connections === 0, 'Constructor invoked connect.');
	$verify($helper->get('Master', IMicroserviceReceiver::class, 'microservicereceiver') === null, 'Missing remote service unexpectedly resolved.');
	$verify($helper->connections === 1, 'Missing service did not trigger exactly one connection attempt.');
	$verify(!file_exists($registry), 'Counting helper unexpectedly wrote a registry.');

	$helper->reset();
	$verify(!file_exists($registry), 'Reset left a registry behind.');

	echo 'MicroserviceHelper option A: OK (' . $checks . " checks)\n";
} finally {
	if (file_exists($registry)) unlink($registry);
	if (is_dir($tmp)) rmdir($tmp);
}
