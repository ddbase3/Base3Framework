<?php declare(strict_types=1);

namespace Base3\Core;

use Base3\Api\IContainer;

/**
 * Dependency Injector.
 * Hier werden alle von der Anwendung global verfügbare Dienste hinterlegt.
 */
class ServiceLocator implements IContainer, \ArrayAccess {

	private static $instance;
	private static $externalInstance = null;

	private $container = array();
	private $aliases = array();
	private $parameters = array();

	// Implementation of ArrayAccess

	public function offsetExists($offset): bool {
		return $this->has($offset);
	}

	public function offsetGet($offset): mixed {
		return $this->get($offset);
	}

	public function offsetSet($offset, $value): void {
		throw new \LogicException('Setting services via array access is not supported.');
	}

	public function offsetUnset($offset): void {
		throw new \LogicException('Unsetting services via array access is not supported.');
	}

	// Implementation of IContainer

	public static function useInstance(self $instance) {
		self::$externalInstance = $instance;
	}

	public static function getInstance(): self {
		if (self::$externalInstance !== null) return self::$externalInstance;
		if (self::$instance === null) self::$instance = new self();
		return self::$instance;
	}

	/**
	 * Get list of registered service names
	 */
	public function getServiceList(): array {
		$list = array();
		foreach ($this->container as $name => $_) $list[] = $name;
		foreach ($this->aliases as $name => $_) $list[] = $name;
		foreach ($this->parameters as $name => $_) $list[] = $name;
		return $list;
	}

	/**
	 * Einen Service hinterlegen.
	 * @param string                       $name            Name des Service.
	 * @param string|object|callable|array $classDefinition Definition wie die Instanz zu erstellen ist. Kann ein Klassenname oder eine Funktion sein. Auch Array möglich (Instanzen werden nicht erzeugt).
	 * @param bool                         $shared          Gibt es nur eine Instanz für alle, oder bekommt jeder eine eigene.// DEPRECATED
	 * @param int $flags Einstellungen gem. Konstanten
	 */
	public function set(string $name, $classDefinition, $flags = 0): IContainer {

		$shared = false;
		$nooverwrite = false;
		$alias = false;
		$parameter = false;

		if (is_bool($flags)) {
			// DEPRECATED
			$shared = $flags;
		} else {
			$shared = ($flags & self::SHARED) != 0;
			$nooverwrite = ($flags & self::NOOVERWRITE) != 0;
			$alias = ($flags & self::ALIAS) != 0;
			$parameter = ($flags & self::PARAMETER) != 0;
		}

		if ($nooverwrite && $this->has($name)) return $this;

		// Sicherstellen, dass der Service in keinem der Container existiert
		$this->remove($name);

		if ($parameter) {
			$this->parameters[$name] = $classDefinition;
			return $this;
		}

		if ($alias) {
			// $classDefinition ist in diesem Fall der Ziel-Service (string)
			if (!$this->has($classDefinition)) throw new \RuntimeException("Cannot create alias: Target service '$classDefinition' not found.");
			$this->aliases[$name] = $classDefinition;
			return $this;
		}

		$this->container[$name] = (object) array('def' => $classDefinition, 'shared' => $shared, 'instance' => null);

		return $this;
	}

	/**
	 * Entfernt einen Service
	 */
	public function remove(string $name) {
		if (array_key_exists($name, $this->container)) unset($this->container[$name]);
		if (array_key_exists($name, $this->aliases)) unset($this->aliases[$name]);
		if (array_key_exists($name, $this->parameters)) unset($this->parameters[$name]);
	}

	/**
	 * Prüft, ob ein Name vergeben ist für einen Service
	 * @return bool
	 */
	public function has(string $name): bool {
		return array_key_exists($name, $this->container)
			|| array_key_exists($name, $this->aliases)
			|| array_key_exists($name, $this->parameters);
	}

	/**
	 * Einen Service abrufen.
	 * @param string $name
	 * @return object
	 */
	public function get(string $name) {
		if (isset($this->aliases[$name])) $name = $this->aliases[$name];

		if (isset($this->parameters[$name])) return $this->parameters[$name];

		if (!isset($this->container[$name])) {
			return null;
			// because classmap needs to instatiate classes without errors to get names
			// throw new \RuntimeException('Requested service "' . $name . '" not defined.');
		}
 
		$service = $this->container[$name];

		if (is_null($service->def) || is_scalar($service->def) || is_array($service->def)) {
			// Null/numeric/string/Array direkt durchgeben, keine Instanzen erzeugen
			return $service->def;
		}

		if (!$service->shared) {
			// Wird nicht gemeinsam verwendet, also immer eine neue Instanz erstellen.
			return $this->createInstance($service->def, false);
		}
 
		if ($service->instance === null) {
			// Service wurde bisher noch nicht angefordert, also neue Instanz erstellen.
			$service->instance = $this->createInstance($service->def, true);
		}
 
		return $service->instance;
	}

	/**
	 * Erstellt eine Instanz der Klasse.
	 * @param string|object|callable $definition
	 * @param bool                   $shared
	 * @return object
	 */
	private function createInstance($definition, bool $shared) {

		if (is_callable($definition)) {
			$ref = new \ReflectionFunction($definition);
			if ($ref->getNumberOfParameters() > 0) return $definition($this);
			return $definition();
		}

		if (is_string($definition)) {
			return new $definition;
		}
 
		if (is_object($definition)) {
			if ($shared) return $definition;
			$class = get_class($definition);
			return new $class;
		}

		throw new \RuntimeException('Malformed service definition!');
	}
}


/*


// examples 1 (create):

$di = DI::getInstance();

// Hinterlegen einer Instanz der Klasse; Service wird gemeinsam verwendet
$di->set('memcache', new \Project\Library\MyMemcache(), true);

// Hinterlegen eines Klassennamens. Nützlich, wenn der Konstruktor keine Argumente benötigt und die Klasse nicht oft verwendet wird.
$di->set('cache', '\Project\Library\MyXCache');

// Defintion mit einer Funktion. Nützlich, wenn die Instanzierung teuer oder komplizierter ist.
// Da in der gesamten Anwendung die selbe Datenbankverbindung genutzt werden soll, setzten wir shared auf true.
$di->set('db', function () {
	return new \PDO('mysql:dbname=testdb;host=127.0.0.1', 'root', '123');
}, true);


// example 2 (use):

class IndexController {
	public function indexAction() {
		$cache = DI::getInstance()->get('cache');
		// ...
	}
}


*/
