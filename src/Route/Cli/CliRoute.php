<?php declare(strict_types=1);

namespace Base3\Route\Cli;

use Base3\Route\Api\IRoute;
use Base3\Api\IRequest;
use Base3\Api\IClassMap;
use Base3\Api\IOutput;

/**
 * CLI route handler.
 *
 * Allows running outputs via CLI with parameters like:
 * php index.php --name=check --out=php
 */
final class CliRoute implements IRoute {

	private IRequest $request;
	private IClassMap $classmap;

	public function __construct(IRequest $request, IClassMap $classmap) {
		$this->request = $request;
		$this->classmap = $classmap;
	}

	/**
	 * Matches CLI calls only. Returns null for web requests.
	 */
	public function match(string $path): ?array {
		if (PHP_SAPI !== 'cli') {
			return null;
		}

		$name = $this->request->get('name', '');
		if ($name === '') {
			return null;
		}

		return [
			'name' => $name,
			'out'  => $this->request->get('out', 'html'),
			'data' => $this->request->get('data', '')
		];
	}

	/**
	 * Dispatches the CLI request directly to the output instance.
	 */
	public function dispatch(array $match): string {
		$_GET['name'] = $_REQUEST['name'] = $match['name'];
		if (!empty($match['data'])) {
			$_GET['data'] = $_REQUEST['data'] = $match['data'];
		}
		if (!empty($match['out'])) {
			$_GET['out'] = $_REQUEST['out'] = $match['out'];
		}

		$instance = $this->classmap->getInstanceByInterfaceName(IOutput::class, $match['name']);
		if (!is_object($instance)) {
			return "404 Not Found\n";
		}

		if ($match['out'] === 'json') {
			header('Content-Type: application/json');
		} elseif ($match['out'] === 'html') {
			header('Content-Type: text/html; charset=utf-8');
		}

		return (string)$instance->getOutput($match['out']);
	}
}

