# BASE3 Language and Translation

## Purpose

This document explains the two related but separate language mechanisms in BASE3:

* `ILanguage` for selecting the active language
* `ITranslation` for resolving translated text

It also explains how these systems relate to MVC language bricks and language-aware routing.

---

## 1. Three related concepts

BASE3 currently has three language-related mechanisms:

```text
ILanguage
  selects and reports the active language code

ITranslation
  translates a key into text

IMvcView language bricks
  loads INI files for a template-oriented translation set
```

They solve different problems and should not be treated as interchangeable.

---

## 2. `ILanguage`

The central language-selection contract is:

```php
namespace Base3\Language\Api;

interface ILanguage {

	public function getLanguage(): string;

	public function setLanguage(string $language);

	public function getLanguages(): array;
}
```

It answers:

```text
Which language is active?
Which languages are available?
May the active language be changed?
```

It does not translate text itself.

---

## 3. `SingleLang`

`SingleLang` represents a runtime with exactly one configured language.

It reads:

```text
configuration group: language
key: main
```

If no value exists, the current implementation defaults to:

```text
en
```

`setLanguage()` intentionally does nothing.

`getLanguages()` returns an array containing only the active language.

Use this when a project does not need runtime language switching.

---

## 4. `MultiLang`

`MultiLang` supports runtime language switching.

It depends on:

```text
IConfiguration
ISession
```

The `language` configuration group is expected to contain values such as:

```ini
[language]
main = "de"
languages[] = "de"
languages[] = "en"
```

The exact configuration backend may differ, but the logical values are the same.

### Active language lookup

The current implementation uses this order:

```text
1. language already cached in the object
2. session value "language"
3. configured language/main
4. fallback "de"
```

### Changing the language

`setLanguage()` only accepts a value present in `getLanguages()`.

When accepted, the current implementation stores the selected code in the session.

---

## 5. Language-aware routing

`LangBasedServiceSelector` interprets the routing `data` value as a language code when its length is two characters.

Conceptually:

```text
de/navigation.html
  -> data=de
  -> ILanguage.setLanguage("de")
  -> navigation output
```

The path-based `GenericOutputRoute` also supports a two-letter language segment and forwards it to its configured language object.

For the complete routing model, see `routing.md`.

---

## 6. `ITranslation`

Translation is a separate service contract:

```php
namespace Base3\Translation\Api;

interface ITranslation {

	public function translate(
		string $set,
		string $section,
		string $key,
		string $fallback = '',
		array $replacements = []
	): string;
}
```

The arguments have explicit meanings:

```text
set
  translation set, commonly corresponding to lang/<set>/<language>.ini

section
  section inside the translation set

key
  stable translation key

fallback
  text used when no translation is available

replacements
  placeholder substitutions
```

---

## 7. `NoTranslation`

The framework currently ships a safe fallback implementation:

```php
Base3\Translation\NoTranslation\NoTranslation
```

It does not load language files.

Instead it returns:

```text
fallback, when fallback is non-empty
otherwise key
```

It then applies replacements.

Example:

```php
$text = $translation->translate(
	'Administration',
	'administration',
	'greeting',
	'Hello {name}',
	['name' => 'Ada']
);
```

With `NoTranslation` the result is:

```text
Hello Ada
```

This implementation allows code to depend on `ITranslation` even when no actual translation backend is installed.

---

## 8. Replacement syntax

`NoTranslation` supports both direct replacement keys and brace-wrapped placeholders.

For:

```php
['name' => 'Ada']
```

it prepares replacements for both:

```text
name
{name}
```

A concrete translation implementation should preserve the public `ITranslation` contract even if its internal storage differs.

---

## 9. MVC language bricks

`MvcView` has a separate mechanism:

```php
$view->loadBricks('navigation');
$bricks = $view->getBricks('navigation');
```

The current view resolves files as:

```text
{view path}/lang/{set}/{language}.ini
```

For example:

```text
plugin/ExamplePlugin/lang/navigation/de.ini
```

This mechanism is template-oriented and predates the generic `ITranslation` service.

It remains valid for MVC templates that are already structured around language bricks.

See `mvc.md`.

---

## 10. Which mechanism should new code use?

Use `ILanguage` when the code needs to know or change the active language.

Use `ITranslation` when runtime code needs translated text through a replaceable service contract.

Use MVC bricks when working in an existing MVC display/template that already uses the brick convention.

Do not create a second language state mechanism inside a plugin.

---

## 11. Stable translation keys

Technical translation keys should be stable and should not contain user-facing text as identity.

Good:

```text
provider_title
save_button
connection_test_failed
```

Avoid using the current English label itself as the only technical identifier when the surrounding subsystem expects stable keys.

---

## 12. Dependency injection

Consumers should depend on the relevant interface:

```php
final class ExampleLabelService {

	public function __construct(
		private readonly ILanguage $language,
		private readonly ITranslation $translation
	) {}
}
```

The project chooses the concrete language and translation implementations.

---

## 13. Sessions and language

`MultiLang` persists the selected language in session state.

Therefore a project using it should ensure its selected `ISession` is active before relying on language persistence.

If the project is intentionally sessionless, `SingleLang` or a host-specific `ILanguage` implementation is usually more appropriate.

---

## 14. Embedded systems

An embedded BASE3 installation may map host-system language state and translation APIs into the BASE3 contracts.

Reusable plugins should therefore avoid direct assumptions about:

* a specific session key
* a particular INI file location outside MVC bricks
* one hardcoded list of languages
* one host translation API

Depend on `ILanguage` and `ITranslation` where replacement is expected.

---

## 15. Summary

```text
ILanguage
  active language state

SingleLang
  one configured language

MultiLang
  session-backed runtime language selection

ITranslation
  key-based translation service

NoTranslation
  safe fallback using fallback text or key

MvcView bricks
  template-oriented INI language loading
```

Language selection and translation are related, but they remain separate contracts.
