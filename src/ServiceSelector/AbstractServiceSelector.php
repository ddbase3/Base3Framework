<?php declare(strict_types=1);

namespace Base3\ServiceSelector;

use Base3\Api\ICheck;
use Base3\Api\IClassMap;
use Base3\Api\IContainer;
use Base3\Api\IOutput;
use Base3\Api\IRequest;
use Base3\Accesscontrol\Api\IAccesscontrol;
use Base3\Configuration\Api\IConfiguration;
use Base3\Middleware\Api\IMiddleware;
use Base3\Page\Api\IPageCatchall;
use Base3\ServiceSelector\Api\IServiceSelector;

/**
 * Abstract base class for service selectors with middleware support.
 *
 * Handles common logic like middleware chaining, output routing, and basic request handling.
 * Subclasses can override language handling via handleLanguage().
 *
 * Example .htaccess supporting these ServiceSelectors:
 *
 * <files *.ini>
 * order deny,allow
 * deny from all
 * </files>
 *
 * RewriteEngine On
 * RewriteRule ^docs/ - [L]
 * RewriteRule ^assets/ - [L]
 * RewriteRule ^plugin/(.*)/assets/ - [L]
 * RewriteRule ^dev/ - [L]
 * RewriteRule ^plugin/(.*)/dev/ - [L]
 * RewriteRule ^tpl/ - [L]
 * RewriteRule ^userfiles/ - [L]
 * RewriteRule ^favicon.ico - [L]
 * RewriteRule ^robots.txt - [L]
 * RewriteRule ^$ index.php
 *
 * RewriteRule ^(.+)/(.+)\.(.+) index.php?data=$1&name=$2&out=$3 [L,QSA]
 * RewriteRule ^(.+)\.(.+) index.php?name=$1&out=$2 [L,QSA]
 *
 * #RewriteRule ^(.+)/(.+)\.(.+) index.php?app=$1&name=$2&out=$3 [L,QSA]
 * #RewriteRule ^(.+)\.(.+) index.php?app=&name=$1&out=$2 [L,QSA]
 */
abstract class AbstractServiceSelector implements IServiceSelector, IMiddleware {

	protected IConfiguration $configuration;
	protected IAccesscontrol $accesscontrol;
	protected IClassMap $classmap;
	protected IRequest $request;
	protected array $middlewares;

	/**
	 * Constructor.
	 * Initializes core services from the container.
	 */
	public function __construct(protected IContainer $container) {}

	/**
	 * Starts the application by processing middleware and output routing.
	 *
	 * @return string Final rendered output
	 */
	public function go(): string {
		$this->middlewares = $this->container->get('middlewares');
		if (empty($this->middlewares)) return $this->process();

		$prev = null;
		foreach ($this->middlewares as $middleware) {
			if ($prev !== null) $prev->setNext($middleware);
			$prev = $middleware;
		}

		$prev->setNext($this);

		return $this->middlewares[0]->process();
	}

	/**
	 * Middleware chaining hook (no-op).
	 *
	 * @param IMiddleware $next Next middleware in chain
	 */
	public final function setNext($next): void {
		// no-op
	}

	/**
	 * Main request processing logic.
	 * Routes request based on "out", "app", "name", and possibly "data" (language).
	 *
	 * @return string Rendered output
	 */
	public function process(): string {
		$this->configuration = $this->container->get('configuration');
		$this->classmap = $this->container->get('classmap');
		$this->accesscontrol = $this->container->get('accesscontrol');
		$this->request = $this->container->get(IRequest::class);

		$out = $this->request->get('out', 'html');
		$data = $this->request->get('data', '');
		$app = $this->request->get('app', '');
		$name = $this->request->get('name', 'index');

		$url = $this->configuration->get('base')["url"];
		$intern = $this->configuration->get('base')["intern"];
		if (!empty($this->accesscontrol->getUserId()) && !empty($intern) && $name == "index") {
			header("Location: " . $url . $intern);
			exit;
		}

		$this->handleLanguage($data);

		$instance = empty($app)
			? $this->classmap->getInstanceByInterfaceName(IOutput::class, $name)
			: $this->classmap->getInstanceByAppInterfaceName($app, IOutput::class, $name);

		if ($instance === null) {
			$instances = $this->classmap->getInstancesByInterface(IPageCatchall::class);
			$instance = reset($instances);
		}

		switch (true) {
			case !is_object($instance):
				header("HTTP/1.0 404 Not Found");
				return "404 Not Found\n";

			case $out === "help":
				if (!getenv('DEBUG')) return '';
				return $instance->getHelp();

			default:
				if ($out === "json") header('Content-Type: application/json');
				return (string) $instance->getOutput($out);
		}
	}

	/**
	 * Optional hook for subclasses to apply language selection logic.
	 *
	 * @param string $data The "data" parameter from the request
	 */
	protected function handleLanguage(string $data): void {
		// default: do nothing
	}
}
