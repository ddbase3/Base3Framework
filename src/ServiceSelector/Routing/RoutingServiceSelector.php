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

namespace Base3\ServiceSelector\Routing;

use Base3\Api\IContainer;
use Base3\Api\IRequest;
use Base3\Api\IClassMap;
use Base3\Route\Api\IRoute;
use Base3\ServiceSelector\AbstractServiceSelector;

class RoutingServiceSelector extends AbstractServiceSelector {
	public function __construct(protected IContainer $container) {
		parent::__construct($container);
	}

	protected function handleLanguage(string $data): void {
		if (strlen($data) === 2) {
			$language = $this->container->get('language');
			$language->setLanguage($data);
		}
	}

	public function process(): string {
		$this->configuration = $this->container->get('configuration');
		$this->classmap      = $this->container->get('classmap');
		$this->accesscontrol = $this->container->get('accesscontrol');
		$this->request       = $this->container->get(IRequest::class);

		// path normalisieren
		$path = $_SERVER['REQUEST_URI'] ?? '/';
		if (($q = strpos($path, '?')) !== false) $path = substr($path, 0, $q);
		$path = '/' . ltrim($path, '/');
		if ($path === '/index.php') $path = '/';

		// routes laden (array oder closure)
		$routes = [];
		if ($this->container->has('routes')) {
			$raw = $this->container->get('routes');
			$defined = ($raw instanceof \Closure) ? $raw() : $raw;
			foreach ((array)$defined as $entry) {
				if ($entry instanceof IRoute) { $routes[] = $entry; continue; }
				if (is_string($entry)) {
					$inst = $this->classmap->instantiate($entry);
					if ($inst instanceof IRoute) $routes[] = $inst;
				}
			}
		}

		// erste Route mit Treffer gewinnt
		foreach ($routes as $route) {
			$m = $route->match($path);
			if ($m !== null) {
				if (isset($m['data']) && is_string($m['data'])) $this->handleLanguage($m['data']);
				return $route->dispatch($m);
			}
		}

		// fallback: GenericOutputRoute (über routes-liste), sonst parent
		return parent::process();
	}
}

