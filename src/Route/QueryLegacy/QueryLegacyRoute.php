<?php declare(strict_types=1);

namespace Base3\Route\QueryLegacy;

use Base3\Route\Api\IRoute;
use Base3\Api\IContainer;
use Base3\Api\IRequest;
use Base3\Api\IClassMap;
use Base3\Accesscontrol\Api\IAccesscontrol;
use Base3\Configuration\Api\IConfiguration;
use Base3\ServiceSelector\AbstractServiceSelector;

/**
 * Legacy query-string based routing.
 *
 * This route activates when classic BASE3 parameters (?name=..., ?out=..., ?app=...)
 * are present. It delegates the entire execution to AbstractServiceSelector,
 * preserving all historical behaviour while coexisting with new path-based routes.
 */
class QueryLegacyRoute implements IRoute {

	public function __construct(
		private readonly IContainer $container
	) {}

	/**
	 * Match whenever legacy routing parameters are present.
	 *
	 * @param string $path Normalized path (ignored here).
	 * @return array|null Match data if legacy mode should be used.
	 */
	public function match(string $path): ?array {
		if (isset($_GET['name']) || isset($_GET['out']) || isset($_GET['app'])) {
			return ['legacy' => true];
		}
		return null;
	}

	/**
	 * Delegate completely to AbstractServiceSelector.
	 *
	 * @param array $match Always contains 'legacy' => true.
	 * @return string Response body produced by legacy selector.
	 */
	public function dispatch(array $match): string {
		// Create a selector instance on the fly
		$selector = new class($this->container) extends AbstractServiceSelector {
			public function __construct(IContainer $c) { parent::__construct($c); }
		};

		return $selector->process();
	}
}
