<?php declare(strict_types=1);

namespace Base3\Core;

use Base3\Api\ICheck;
use Base3\Api\IClassMap;
use Base3\Api\IContainer;

abstract class AbstractClassMap implements IClassMap, ICheck {

	protected IContainer $container;
	protected string $filename;
	protected ?array $map = null;

	public function __construct(IContainer $container) {
		$this->container = $container;
		$this->filename = DIR_TMP . 'classmap.php';
	}

	abstract protected function getScanTargets(): array;

	protected function &getMap(): array {
		if (!isset($this->map)) {
			if (!file_exists($this->filename) || filesize($this->filename) === 0) {
				$this->generate(true);
			}
			$this->map = require $this->filename;
		}
		return $this->map;
	}

	public function generate($regenerate = false): void {
		if (!$regenerate && file_exists($this->filename) && filesize($this->filename) > 0) return;

		if (!is_writable(DIR_TMP)) die('Directory /tmp has to be writable.');

		$this->map = [];

		if (method_exists($this, 'generateFromComposerClassMap')) {
			$this->generateFromComposerClassMap();
			$this->writeClassMap();
			return;
		}

		foreach ($this->getScanTargets() as $target) {
			$basedir = $target['basedir'];
			$subdir = $target['subdir'] ?? '';
			$subns = $target['subns'] ?? '';

			$apps = isset($target['app'])
				? [$target['app']]
				: $this->getEntries($basedir);

			foreach ($apps as $app) {
				$apppath = $basedir . DIRECTORY_SEPARATOR . $app;
				if (!empty($subdir)) $apppath .= DIRECTORY_SEPARATOR . $subdir;
				if (!is_dir($apppath)) continue;

				$classes = [];
				$this->scanClasses($classes, $basedir, $app, $subdir, $subns);
				$this->fillClassMap($app, $classes);
			}
		}

		$this->writeClassMap();
	}

	protected function scanClasses(&$classes, $basedir, $app, $subdir = "", $subns = "", $path = ""): void {
		$fullpath = $basedir . DIRECTORY_SEPARATOR . $app;
		if (!empty($subdir)) $fullpath .= DIRECTORY_SEPARATOR . $subdir;
		if (!empty($path)) $fullpath .= DIRECTORY_SEPARATOR . $path;

		foreach ($this->getEntries($fullpath) as $entry) {
			$fullentry = $fullpath . DIRECTORY_SEPARATOR . $entry;

			if (is_dir($fullentry)) {
				$this->scanClasses($classes, $basedir, $app, $subdir, $subns, $path . DIRECTORY_SEPARATOR . $entry);
				continue;
			}

			if (substr($entry, -4) !== ".php" || substr_count($entry, ".") !== 1) continue;
			if (basename($fullentry) === 'Autoloader.php' && str_contains($fullentry, 'Base3Framework')) continue;

			require_once($fullentry);

			$nsparts = !empty($subns)
				? explode("\\", $subns)
				: explode(DIRECTORY_SEPARATOR, $app);

			$appParts = explode("/", $app);
			$lastAppPart = end($appParts);
			if (!in_array($lastAppPart, $nsparts)) $nsparts[] = $lastAppPart;

			foreach (explode(DIRECTORY_SEPARATOR, $path) as $pp)
				if (!empty($pp)) $nsparts[] = $pp;

			$namespace = implode("\\", $nsparts);
			$classname = $namespace . "\\" . substr($entry, 0, -4);

			if (!class_exists($classname, false)) continue;

			$rc = new \ReflectionClass($classname);
			if ($rc->isAbstract()) continue;

			$interfaces = class_implements($classname);

			$classes[] = [
				"file" => $fullentry,
				"class" => $classname,
				"interfaces" => $interfaces
			];
		}
	}

	public function getApps() {
		return array_keys($this->getMap());
	}

	public function getPlugins() {
		return [];
	}

	public function &getInstances(array $criteria = []) {
		$instances = [];

		$app = $criteria['app'] ?? null;
		$interface = $criteria['interface'] ?? null;
		$name = $criteria['name'] ?? null;

		// app + interface + name
		if ($app && $interface && $name) {
			$inst = $this->getInstanceByAppInterfaceName($app, $interface, $name);
			if ($inst) $instances[] = $inst;
			return $instances;
		}

		// app + interface
		if ($app && $interface) {
			$instances = $this->getInstancesByAppInterface($app, $interface);
			return $instances;
		}

		// app + name
		if ($app && $name) {
			$inst = $this->getInstanceByAppName($app, $name);
			if ($inst) $instances[] = $inst;
			return $instances;
		}

		// interface + name
		if ($interface && $name) {
			$inst = $this->getInstanceByInterfaceName($interface, $name);
			if ($inst) $instances[] = $inst;
			return $instances;
		}

		// interface
		if ($interface) {
			$instances = $this->getInstancesByInterface($interface);
			return $instances;
		}

		// name
		if ($name) {
			$map = $this->getMap();
			foreach ($map as $app => $data) {
				if (!isset($data['name'][$name])) continue;
				$c = $data['name'][$name];
				if (class_exists($c)) $instances[] = $this->instantiate($c);
			}
			return $instances;
		}

		// no criteria: all instances
		$map = $this->getMap();
		foreach ($map as $app => $data) {
			if (!isset($data['name'])) continue;
			foreach ($data['name'] as $c) {
				if (class_exists($c)) $instances[] = $this->instantiate($c);
			}
		}

		return $instances;
	}

