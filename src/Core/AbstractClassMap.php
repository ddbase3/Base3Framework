<?php declare(strict_types=1);

namespace Base3\Core;

use Base3\Api\IContainer;

abstract class AbstractClassMap {

	protected $container;

	protected $filename;
	protected $map;

	public function __construct(IContainer $container) {
		$this->container = $container;
		$this->filename = DIR_TMP . 'classmap.php';
		$this->generate();
		$this->map = require $this->filename;
	}

	abstract public function generate($regenerate = false);
	abstract public function getPlugins();

	public function getApps() {
		return array_keys($this->map);
	}

	protected function instantiate(string $class) {
		$refClass = new \ReflectionClass($class);

		// Kein Konstruktor? Einfach instanziieren
		if (!$refClass->getConstructor()) return new $class();

		$params = [];
		foreach ($refClass->getConstructor()->getParameters() as $param) {
			$type = $param->getType();
			if (!$type || !$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
				throw new \RuntimeException("Cannot resolve constructor param \${$param->getName()} in $class");
			}

			$dep = $type->getName();

			if (!$this->container->has($dep)) {
				throw new \RuntimeException("Dependency $dep not found in container for class $class");
			}

			$params[] = $this->container->get($dep);
		}

		return $refClass->newInstanceArgs($params);
	}
}
