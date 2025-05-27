<?php declare(strict_types=1);

namespace Base3\Core;

use Base3\Api\IClassMap;
use Base3\Api\IContainer;

abstract class AbstractClassMap implements IClassMap {

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

	public function getApps() {
		return array_keys($this->map);
	}

	public function getPlugins() {
		return [];
	}

	public function &getInstancesByInterface($interface) {
		$instances = array();
		foreach ($this->map as $app => $m) {
			$is = $this->getInstancesByAppInterface($app, $interface, true);
			$instances = array_merge($instances, $is);
		}
		return $instances;
	}

	public function &getInstancesByAppInterface($app, $interface, $retry = false) {
		$instances = array();
		if (isset($this->map[$app]) && isset($this->map[$app]["interface"][$interface])) {
			$cs = $this->map[$app]["interface"][$interface];
			foreach ($cs as $c) $instances[] = $this->instantiate($c);
			return $instances;
		}

		if ($retry) return $instances;
		$this->generate(true);
		return $this->getInstancesByAppInterface($app, $interface, true);
	}

	public function &getInstanceByAppName($app, $name, $retry = false) {
		$instance = null;
		if (isset($this->map[$app]) && isset($this->map[$app]["name"][$name])) {
			$c = $this->map[$app]["name"][$name];
			if (class_exists($c)) {  // alternatively regenerate classmap
				$instance = $this->instantiate($c);
				return $instance;
			}
		}

		if ($retry) return $instance;
		$this->generate(true);
		return $this->getInstanceByAppName($app, $name, true);
	}

	public function &getInstanceByInterfaceName($interface, $name, $retry = false) {
		$instance = null;
		if (is_array($this->map)) {
			foreach ($this->map as $appdata) {
				if (!isset($appdata["name"])) continue;
				foreach ($appdata["name"] as $n => $c) {
					if ($n != $name || !class_exists($c)) continue;
					// TODO check if class implements given interface
					$instance = $this->instantiate($c);
					return $instance;
				}
			}
		}

		if ($retry) return $instance;
		$this->generate(true);
		return $this->getInstanceByInterfaceName($interface, $name, true);
	}

	public function &getInstanceByAppInterfaceName($app, $interface, $name, $retry = false) {
		if (!strlen($app)) return $this->getInstanceByInterfaceName($interface, $name);

		$instance = null;
		if (is_array($this->map) && isset($this->map[$app]) && isset($this->map[$app]["name"][$name]) && isset($this->map[$app]["interface"][$interface])) {
			$c = $this->map[$app]["name"][$name];
			if (!in_array($c, $this->map[$app]["interface"][$interface])) return null;
			if (class_exists($c)) {  // alternatively regenerate classmap
				$instance = $this->instantiate($c);
				return $instance;
			}
		}

		if ($retry) return $instance;
		$this->generate(true);
		return $this->getInstanceByAppInterfaceName($app, $interface, $name, true);
	}

public function instantiate(string $class) {
    try {
        $refClass = new \ReflectionClass($class);

        // nur konkrete Klassen
        if ($refClass->isAbstract()) return null;

        // Kein Konstruktor? Einfach instanziieren
        $constructor = $refClass->getConstructor();
        if (!$constructor) return new $class();

        $params = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            $paramName = $param->getName();

            if (!$type || !$type instanceof \ReflectionNamedType) {
                // z. B. union type, intersection oder mixed
                if ($param->isDefaultValueAvailable()) {
                    $params[] = $param->getDefaultValue();
                    continue;
                }

                // sonst: überspringen
                return null;
            }

            if ($type->isBuiltin()) {
                if ($this->container->has($paramName)) {
                    $params[] = $this->container->get($paramName);
                    continue;
                } elseif ($param->isDefaultValueAvailable()) {
                    $params[] = $param->getDefaultValue();
                    continue;
                } else {
                    return null;
                }
            }

            $dep = $type->getName();

            if ($this->container->has($dep)) {
                $value = $this->container->get($dep);
            } elseif ($this->container->has($paramName)) {
                $value = $this->container->get($paramName);
            } else {
                $mock = \Base3\Core\DynamicMockFactory::createMock($dep);
                if ($mock === null) return null;
                $value = $mock;
            }

            if ($value instanceof \Closure) {
                $value = $value();
            }

            $params[] = $value;
        }

        return $refClass->newInstanceArgs($params);
    } catch (\Throwable $e) {
        // Problem bei Reflection, Konstruktor-Auflösung oder Instanziierung
        return null;
    }
}

	// Private methods

	protected function getEntries($path) {
		$path = rtrim($path, DIRECTORY_SEPARATOR);
		$entries = array();
		$handle = opendir($path);
		while ($entry = readdir($handle)) {
			if ($entry == "." || $entry == "..") continue;
			if (substr($entry, 0, 1) == "_") continue;
			if (substr($entry, 0, 1) == ".") continue;
			$entries[] = $entry;
		}
		closedir($handle);
		return $entries;
	}
}
