# BASE3 Configured Components

## Purpose

This document explains the configured component mechanism in BASE3.

It is written for developers who need more than class discovery but do not want to create a second container.

Typical questions are:

* how can one implementation class be used for multiple configured runtime instances?
* how does `IComponent::id()` differ from `IBase::getName()`?
* where should component definitions be stored?
* how does `ComponentResolver` use the existing container and class map?
* when should a configured component be used instead of a normal service?

---

## 1. The problem

The class map discovers implementation classes.

Example:

```text
RagRetrievalTool::getName() = "rag"
```

That is enough when one discovered implementation creates one normal instance.

It is not enough when one implementation must be used multiple times with different configuration:

```text
internal-rag
  implementation: rag
  vector_db: internal

customer-rag
  implementation: rag
  vector_db: customer
```

Both instances use the same implementation class, but they are different runtime components.

---

## 2. The core distinction

Configured components separate implementation identity from runtime instance identity.

```text
IBase::getName()
  class-level implementation name
  static
  discovered by the class map
  one value per class

IComponent::id()
  runtime instance id
  instance-level
  comes from configuration
  multiple values per class are possible
```

Example:

```text
RagRetrievalTool::getName() = "rag"

$internalRag->id() = "internal-rag"
$customerRag->id() = "customer-rag"
```

---

## 3. Main contracts

A configured component implements `IComponent`.

```php
interface IComponent extends IBase {

	public function id(): string;
}
```

A component definition describes one configured runtime instance.

```php
final class ComponentDefinition {

	public const SERVICE_PREFIX = 'component.definition.';

	public function __construct(
		public readonly string $id,
		public readonly string $interfaceName,
		public readonly string $implementationName,
		public readonly array $arguments = [],
		public readonly array $config = [],
		public readonly array $metadata = [],
	) {}
}
```

A resolver turns definitions into component instances.

```php
interface IComponentResolver {

	public function has(string $interfaceName, string $id): bool;

	public function get(string $interfaceName, string $id): ?IComponent;

	public function all(string $interfaceName): iterable;
}
```

---

## 4. No second container

`ComponentResolver` is deliberately not a second container.

It does not own the service graph.

It does not keep a separate registry of active services.

It does not replace `IContainer`.

The roles stay separate:

```text
Container
  stores known services and parameters
  stores ComponentDefinition values

Class map
  discovers implementation classes
  instantiates them with autowiring
  supports instantiateWith() for constructor overrides

ComponentResolver
  reads ComponentDefinition values from the container
  asks the class map for the implementation class
  asks the class map to instantiate it with definition-specific arguments
```

This keeps the existing BASE3 rule intact:

```text
Container for known shared services.
PluginClassMap for framework and plugin discovery.
```

---

## 5. Register component definitions

Component definitions are registered in the existing container, usually as parameters.

```php
use Base3\Api\IContainer;
use Base3\Core\ComponentDefinition;
use ExamplePlugin\Api\IExampleTool;

$definition = new ComponentDefinition(
	id: 'internal-rag',
	interfaceName: IExampleTool::class,
	implementationName: 'rag',
	config: [
		'vector_db' => 'internal',
	],
	metadata: [
		'toolName' => 'rag_internal_search',
	]
);

$container->set(
	$definition->getServiceName(),
	$definition,
	IContainer::PARAMETER
);
```

A second instance can use the same implementation name:

```php
$definition = new ComponentDefinition(
	id: 'customer-rag',
	interfaceName: IExampleTool::class,
	implementationName: 'rag',
	config: [
		'vector_db' => 'customer',
	],
	metadata: [
		'toolName' => 'rag_customer_search',
	]
);

$container->set(
	$definition->getServiceName(),
	$definition,
	IContainer::PARAMETER
);
```

The service name helper keeps all component definitions under the same prefix:

```text
component.definition.
```

That allows `ComponentResolver` to find only component definitions without scanning unrelated services.

