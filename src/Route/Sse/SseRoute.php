<?php declare(strict_types=1);

namespace Base3\Route\Sse;

use Base3\Route\Api\IRoute;
use Base3\Api\IClassMap;
use Base3\Api\IStream;

/**
 * Generic SSE route.
 * Matches /sse/<name> and dispatches to the corresponding IStream service.
 */
class SseRoute implements IRoute {

	private IClassMap $classmap;

	public function __construct(IClassMap $classmap) {
		$this->classmap = $classmap;
	}

	/**
	 * Matches: /sse/<name>
	 *
	 * @param string $path
	 * @return array|null
	 */
	public function match(string $path): ?array {
		$path = explode('?', $path, 2)[0];
		$path = trim($path, '/');

		if (!str_starts_with($path, 'sse/')) {
			return null;
		}

		$name = substr($path, 4);
		if ($name === '') {
			return null;
		}

		// Ensure matching service exists and implements IStream
		$instance = $this->classmap->getInstanceByInterfaceName(IStream::class, $name);
		if (!$instance instanceof IStream) {
			return null;
		}

		return [
			'name' => $name
		];
	}

	/**
	 * Dispatches to the streaming instance.
	 *
	 * @param array $match
	 * @return string (always empty)
	 */
	public function dispatch(array $match): string {
		$name = $match['name'];

		/** @var IStream|null $instance */
		$instance = $this->classmap->getInstanceByInterfaceName(IStream::class, $name);
		if (!$instance instanceof IStream) {
			header('HTTP/1.1 404 Not Found');
			echo "Stream '$name' not found.";
			return '';
		}

		// No output buffering issues — stream handles its own headers
		$instance->stream();

		// SSE expects no return body after stream()
		return '';
	}
}