	public function &getInstancesByInterface($interface) {
		$instances = [];
		foreach ($this->getMap() as $app => $m) {
			$is = $this->getInstancesByAppInterface($app, $interface, true);
			$instances = array_merge($instances, $is);
		}
		return $instances;
	}

	public function &getInstancesByAppInterface($app, $interface, $retry = false) {
		$map = $this->getMap();
		$instances = [];

		if (isset($map[$app]['interface'][$interface])) {
			foreach ($map[$app]['interface'][$interface] as $c)
				$instances[] = $this->instantiate($c);
			return $instances;
		}

		if ($retry) return $instances;
		$this->generate(true);
		return $this->getInstancesByAppInterface($app, $interface, true);
	}

	public function &getInstanceByAppName($app, $name, $retry = false) {
		$map = $this->getMap();
		$instance = null;

		if (isset($map[$app]['name'][$name])) {
			$c = $map[$app]['name'][$name];
			if (class_exists($c)) {
				$instance = $this->instantiate($c);
				return $instance;
			}
		}

		if ($retry) return $instance;
		$this->generate(true);
		return $this->getInstanceByAppName($app, $name, true);
	}

	public function &getInstanceByInterfaceName($interface, $name, $retry = false) {
		$map = $this->getMap();
		$instance = null;

		foreach ($map as $appdata) {
			if (!isset($appdata["name"])) continue;
			foreach ($appdata["name"] as $n => $c) {
				if ($n != $name || !class_exists($c)) continue;
				if (!in_array($interface, class_implements($c))) continue;
				$instance = $this->instantiate($c);
				return $instance;
			}
		}

		if ($retry) return $instance;
		$this->generate(true);
		return $this->getInstanceByInterfaceName($interface, $name, true);
	}

	public function &getInstanceByAppInterfaceName($app, $interface, $name, $retry = false) {
		if (!strlen($app)) return $this->getInstanceByInterfaceName($interface, $name);

		$map = $this->getMap();
		$instance = null;

		if (isset($map[$app]['name'][$name], $map[$app]['interface'][$interface])) {
			$c = $map[$app]['name'][$name];
			if (!in_array($c, $map[$app]['interface'][$interface])) return null;
			if (class_exists($c)) {
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
			if ($refClass->isAbstract()) return null;

			$constructor = $refClass->getConstructor();
			if (!$constructor) return new $class();

			$params = [];

			foreach ($constructor->getParameters() as $param) {
				$type = $param->getType();
				$paramName = $param->getName();

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
						if ($param->isDefaultValueAvailable()) $params[] = $param->getDefaultValue();
						else return null;
					}
					continue;
				}

				if ($type instanceof \ReflectionNamedType) {
					if ($type->allowsNull() && $param->isDefaultValueAvailable()) {
						$params[] = null;
						continue;
					}

					if (!$type->isBuiltin()) {
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

					if ($type->isBuiltin()) {
						if ($this->container->has($paramName)) {
							$params[] = $this->container->get($paramName);
						} elseif ($param->isDefaultValueAvailable()) {
							$params[] = $param->getDefaultValue();
						} else {
							return null;
						}
						continue;
					}
				}

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
		}
	}

	public function checkDependencies() {
		$this->getMap(); // Trigger loading
		return [
			'classmap_writable' => is_writable($this->filename ?? '') ? 'Ok' : ($this->filename ?? 'undefined') . ' not writable'
		];
	}

	protected function getEntries($path): array {
		$path = rtrim($path, DIRECTORY_SEPARATOR);
		$entries = [];
		$handle = opendir($path);
		while ($entry = readdir($handle)) {
			if ($entry[0] === '.' || $entry[0] === '_') continue;
			$entries[] = $entry;
		}
		closedir($handle);
		return $entries;
	}

	protected function fillClassMap(string $app, array $classes): void {
		foreach ($classes as $c) {
			foreach ($c['interfaces'] as $interface) {
				$this->map[$app]['interface'][$interface][] = $c['class'];
			}

			if (!in_array(\Base3\Api\IBase::class, $c['interfaces'])) continue;
			if (!is_callable([$c['class'], 'getName'])) continue;

			try {
				$name = $c['class']::getName();
				$this->map[$app]['name'][$name] = $c['class'];
			} catch (\Throwable $e) {
				continue;
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