---

## 6. Resolver wiring

The default BASE3 bootstrap registers the resolver as a normal known shared service.

It is registered directly under its interface. No legacy alias entry is needed.

```php
use Base3\Api\IClassMap;
use Base3\Api\IComponentResolver;
use Base3\Api\IContainer;
use Base3\Core\ComponentResolver;

$container->set(
	IComponentResolver::class,
	fn($c) => new ComponentResolver(
		$c->get(IContainer::class),
		$c->get(IClassMap::class)
	),
	IContainer::SHARED
);
```

The resolver can be shared because it does not store component instances. Custom bootstraps should provide the same binding if they do not use the default `Bootstrap`.

---

## 7. Implement a component

A component can receive its definition through constructor injection.

```php
<?php declare(strict_types=1);

namespace ExamplePlugin\Tool;

use Base3\Core\ComponentDefinition;
use ExamplePlugin\Api\IExampleTool;

final class RagTool implements IExampleTool {

	public function __construct(
		private readonly ComponentDefinition $definition,
	) {}

	public static function getName(): string {
		return 'rag';
	}

	public function id(): string {
		return $this->definition->id;
	}

	public function name(): string {
		return $this->definition->metadata['toolName'] ?? $this->definition->id;
	}

	public function search(string $query): array {
		$vectorDb = $this->definition->config['vector_db'] ?? 'default';

		return [
			'component_id' => $this->id(),
			'vector_db' => $vectorDb,
			'query' => $query,
		];
	}
}
```

The implementation name is still class-level:

```php
RagTool::getName() === 'rag'
```

The component id is instance-level:

```php
$tool->id() === 'internal-rag'
```

---

## 8. Resolve configured components

Consumers receive `IComponentResolver` through the constructor.

```php
use Base3\Api\IComponentResolver;
use ExamplePlugin\Api\IExampleTool;

final class ExampleRunner {

	public function __construct(
		private readonly IComponentResolver $components,
	) {}

	public function run(): array {
		$tool = $this->components->get(IExampleTool::class, 'internal-rag');
		if (!$tool instanceof IExampleTool) return [];

		return $tool->search('BASE3 class map');
	}
}
```

All configured components for an interface can be resolved as a group:

```php
foreach ($this->components->all(IExampleTool::class) as $tool) {
	// use $tool
}
```

---

## 9. How `instantiateWith()` fits in

`ComponentResolver` does not construct objects itself.

It delegates construction to the class map:

```text
ComponentDefinition
  -> get implementation class through class map
  -> classMap->instantiateWith(class, arguments)
  -> configured IComponent instance
```

`instantiateWith()` uses the normal class map constructor recipe and container autowiring, but explicit arguments win.

Resolution order:

```text
1. explicit argument by parameter name
2. explicit argument by type name
3. container lookup by type
4. container lookup by parameter name
5. default value
6. null when nullable
7. otherwise not instantiable
```

This is what allows a class to receive a different `ComponentDefinition` for each configured runtime instance.

---

## 10. When to use configured components

Use configured components when these conditions are true:

```text
- the implementation class should be discoverable
- multiple configured runtime instances may exist
- each instance needs a stable id
- construction still benefits from class map autowiring
```

Good examples:

```text
- agent tools
- retrieval tools
- connector instances
- import/export adapters
- configured processors
- configured resource providers
- named skill/module instances
```

Do not use configured components for ordinary known shared services.

For those, use the container directly.

---

## 11. Summary

Configured components add one missing layer between class map discovery and runtime composition:

```text
Class map
  implementation discovery and autowired instantiation

Container
  known services and ComponentDefinition parameters

ComponentResolver
  lightweight resolution from definitions to configured component instances
```

The most important naming rule is:

```text
getName()
  implementation name

id()
  configured runtime instance id
```

This allows one implementation class to safely produce many configured instances without turning the component resolver into a second container.
