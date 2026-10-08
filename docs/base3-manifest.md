# BASE3 Module Manifest

## Purpose

Every BASE3 module has a `base3.json` file at its module root.

The manifest serves three purposes:

* it identifies the directory as a BASE3 module
* it provides stable module metadata without deriving identity from the physical directory layout
* it declares hard dependencies on other BASE3 modules when required

This is especially important for embedded BASE3 installations where modules may live below host-specific directory structures.

---

## 1. Minimal manifest

A valid version 1 manifest contains four required fields:

```json
{
    "manifestVersion": 1,
    "name": "ExamplePlugin",
    "namespace": "ExamplePlugin",
    "version": "1.0.0"
}
```

The fields mean:

* `manifestVersion` identifies the schema version of `base3.json`
* `name` is the stable logical BASE3 module name
* `namespace` is the module's PHP root namespace
* `version` is the version of this individual BASE3 module

The module version remains independent from any product, deployment package, host component, or repository release that contains the module.

---

## 2. Module root

The directory containing `base3.json` is the module root.

Typical standalone layout:

```text
plugin/
└── ExamplePlugin/
    ├── base3.json
    ├── src/
    ├── tpl/
    ├── assets/
    ├── lang/
    └── test/
```

The normal BASE3 directory conventions continue to apply relative to that module root.

---

## 3. Physical location is not module identity

The manifest allows host integrations to separate physical placement from logical module identity.

These paths can describe the same logical module when their manifests contain the same module metadata:

```text
plugin/IliasReporting/base3.json
components/Qualitus/Reporting/lib/IliasReporting/base3.json
components/AnyVendor/AnyComponent/AnySubDir/IliasReporting/base3.json
```

For example:

```json
{
    "manifestVersion": 1,
    "name": "IliasReporting",
    "namespace": "IliasReporting",
    "version": "1.4.2"
}
```

A host-specific class map or bootstrap may therefore discover modules by locating `base3.json` manifests instead of assuming a fixed directory depth. The rest of BASE3 continues to depend on framework abstractions such as `IClassMap`, not on the host filesystem layout.

---

## 4. Name and namespace

`name` and `namespace` are explicit because they are not always identical.

A normal plugin may use:

```json
{
    "manifestVersion": 1,
    "name": "IliasReporting",
    "namespace": "IliasReporting",
    "version": "1.4.2"
}
```

The framework module itself uses a different root namespace:

```json
{
    "manifestVersion": 1,
    "name": "Base3Framework",
    "namespace": "Base3",
    "version": "4.10.0"
}
```

Consumers must not derive the namespace from the directory name when a manifest is available.

---

## 5. Optional metadata

A manifest may contain descriptive metadata in addition to the required fields.

Example:

```json
{
    "manifestVersion": 1,
    "name": "Base3Framework",
    "namespace": "Base3",
    "version": "4.10.0",
    "description": "BASE3 core framework.",
    "license": "GPL-3.0-or-later",
    "maintainers": [
        {
            "name": "Daniel Dahme",
            "url": "https://www.base3.de",
            "email": "info@base3.de"
        }
    ],
    "repository": "https://github.com/ddbase3/Base3Framework"
}
```

Recommended optional metadata fields are:

* `description`: short human-readable module description
* `license`: SPDX-compatible license identifier where possible
* `maintainers`: list of maintainers for the module
* `repository`: canonical human-readable repository URL

A maintainer entry requires `name` and may additionally contain:

* `organization`
* `url`
* `email`

For example, a maintainer acting through an organization can be represented as:

```json
{
    "name": "Daniel Dahme",
    "organization": "Qualitus GmbH",
    "url": "https://www.qualitus.de",
    "email": "dahme@qualitus.de"
}
```

If a maintainer represents the BASE3 project directly rather than a legal organization, `organization` can simply be omitted:

```json
{
    "name": "Daniel Dahme",
    "url": "https://www.base3.de",
    "email": "info@base3.de"
}
```

The `repository` field should normally use the human-readable repository URL, for example:

```text
https://github.com/ddbase3/Base3Framework
```

