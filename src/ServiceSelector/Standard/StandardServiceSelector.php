<?php declare(strict_types=1);

namespace Base3\ServiceSelector\Standard;

use Base3\Api\ICheck;
use Base3\Api\IOutput;
use Base3\Api\IRequest;
use Base3\Core\ServiceLocator;
use Base3\Middleware\Api\IMiddleware;
use Base3\Page\Api\IPageCatchall;
use Base3\ServiceSelector\Api\IServiceSelector;

class StandardServiceSelector implements IServiceSelector, IMiddleware, ICheck {

	private $servicelocator;
	private $request;

	private static $instance;

	private function __construct() {
		$this->servicelocator = ServiceLocator::getInstance();
		$this->request = $this->servicelocator->get(IRequest::class);
	}

	public static function getInstance() {
		if (self::$instance === null) self::$instance = new self();
		return self::$instance;
	}

	// Implementation of IServiceSelector

	public function go(): string {
		$middlewares = $this->servicelocator->get('middlewares');
		if (empty($middlewares)) return $this->process();

		$prev = null;
		foreach ($middlewares as $middleware) {
			if ($prev !== null) $prev->setNext($middleware);
			$prev = $middleware;
		}

		$prev->setNext($this);

		return $middlewares[0]->process();
	}

	// Implementation of IMiddleware

	public final function setNext($next) {
		// do nothing
	}

	public function process(): string {
		$classmap = $this->servicelocator->get('classmap');
		$configuration = $this->servicelocator->get('configuration');
		$accesscontrol = $this->servicelocator->get('accesscontrol');

		$out = $this->request->get('out', 'html');
		$data = $this->request->get('data', '');
		$app = $this->request->get('app', '');
		$name = $this->request->get('name', 'index');

		$url = $configuration->get('base')["url"];
		$intern = $configuration->get('base')["intern"];
		if (!is_null($accesscontrol) && !empty($accesscontrol->getUserId()) && !empty($intern) && $name == "index") {
			header("Location: " . $url . $intern);
			exit;
		}

		$instance = empty($app)
			? $classmap->getInstanceByInterfaceName(IOutput::class, $name)
			: $classmap->getInstanceByAppInterfaceName($app, IOutput::class, $name);
		if ($instance == null) {
			$instances = $classmap->getInstancesByInterface(IPageCatchall::class);
			$instance = reset($instances);
		}

		$output = "";
		switch (true) {

			case !is_object($instance):
				header("HTTP/1.0 404 Not Found");
				die("404 Not Found\n");

			case $out == "help":

				if (!getenv('DEBUG')) exit;

				echo $instance->getHelp();
				exit;

			default:
				if ($out == "json") header('Content-Type: application/json');
				echo $instance->getOutput($out);
				exit;
		}

		return $output ?? '';
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->servicelocator->get('classmap') == null ? "Fail" : "Ok"
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
RewriteRule ^(.+)/(.+)\.(.+) index.php?app=$1&name=$2&out=$3 [L,QSA]
RewriteRule ^(.+)\.(.+) index.php?app=&name=$1&out=$2 [L,QSA]

*/
