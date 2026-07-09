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

namespace Base3\Core;

use Base3\Api\IClassMap;
use Base3\Api\IComponent;
use Base3\Api\IComponentResolver;
use Base3\Api\IContainer;

/**
 * ComponentResolver
 *
 * Resolves configured component instances without becoming a second container.
 * Component definitions remain container parameters; implementations remain classmap-discovered classes.
 */
final class ComponentResolver implements IComponentResolver {

	public function __construct(
		private readonly IContainer $container,
		private readonly IClassMap $classMap,
	) {}

	public function has(string $interfaceName, string $id): bool {
		return $this->findDefinition($interfaceName, $id) !== null;
	}

	public function get(string $interfaceName, string $id): ?IComponent {
		$definition = $this->findDefinition($interfaceName, $id);
		if (!$definition) return null;

		$class = $this->classMap->getClassByInterfaceName(
			$definition->interfaceName,
			$definition->implementationName
		);

		if (!$class) return null;

		$arguments = $definition->arguments + [
			ComponentDefinition::class => $definition,
			'definition' => $definition,
			'config' => $definition->config,
		];

		$component = $this->classMap->instantiateWith($class, $arguments);
		if (!$component instanceof IComponent) return null;

		return $component;
	}

	public function all(string $interfaceName): iterable {
		foreach ($this->getDefinitions() as $definition) {
			if ($definition->interfaceName !== $interfaceName) continue;

			$component = $this->get($interfaceName, $definition->id);
			if ($component) yield $component;
		}
	}

	private function findDefinition(string $interfaceName, string $id): ?ComponentDefinition {
		foreach ($this->getDefinitions() as $definition) {
			if ($definition->interfaceName !== $interfaceName) continue;
			if ($definition->id !== $id) continue;

			return $definition;
		}

		return null;
	}

	/**
	 * @return iterable<ComponentDefinition>
	 */
	private function getDefinitions(): iterable {
		foreach ($this->container->getServiceList() as $serviceName) {
			if (!str_starts_with($serviceName, ComponentDefinition::SERVICE_PREFIX)) continue;

			$definition = $this->container->get($serviceName);
			if (!$definition instanceof ComponentDefinition) continue;

			yield $definition;
		}
	}
}