A `.git` suffix is not required.

Optional metadata must not redefine the module's physical path or duplicate directory conventions that BASE3 already defines.

In particular, the manifest should not need fields such as:

```text
path
vendor
component
src
assets
lang
enabled
```

The module root comes from the location of `base3.json`, and standard directories remain conventions relative to that root.

---

## 6. Dependencies

A module may declare hard dependencies on other BASE3 modules through `dependencies`.

Example:

```json
{
    "manifestVersion": 1,
    "name": "Base3Ilias",
    "namespace": "Base3Ilias",
    "version": "4.21.0",
    "description": "Integration of the BASE3 Ecosystem with ILIAS LMS",
    "license": "GPL-3.0-or-later",
    "maintainers": [
        {
            "name": "Daniel Dahme",
            "url": "https://www.base3.de",
            "email": "info@base3.de"
        }
    ],
    "repository": "https://github.com/ddbase3/Base3Ilias",
    "dependencies": {
        "Base3Framework": ">=4.10.0",
        "ResourceFoundation": ">=4.5.0",
        "UiFoundation": ">=4.3.0"
    }
}
```

Dependency keys are the exact `name` values of the required BASE3 modules.

Dependency values are version constraints. They should use Composer-style version constraint syntax so that common forms remain possible, for example:

```text
>=4.10.0
^4.10
>=4.5 <5.0
```

The manifest should declare only hard module dependencies. A module belongs in `dependencies` when the declaring module cannot operate correctly without it.

Do not use `dependencies` merely because another plugin may provide an optional implementation or because a service is selected through a Foundation contract.

The normal BASE3 dependency rules still apply:

* reusable plugins should usually depend only on BASE3 framework APIs, their own code, Foundation APIs, and explicit extension targets
* implementation choices should remain in project composition
* direct dependencies between unrelated normal plugins should be avoided
* extension plugins may declare the plugin they explicitly extend

Modules should declare `Base3Framework` explicitly when they require a specific framework version. Tooling should not need an implicit rule to infer that dependency.

---

## 7. Manifest validation

For manifest version 1:

* `manifestVersion` must be the integer `1`
* `name` must be a non-empty string
* `namespace` must be a non-empty string
* `version` must be a non-empty string
* `maintainers`, when present, must be an array
* every maintainer entry must contain a non-empty `name`
* `organization`, `url`, and `email` are optional maintainer fields
* `dependencies`, when present, must be an object mapping module names to non-empty version constraints
* the file must contain valid JSON

Unknown optional fields should be ignored by readers that do not use them. This allows the manifest format to grow without forcing every consumer to understand every metadata field.

A malformed manifest should be treated as an invalid BASE3 module declaration rather than silently interpreted as another format.

---

## 8. Discovery

The standard standalone BASE3 layout may continue to scan the configured plugin directory directly.

Embedded host integrations may use `base3.json` as the discovery marker. A host integration can recursively search its allowed module area, treat each directory containing `base3.json` as a module root, and register the declared namespace and source path with its class map and autoloader.

The active runtime also exposes `Base3\Api\IModuleRegistry` as the service for resolving a logical BASE3 module name to its physical module root. The default standalone implementation reads the framework root plus direct modules below `DIR_PLUGIN`. Embedded runtimes may replace that implementation with their own manifest discovery while consumers continue to depend only on `IModuleRegistry`.

The discovery scope itself is host-specific. For example, an ILIAS integration can search below `components/` while excluding host-owned areas such as `components/ILIAS` that should not be explored.

BASE3 core code should not contain a fixed list of host vendors or product names.

---

## 9. Relationship to packaging

A BASE3 module manifest describes the individual module. It does not define how several modules are grouped for deployment.

An embedded host may package several BASE3 modules together in one host component or release repository. Each contained BASE3 module still keeps its own `base3.json` and its own module version.

For example, an ILIAS component repository may keep included BASE3 modules below a conventional `lib/` directory while the BASE3 runtime continues to discover modules solely through their manifests.

This keeps BASE3 module identity portable between standalone and embedded installations while allowing host integrations to choose their own packaging structure.
