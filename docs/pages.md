# BASE3 Pages and Page Modules

## Purpose

This document explains the page contracts in BASE3.

It covers:

* `IPage`
* `IPageCatchall`
* `IPagePostDataProcessor`
* the page module interfaces
* module priorities and dependencies
* how pages relate to `IOutput`, routing, MVC, and plugins
* what the framework does and does not prescribe about page composition

---

## 1. Page versus output

A page is an output that also knows its public URL.

```php
interface IPage extends IOutput {

	public function getUrl();
}
```

Because `IPage` extends `IOutput`, a page also has:

```text
static getName()
getOutput(out, final)
```

The page interface adds only:

```text
getUrl()
```

---

## 2. Minimal page

```php
<?php declare(strict_types=1);

namespace ExamplePlugin\Page;

use Base3\Page\Api\IPage;

final class ExamplePage implements IPage {

	public static function getName(): string {
		return 'example';
	}

	public function getUrl() {
		return '/example.html';
	}

	public function getOutput(string $out = 'html', bool $final = false): string {
		return '<h1>Example</h1>';
	}
}
```

Help is optional and belongs to `IHelp`, not to `IPage` or `IOutput`.

---

## 3. `getUrl()`

`getUrl()` returns the public URL associated with the page, or an implementation-specific null-like value when no URL is available.

The current interface does not prescribe one routing style.

A project may use:

* query-based URLs
* pretty names
* host-system routes
* a custom link target service

If a page URL can be generated generically, prefer the project's link-generation abstraction rather than duplicating route syntax in many classes.

See `routing.md`.

---

## 4. `IPageCatchall`

`IPageCatchall` is a marker interface:

```php
interface IPageCatchall extends IPage {}
```

`AbstractServiceSelector` uses it as a fallback when the requested `IOutput` cannot be resolved.

The current selector behavior is:

```text
resolve requested IOutput
  -> if not found, get all IPageCatchall implementations
  -> use the first available instance
  -> if none exists, return 404
```

A project should therefore avoid accidentally providing multiple catch-all pages without defining how their discovery order should be interpreted.

---

## 5. `IPagePostDataProcessor`

A page that owns POST-processing behavior can implement:

```php
interface IPagePostDataProcessor extends IPage {

	public function processPostData();

	public function getForwardUrl();
}
```

The contract separates:

```text
processPostData()
  perform the submission work

getForwardUrl()
  return the redirect/forward target after successful processing
```

The interface does not itself provide a controller that invokes these methods automatically in every routing mode.

The integrating page/controller/host flow is responsible for calling the contract at the appropriate request boundary.

---

## 6. Page modules

Reusable page fragments implement:

```php
Base3\Page\Api\IPageModule
```

The contract is:

```php
interface IPageModule extends IBase {

	public function setData($data);

	public function getHtml();
}
```

A page module therefore has:

* a stable technical name
* an input-data setter
* rendered HTML output

This is a lighter contract than `IDisplay`.

---

## 7. Content modules

`IPageModuleContent` is a marker interface:

```php
interface IPageModuleContent extends IPageModule {}
```

It identifies modules intended for the page content area.

The marker adds no new methods.

---

## 8. Header modules

Header modules implement:

```php
interface IPageModuleHeader extends IPageModule {

	public function getPriority();
}
```

The current API documentation describes higher values as typically appearing earlier for header layout logic.

The actual rendering/composition system remains responsible for applying ordering consistently.

---

## 9. Footer modules

Footer modules implement:

```php
interface IPageModuleFooter extends IPageModule {

	public function getPriority();
}
```

The current API documentation notes that lower values may appear earlier depending on layout logic.

Because these legacy interfaces do not share the typed `ISortable` contract, consumers should follow the ordering rules of the page composition implementation they are integrating with.

---

## 10. Module dependencies

A module may implement:

```php
interface IPageModuleDependent extends IPageModule {

	public function getRequiredModules();
}
```

The method returns required module identifiers.

This allows a page composition layer to ensure that supporting modules are present when one module depends on another.

The framework interface does not define a universal dependency resolver algorithm. The page composition implementation owns that behavior.

---

## 11. Page module identity

Because `IPageModule` extends `IBase`, module identity comes from:

```php
public static function getName(): string;
```

Use stable lowercase technical names.

Do not use translated labels or UI captions as the module identity.

---

## 12. Pages and `IOutput`

A page can be selected anywhere an `IOutput` can be selected because it extends the same contract.

That means class-map discovery can resolve a page through:

```php
$classMap->getInstanceByInterfaceName(IOutput::class, 'example');
```

or specifically as a page:

```php
$classMap->getInstanceByInterfaceName(IPage::class, 'example');
```

Choose the narrow interface that expresses the caller's real requirement.

---

## 13. Pages and MVC

A page may render through `IMvcView`, but MVC is not required by `IPage`.

Typical pattern:

```text
IPage implementation
  -> prepares data
  -> configures IMvcView
  -> loads template
  -> returns HTML
```

See `mvc.md`.

---

## 14. Pages and displays

`IDisplay` and `IPageModule` are both reusable rendering concepts but have different contracts.

```text
IDisplay
  IOutput-like rendering with setData()
  may support different output formats

IPageModule
  setData() + getHtml()
  focused on page fragment HTML
```

Do not introduce an adapter layer unless the actual integration needs one.

Prefer the existing contract used by the surrounding page system.

---

## 15. Pages and plugins

Page classes that should be discoverable must live under the plugin's `src/` tree and follow normal namespace/path rules.

For example:

```text
plugin/ExamplePlugin/src/Page/DashboardPage.php
```

with:

```php
namespace ExamplePlugin\Page;
```

The class map can then discover the interface implementation.

---

## 16. Help capability

The current output architecture separates help from rendering.

A page that wants developer help can additionally implement:

```php
Base3\Api\IHelp
```

Example:

```php
final class ExamplePage implements IPage, IHelp {

	public function getHelp(): string {
		return 'Shows the example page.';
	}
}
```

Do not add `getHelp()` to a new `IPage` implementation as if it were required by the page contract.

---

## 17. Current scope of the framework page API

The framework currently provides page contracts, not one complete universal page composer.

The interfaces define extension slots for:

* pages
* catch-all pages
* POST-aware pages
* content modules
* header modules
* footer modules
* dependent modules

A plugin or host system may provide the final page assembly behavior.

That final implementation should consume these contracts rather than duplicating them with parallel page interfaces.

---

## 18. Summary

```text
IPage
  IOutput + public URL

IPageCatchall
  fallback page marker

IPagePostDataProcessor
  page submission lifecycle

IPageModule
  named reusable HTML fragment

IPageModuleContent
  content module marker

IPageModuleHeader
  header module with priority

IPageModuleFooter
  footer module with priority

IPageModuleDependent
  declares required modules
```

Keep page contracts small and let routing, MVC, and project-specific composition perform their own responsibilities.
