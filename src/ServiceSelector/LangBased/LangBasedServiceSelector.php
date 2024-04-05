<?php

namespace ServiceSelector\LangBased;

use ServiceSelector\Api\IServiceSelector;
use Middleware\Api\IMiddleware;
use Api\ICheck;

class LangBasedServiceSelector implements IServiceSelector, IMiddleware, ICheck {

	private $servicelocator;

	private static $instance;
 
	private function __construct() {

		$this->servicelocator = \Base3\ServiceLocator::getInstance();

		if (php_sapi_name() != "cli") return;
		$options = getopt("", array("app:", "name:", "out:"));
	        if (isset($options["app"])) $_REQUEST["app"] = $_GET["app"] = $options["app"];
        	if (isset($options["name"])) $_REQUEST["name"] = $_GET["name"] = $options["name"];
	        if (isset($options["out"])) $_REQUEST["out"] = $_GET["out"] = $options["out"];
	}
 
	// private function __clone() {}
 	// private function __wakeup() {}

	public static function getInstance() {
		if (self::$instance === null) self::$instance = new self();
		return self::$instance;
	}

	// Implementation of IServiceSelector

	public function go() {
		$middlewares = $this->servicelocator->get('middlewares');
		if (!isset($middlewares) || count($middlewares) == 0) {
			echo $this->process();
			return;
		}

		$prev = null;
		foreach ($middlewares as $middleware) {
			if ($prev != null) $prev->setNext($middleware);
			$prev = $middleware;
		}
		$prev->setNext($this);
		echo $middlewares[0]->process();
	}

	// Implementation of IMiddleware

	public function setNext($next) {
		// do nothing
	}

	public function process() {
		$classmap = $this->servicelocator->get('classmap');
		$language = $this->servicelocator->get('language');
		$configuration = $this->servicelocator->get('configuration');
		$accesscontrol = $this->servicelocator->get('accesscontrol');

		$out = $_REQUEST['out'] = isset($_REQUEST['out']) && strlen($_REQUEST['out']) ? $_REQUEST['out'] : 'html';
		$data = $_REQUEST['data'] = isset($_REQUEST['data']) && strlen($_REQUEST['data']) ? $_REQUEST['data'] : '';
		$app = $_REQUEST['app'] = isset($_REQUEST['app']) && strlen($_REQUEST['app']) ? $_REQUEST['app'] : '';
		$name = $_REQUEST['name'] = isset($_REQUEST['name']) && strlen($_REQUEST['name']) ? $_REQUEST['name'] : 'index';

		$url = $configuration->get('base')["url"];
		$intern = $configuration->get('base')["intern"];
		if (strlen($accesscontrol->getUserId()) && isset($intern) && strlen($intern) && $name == "index") {
			header("Location: " . $url . $intern);
			exit;
		}

		if (strlen($data) == 2) $language->setLanguage($data);

		$instance = strlen($app)
			? $classmap->getInstanceByAppInterfaceName($app, "Api\\IOutput", $name)
			: $classmap->getInstanceByInterfaceName("Api\\IOutput", $name);
		if ($instance == null) {
			$instances = $classmap->getInstancesByInterface("Page\\Api\\IPageCatchall");
			$instance = reset($instances);
		}

		$output = "";
		switch (true) {

			case !is_object($instance):
				header("HTTP/1.0 404 Not Found");
				die("404 Not Found\n");

			case $out == "help":
				$output = $instance->getHelp();
				break;

			default:
				if ($out == "json") header('Content-Type: application/json');
				$output = $instance->getOutput($out);
				break;
		}

		return $output;
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->servicelocator->get('classmap') == null || $this->servicelocator->get('language') == null ? "Fail" : "Ok"
		);
	}
}

/*
// .htaccess:

<files *.ini>
order deny,allow
deny from all
</files>

RewriteEngine On
RewriteRule ^assets/ - [L]
RewriteRule ^tpl/ - [L]
RewriteRule ^userfiles/ - [L]
RewriteRule ^favicon.ico - [L]
RewriteRule ^robots.txt - [L]
RewriteRule ^$ index.html
RewriteRule ^(.+)/(.+)\.(.+) index.php?data=$1&name=$2&out=$3 [L,QSA]
RewriteRule ^(.+)\.(.+) index.php?name=$1&out=$2 [L,QSA]

*/
