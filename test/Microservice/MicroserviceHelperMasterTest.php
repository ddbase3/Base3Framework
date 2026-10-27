<?php declare(strict_types=1);

/**
 * Standalone master-resolution regression test using a uniquely named test double.
 * This test does not replace or declare the production MicroserviceConnector class.
 * Run with: php test/Microservice/MicroserviceHelperMasterTest.php
 */

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

class SimulatedMasterMicroserviceHelperTest extends MicroserviceHelper {
	public int $connections = 0;
	public array $advertised = [];
	public ?array $masterResponse = [];

	public function connect() {
		$this->connections++;
		$this->advertised = $this->getMicroserviceList();
		if ($this->masterResponse === null) return;
		file_put_contents(DIR_TMP . 'microservices.json', json_encode($this->masterResponse));
	}
}


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
	$helper = new SimulatedMasterMicroserviceHelperTest();
	$helper->masterResponse = ['Client' => 'https://client.example.test', 'Remote' => 'https://remote.example.test'];
	$verify($helper->connections === 0, 'Constructor contacted the master.');
	$verify(!file_exists($registry), 'Constructor created a registry.');

	$remote = $helper->get('Remote', IMicroserviceReceiver::class, 'microservicereceiver');
	$verify($helper->connections === 1, 'Missing service did not trigger exactly one connect attempt.');
	$verify($helper->advertised === ['Client' => 'https://client.example.test'], 'Wrong local services offered for registration.');
	$verify($remote instanceof MicroserviceConnector, 'Resolution did not use the production connector.');
	$verify(realpath((new \ReflectionClass(MicroserviceConnector::class))->getFileName()) === realpath(DIR_SRC . 'Microservice/Microservice/MicroserviceConnector.php'), 'Production connector was shadowed by a test class.');
	$verify($remote->getMicroserviceUrl() === 'https://remote.example.test/microservicereceiver.json', 'Remote service was not resolved.');
	$verify(json_decode(file_get_contents($registry), true) === $helper->masterResponse, 'Simulated master registry was not persisted.');

	$manual = ['Manual' => 'https://manual.example.test'];
	file_put_contents($registry, json_encode($manual));
	$helper->masterResponse = null;
	$verify($helper->get('Unavailable', IMicroserviceReceiver::class, 'microservicereceiver') === null, 'Unexpected service from failed master.');
	$verify($helper->connections === 2, 'A missing service triggered an unexpected number of connections.');
	$verify(json_decode(file_get_contents($registry), true) === $manual, 'Failed simulated response overwrote the manual registry.');

	echo 'MicroserviceHelper master test: OK (' . $checks . " checks)\n";
} finally {
	if (file_exists($registry)) unlink($registry);
	if (is_dir($tmp)) rmdir($tmp);
}
