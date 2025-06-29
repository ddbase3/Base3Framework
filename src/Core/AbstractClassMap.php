<?php declare(strict_types=1);

namespace Base3\Core;

use Base3\Api\ICheck;
use Base3\Api\IClassMap;
use Base3\Api\IContainer;

abstract class AbstractClassMap implements IClassMap, ICheck {

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
					if (!in_array($interface, class_implements($c))) continue;
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

			// Only instantiate concrete classes
			if ($refClass->isAbstract()) return null;

			// No constructor? Instantiate directly
			$constructor = $refClass->getConstructor();
			if (!$constructor) return new $class();

			$params = [];
			foreach ($constructor->getParameters() as $param) {
				$type = $param->getType();
				$paramName = $param->getName();

				// Handle union types (e.g. FooService|BarService)
				if ($type instanceof \ReflectionUnionType) {
					$resolved = false;
					foreach ($type->getTypes() as $unionType) {
						if (!$unionType instanceof \ReflectionNamedType) continue;
						$dep = $unionType->getName();

						if ($this->container->has($dep)) {
							$value = $this->container->get($dep);
							if ($value instanceof \Closure) $value = $value();
							$params[] = $value;
							$resolved = true;
							break;
						}
					}

					if (!$resolved) {
						if ($param->isDefaultValueAvailable()) {
							$params[] = $param->getDefaultValue();
						} else {
							return null; // No suitable match for the union type
						}
					}

					continue;
				}

				// Handle named class/interface types
				if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
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

					if ($value instanceof \Closure) $value = $value();
					$params[] = $value;
					continue;
				}

				// Handle built-in types (e.g. string, int)
				if ($type instanceof \ReflectionNamedType && $type->isBuiltin()) {
					if ($this->container->has($paramName)) {
						$params[] = $this->container->get($paramName);
					} elseif ($param->isDefaultValueAvailable()) {
						$params[] = $param->getDefaultValue();
					} else {
						return null;
					}

					continue;
				}

				// No type hint or unsupported type
				if ($param->isDefaultValueAvailable()) {
					$params[] = $param->getDefaultValue();
				} else {
					return null;
				}
			}

			return $refClass->newInstanceArgs($params);

		} catch (\Throwable $e) {
			echo $e->getMessage();
			exit;

			// Reflection, constructor resolution, or instantiation failed
			return null;
		}
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			'classmap_writable' => is_writable($this->filename) ? 'Ok' : $this->filename . ' not writable'
		);
	}

	// Private methods

	protected function getEntries($path): array {
		$path = rtrim($path, DIRECTORY_SEPARATOR);
		$entries = array();
		$handle = opendir($path);
		while ($entry = readdir($handle)) {
			$firstChar = substr($entry, 0, 1);
			if ($firstChar == '.' || $firstChar == '_') continue;
			$entries[] = $entry;
		}
		closedir($handle);
		return $entries;
	}

	protected function fillClassMap(string $app, array &$classes): void {
		foreach ($classes as $c) {
			foreach ($c['interfaces'] as $interface) {
				$this->map[$app]['interface'][$interface][] = $c['class'];

				if ($interface !== IBase::class) continue;
				if (!method_exists($c['class'], 'getName')) continue;

				try {
					$name = $c['class']::getName();
				} catch (\Throwable $e) {
					continue;  //ignore failing implementations
				}
				$this->map[$app]['name'][$name] = $c['class'];
			}
		}
	}

	protected function writeClassMap(): void {
                $str = "<?php return ";
                $str .= var_export($this->map, true);
                $str .= ";\n";

                file_put_contents($this->filename, $str);
	}
}
