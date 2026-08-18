# BASE3 Core Contracts

## Purpose

This document collects the small framework-wide contracts that are used across multiple subsystems but do not require a large standalone subsystem document.

It covers:

* `IBase`
* `IOutput`
* `IHelp`
* `IDisplay`
* `ISchemaProvider`
* `IOutputSchemaProvider`
* `IComparable`
* `ISortable`
* `IConversation`
* supporting core helpers related to those contracts

Subsystem-specific contracts remain documented in their own guides.

---

## 1. `IBase`

`IBase` defines one static technical identifier:

```php
interface IBase {

	public static function getName(): string;
}
```

This name is used throughout BASE3 for discovery and selection.

Typical discoverable components include:

```text
IOutput
IPlugin
IAuthentication
IJob
IJobExecutionPolicy
IConfigValueModeResolver
IDatabaseMigrationProvider
```

### Naming rule

Use stable lowercase technical names.

Good:

```php
public static function getName(): string {
	return 'connectionconfigdisplay';
}
```

Do not use translated labels, spaces, or display captions as the technical identity.

See `classmap.md` and `coding-conventions.md`.

---

## 2. `IOutput`

The current output contract is:

```php
interface IOutput extends IBase {

	public function getOutput(string $out = 'html', bool $final = false): string;
}
```

It represents a routable or reusable output component.

Important:

```text
getHelp() is not part of IOutput
```

Help was deliberately separated into `IHelp`.

---

## 3. Output format

The `$out` argument selects the requested representation.

Common values include:

```text
html
json
xml
csv
txt
page
```

The exact supported formats belong to the concrete output.

The interface does not require every output to implement every format.

---

## 4. Final versus embedded output

The `$final` parameter distinguishes direct routed execution from internal/embedded rendering.

Conceptually:

```text
final = true
  direct endpoint execution

final = false
  nested/internal rendering
```

The classic `AbstractServiceSelector` currently invokes routed outputs with `final=true`.

Some path-based routes call `getOutput()` without explicitly passing `true`, so consumers should not assume all route implementations currently mark final execution identically.

See `routing.md`.

---

## 5. `IHelp`

Optional help and developer self-description use:

```php
interface IHelp {

	public function getHelp(): string;
}
```

The interface is intentionally separate from `IOutput`.

An output that supports help implements both:

```php
final class ExampleOutput implements IOutput, IHelp {
	// ...
}
```

The classic service selector only exposes help when:

* `out=help`
* `DEBUG` is enabled
* the resolved object implements `IHelp`

---

## 6. `AbstractOutput`

`Base3\Core\AbstractOutput` provides a compatibility convenience base class.

It derives `getName()` from the concrete class name.

It also currently contains a `getHelp()` method even though it only implements `IOutput` and does not declare `IHelp`.

That method is a legacy convenience and does not change the current `IOutput` contract.

New code should implement `IHelp` explicitly when help capability is intended.

---

## 7. `IDisplay`

`IDisplay` is a reusable output/display contract used by UI-oriented components.

It combines output identity/rendering with mutable input data through `setData()`.

Displays commonly render with `IMvcView` and plugin templates.

See `mvc.md` and `extension-cookbook.md`.

---

## 8. `ISchemaProvider`

`ISchemaProvider` provides one structured schema:

```php
interface ISchemaProvider {

	public function getSchema(): array;
}
```

The schema may be used for:

* validation
* UI generation
* documentation
* configuration editors
* code generation

The interface deliberately does not mandate one universal schema dialect.

Consumers must follow the schema conventions of the subsystem that owns the provider.

---

## 9. `IOutputSchemaProvider`

Some components need schemas for multiple named output operations.

The contract is:

```php
interface IOutputSchemaProvider {

	public function getOutputSchemas(): array;
}
```

The returned array is indexed by operation name.

Use this when one component exposes several structured output shapes and one `getSchema()` value would be ambiguous.

Do not replace an existing subsystem-specific schema contract if that subsystem already defines a more precise API.

---

## 10. `IComparable`

`IComparable` defines object-to-object comparison:

```php
interface IComparable {

	public function compareTo($o);
}
```

The expected result follows the usual comparison convention:

```text
-1 smaller
 0 equal
 1 greater
```

`Base3\Core\Comparator::sort()` uses `usort()` and delegates comparisons to `compareTo()`.

---

## 11. `ISortable`

`ISortable` defines an integer priority:

```php
interface ISortable {

	public function getPriority(): int;
}
```

The current API contract states that lower values execute earlier and higher values later.

This contract is useful for extension collections that need deterministic priority ordering.

Do not assume legacy interfaces with an untyped `getPriority()` method automatically implement `ISortable`.

---

## 12. Comparable versus sortable

The concepts are different.

```text
IComparable
  object decides relative order against another object

ISortable
  object exposes one priority value
```

Use the smallest contract that matches the collection's actual ordering model.

---

## 13. `IConversation`

`IConversation` is currently a framework-level interface for AI-style multimodal conversations.

It defines:

```text
chat(messages, context)
raw(messages, context)
getModel()
configure(options)
extractToolCall(response)
isFinalResponse(response)
```

The interface predates the broader `AssistantFoundation` contract set.

### Architectural guidance

For new AI provider, chat model, embedding, vector search, task, or agent integration work, prefer the corresponding `AssistantFoundation` APIs where they cover the use case.

Do not grow `IConversation` into a second parallel AI foundation.

Existing consumers may continue to use the current contract where compatibility requires it.

---

## 14. `IConversation::chat()`

```php
public function chat(array $messages, array $context = []): string;
```

Messages are described by the current interface documentation as role/content structures.

The method returns the textual response.

---

## 15. `IConversation::raw()`

```php
public function raw(array $messages, array $context = []);
```

This exposes the implementation-specific raw provider/model response.

Because the return type is broad, reusable code should avoid assuming one provider-specific structure unless its own integration explicitly requires that provider.

---

## 16. Tool calls and final-response helpers

`extractToolCall()` and `isFinalResponse()` provide compatibility hooks for older conversation engines that handle tool calls and multi-step completion.

New agent/tool orchestration should use `AssistantFoundation` contracts when applicable.

---

## 17. `NullObject`

`Base3\Core\NullObject` accepts unknown method calls through `__call()` and does nothing, optionally printing a debug message.

It is a generic legacy null object, not a replacement for typed no-op implementations such as:

```text
NoStateStore
NoSession
NoAccesscontrol
NoTranslation
NoMigrationRunner
```

Prefer a typed null-object implementation when the framework already defines one for the relevant service contract.

---

## 18. `DynamicMockFactory`

`DynamicMockFactory` is a development/testing helper that creates broad runtime mocks with reflection and generated anonymous classes.

It is useful for discovery/testing support and should not be used to hide missing production service contracts.

Production composition should provide the real interface implementation required by the runtime class.

---

## 19. Summary

The small BASE3 core contracts each have a narrow purpose:

```text
IBase
  stable technical identity

IOutput
  routable/renderable output

IHelp
  optional help capability

IDisplay
  reusable display output with data input

ISchemaProvider
  one schema

IOutputSchemaProvider
  schemas per output operation

IComparable
  pairwise object comparison

ISortable
  priority value

IConversation
  legacy/core AI conversation compatibility contract
```

Use the subsystem-specific contracts when they are more precise, and avoid creating parallel abstractions for capabilities already represented by a foundation.
