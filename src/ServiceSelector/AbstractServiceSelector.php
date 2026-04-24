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

namespace Base3\ServiceSelector;

use Base3\Api\IClassMap;
use Base3\Api\IContainer;
use Base3\Api\IHelp;
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
 * This class contains the common routing logic for resolving output
 * components, executing middleware chains, handling optional language
 * processing, and dispatching the final response.
 *
 * Request parameters used by the selector:
 * - out  : requested output format, default "html"
 * - data : optional data segment, often used for language or path context
 * - app  : optional application namespace or application identifier
 * - name : output/service name, default "index"
 *
 * Resolution strategy:
 * 1. Resolve an IOutput instance by name, optionally scoped by app
 * 2. If not found, try the first available IPageCatchall implementation
 * 3. Return 404 if no matching output could be resolved
 *
 * Special handling:
 * - "help" output is only returned in debug mode
 * - help is optional and only available if the resolved instance also
 *   implements IHelp
 * - "json" output automatically sets the JSON content type header
 *
 * Subclasses may override handleLanguage() to apply custom logic based on
 * the "data" request parameter.
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
	 *
	 * The selector lazily resolves its required services from the
	 * container during request processing.
	 *
	 * @param IContainer $container Dependency injection container
	 */
	public function __construct(protected IContainer $container) {}

	/**
	 * Starts the application by executing the configured middleware chain
	 * and eventually dispatching the routed output.
	 *
	 * If no middleware is configured, processing continues immediately with
	 * the selector itself.
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
	 * Middleware chaining hook.
	 *
	 * The service selector is always the terminal element of the middleware
	 * chain and therefore does not forward execution to a next middleware.
	 *
	 * @param IMiddleware $next Next middleware in the chain
	 */
	public final function setNext($next): void {
		// no-op
	}

	/**
	 * Main request processing logic.
	 *
	 * This method resolves framework core services from the container,
	 * reads the relevant routing parameters from the request, performs
	 * optional redirect handling for authenticated users, applies language
	 * logic, resolves the target output instance, and returns the final
	 * response body.
	 *
	 * Routing behavior:
	 * - resolves by app/name when app is set
	 * - resolves by name only when app is empty
	 * - falls back to IPageCatchall if no explicit output was found
	 *
	 * Output behavior:
	 * - returns 404 if no output instance could be resolved
	 * - returns help only in debug mode and only if the instance supports IHelp
	 * - sets JSON content type header for out=json
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
				if (!$instance instanceof IHelp) return '';
				return $instance->getHelp();

			default:
				if ($out === "json") header('Content-Type: application/json');
				return $instance->getOutput($out, true);
		}
	}

	/**
	 * Optional extension hook for subclasses.
	 *
	 * Subclasses may override this method to apply language selection or
	 * other context-specific processing based on the "data" request
	 * parameter before output resolution happens.
	 *
	 * The default implementation intentionally does nothing.
	 *
	 * @param string $data The "data" request parameter
	 */
	protected function handleLanguage(string $data): void {
		// default: do nothing
	}
}
