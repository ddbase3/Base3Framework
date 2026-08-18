# BASE3 Knowledge Service

## Purpose

This document explains the lightweight structured knowledge abstraction in BASE3.

It covers:

* the `IKnowledge` contract
* scopes, fields, and data
* the built-in `KnowledgeSource`
* the `DevStatusMicroservice`
* current return-shape semantics
* when to use a foundation or plugin-specific data service instead

---

## 1. `IKnowledge`

The framework contract is:

```php
namespace Base3\Knowledge\Api;

interface IKnowledge {

	public function getScopes();

	public function getFields($scope);

	public function getData($scope, $fields = null);
}
```

It models a small structured knowledge source with three questions:

```text
Which scopes exist?
Which fields exist in one scope?
What data exists for that scope and selected fields?
```

---

## 2. Scope

A scope is a stable logical dataset identifier.

The built-in `KnowledgeSource` currently exposes:

```text
continent
country
language
```

Other `IKnowledge` implementations may expose different scopes.

Consumers should discover scopes instead of assuming the built-in list when they are written against the generic interface.

---

## 3. Fields

```php
$fields = $knowledge->getFields('country');
```

The meaning of the returned array is implementation-defined by the broad legacy contract.

The built-in source returns field identifiers for each supported scope.

Example country fields include:

```text
id
name_native
name_en
continent_id
language_ids
phone
capital_en
currency
```

---

## 4. Data lookup

```php
$rows = $knowledge->getData('country', [
	'id',
	'name_en',
	'currency'
]);
```

The selected fields are used to project the returned data.

The interface intentionally keeps return types broad for compatibility.

A consumer that requires a stronger typed data model should define that model in the appropriate plugin or foundation contract instead of guessing shapes around `IKnowledge`.

---

## 5. `KnowledgeSource`

The built-in source reads JSON data located next to the class under its `Data` directory.

It provides reference data for:

* continents
* countries
* languages

The source can derive additional data such as language-to-country relationships by combining the local JSON datasets.

---

## 6. Built-in field semantics

### `continent`

Fields:

```text
id
name_de
name_en
```

### `country`

Fields:

```text
id
name_native
name_en
continent_id
language_ids
phone
capital_en
currency
```

### `language`

Fields:

```text
id
name_native
name_en
rtl
country_ids
```

These fields belong to the built-in `KnowledgeSource`. They are not a universal schema imposed on every `IKnowledge` implementation.

---

## 7. Current no-field behavior

`KnowledgeSource` has legacy behavior when `getData()` is called without a field list.

For a known scope, it returns keys from the underlying dataset rather than the same fully projected row shape used when fields are supplied.

Consumers that need predictable projections should explicitly request the fields they need.

---

## 8. `DevStatusMicroservice`

The framework also contains:

```php
Base3\Knowledge\DevStatusMicroservice
```

It extends `AbstractMicroservice` and implements `IKnowledge`.

It reads:

```text
DIR_LOCAL/devstatus.json
```

and exposes the top-level keys as scopes.

For each scope:

* field names are inferred from the first row
* `getData()` can return all rows or a selected field projection

Because it extends `AbstractMicroservice`, these methods can also be exposed through the BASE3 microservice call mechanism.

---

## 9. Knowledge versus ResourceFoundation

`IKnowledge` is a small legacy/general framework abstraction.

For entity data, storage, schema discovery, query execution, and richer resource metadata, use the contracts in `ResourceFoundation` where applicable.

Do not expand `IKnowledge` into a parallel general-purpose resource architecture.

---

## 10. Knowledge versus Settings Store

`IKnowledge` exposes data for reading/querying.

`ISettingsStore` stores named configuration-like datasets that users or administrators may edit.

The two concepts should stay separate.

---

## 11. Knowledge versus Configuration

Reference or domain data should not be forced into `IConfiguration` merely because it can be represented as arrays.

Configuration describes runtime/project choices.

Knowledge data describes information a consumer wants to read.

---

## 12. Dependency injection

If a plugin deliberately depends on the framework knowledge abstraction, inject the interface:

```php
public function __construct(
	private readonly IKnowledge $knowledge
) {}
```

The project chooses the concrete source.

---

## 13. Microservice exposure

`DevStatusMicroservice` demonstrates that a service contract can also be exposed remotely by implementing it on an `AbstractMicroservice` endpoint.

That pattern does not change the local contract.

See `microservices.md` for the transport behavior.

---

## 14. Summary

```text
IKnowledge
  scopes, fields, and data

KnowledgeSource
  built-in continent/country/language reference data

DevStatusMicroservice
  local devstatus.json exposed through IKnowledge and microservice transport
```

Use this abstraction for its existing lightweight purpose and prefer richer foundation contracts when a domain needs typed resource/query semantics.
