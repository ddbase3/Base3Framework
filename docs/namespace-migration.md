# BASE3 Namespace Migration: Core, CoreFoundation, AssistantFoundation, AssistantRuntime

## Scope of this patch

The former root `src/` is retired. Exactly one application bootstrap autoloader remains at `bootstrap/Autoloader.php`. The existing `PluginClassMap`, `ClassMap`, `ModuleRegistry` and `Bootstrap` implementations remain the authoritative owners of their respective discovery and runtime duties.

- `CoreFoundation` (`Base3\CoreFoundation`) contains framework interfaces, models, DTOs, exceptions, shared abstract classes, neutral helpers and shared traits.
- `Core` (`Base3\Core`) provides concrete framework services and the default bootstrap. Its module manifest depends on `CoreFoundation`.
- `AssistantFoundation` and `AssistantRuntime` use `Base3\<Module>` and depend on renamed Foundation contracts.
- Module names, classmap app identities, logical `getName()` values, agent identifiers, configuration group names and asset locations are not automatically renamed.

No duplicate autoloader, fallback namespace, class alias or compatibility service is introduced.

## Installation on the matching baseline

First save the deployed files and configuration and secure a Git reference to the last accepted revision. Apply **only** to a checkout matching the supplied framework and two plugins. In the BASE3 framework root:

```bash
unzip -o base3-namespace-refactor.zip
bash namespace-refactor-cleanup.sh
```

The cleanup script validates both new core modules, checks the checksums of every retired file from the supplied root `src/` and removes exactly these known old files. It never recursively removes an unknown source file. It removes the generated `tmp/classmap.php` and `tmp/ctorcache.php` so the existing classmap regenerates them on next startup. The script can be executed again safely. If an old source file was locally modified, it stops without deleting the old source tree. Review that difference rather than forcing deletion.

If your application uses Composer-generated classmaps or PSR-4 mappings, regenerate them from its real Composer configuration. Reset PHP OPcache or restart the relevant PHP workers during your managed deployment. Do not restart production just to compensate for a class resolution error without inspecting the actual path first.

**Important:** The supplied package is an intermediate source migration for four modules, not a complete converted installation. Other installed plugins still using old PHP namespaces will need corresponding changes before integrated activation. `AssistantFoundation` depends on `MediaFoundation`; `AssistantRuntime` depends on `UiFoundation`. They were not supplied in this archive. Do not install temporary namespace aliases to make mixed versions run.

## Preserved infrastructure boundaries

- Early class loading reads module `base3.json` files before the container exists.
- `ModuleRegistry` remains the single existing runtime module discovery owner.
- `PluginClassMap` uses `IModuleRegistry` and each module's manifest namespace, not a hardcoded framework source scan target.
- `ClassMap` is still the restricted framework discovery implementation, now covering `Core` and `CoreFoundation`.
- `CoreFoundation` has no references to `Base3\Core\` in its PHP source and can load without Core.
- Three abstract bases previously looked up the concrete `ServiceLocator`; their constructors now take `IContainer`. Existing concrete Core classes have been updated, and future consumers of these bases must use the new constructor contracts.

## Persisted data and integration audit

A PHP namespace rename is not by itself a database migration. Before activating a real installation, examine these external and persistent locations **read-only**:

1. `ISettingsStore`, including JSON or database backends. Configured component definitions may store fully qualified interface names and implementation class names. `ComponentDefinition::getServiceNameFor()` also produces keys embedding the interface name; an old key such as `component.definition.Base3.Api.IComponent.*` must be evaluated for migration to `component.definition.Base3.CoreFoundation.Api.IComponent.*` if actually persisted.
2. `IStateStore` values and keys where a plugin may have persisted class-string identifiers. Do not blindly rewrite unrelated logical state or identifiers.
3. `IConfiguration`, `config.ini` files, environment values and custom host config files, including DI bindings, providers, migration class names, selectors and bootstrap implementations.
4. Database rows, JSON columns, serialized PHP objects and queues/jobs containing type names. PHP-serialized objects need format-aware migration rather than a string substitution. Check class strings in incoming requests, saved workflows, agent/tool settings and caches as applicable.
5. Existing migration history tables such as `base3_migrations`: verify provider name, migration ID and checksums against the running migration implementation before changing any history. Never rerun already applied schema changes or overwrite their history speculatively.
6. `tmp/classmap.php`, `tmp/ctorcache.php`, PHP OPcache, preloaded scripts and Composer classmaps. Only the first two generated files are deleted by the included cleanup script.
7. Embedded host bootstrap/classmap/autoloader paths, especially ILIAS integrations, and host-owned configuration, plugin deployment locations, CI workflows, PHP entry points, classmaps and tests.
8. Third-party plugins and packages not in this ZIP that still depend on `Base3\Api`, `Base3\<old framework subsystem>`, `AssistantFoundation\...`, `AssistantRuntime\...` or other unprefixed plugin roots.

The patch does not modify a live database, an external host application or missing plugin repositories. All such references remain explicit follow-up audit items.

## Scope of source checks

The supplied source contains 293 PHP files plus 10 non-PHP data files under root `src/`. All are represented in their new Core/CoreFoundation locations (except the illustrative PHP comment-only Example source, now kept as a document). A complete old-file cleanup manifest is supplied alongside this document. Source type names and file locations are recorded in `docs/namespace-migration-map.tsv` for follow-up plugin batches.

Local checks: PHP syntax checks; Core/CoreFoundation isolated loading; direct CoreFoundation-only loading; classmap generation for Core/CoreFoundation; minimal standalone bootstrap; KnowledgeSource static data loading. PHPUnit was not available in the execution environment and therefore its test suite has not been run. Generating the classmap for all four supplied modules also fails until `UiFoundation` is available, so a full integrated green status cannot be claimed.

## Baseline recovery

On failure, use the last accepted Git revision and its matching deployed dependencies, plus your backed-up configuration and persisted data. Unzipping the old source tree into the new directories is not a complete rollback because moved files must also be removed. No compatibility mode or alternate serving route is provided in this patch.

## Batch 2: Base3Backend, Base3ChatWebsite, Base3Template, Base3Tools

This is a source-only namespace migration on the same accepted architecture. All four modules now
use `Base3\<Module>` as their PHP root namespace; every `use`, class literal, cross-module
reference, manifest namespace and dependency declaration in the supplied sources has been
updated. Logical module names, `getName()` identifiers, configured audiences and group keys,
asset addresses and public service names are not rewritten.

All four modules use `CoreFoundation` contracts and `Core` implementations, so their manifests
explicitly require both. `Core` depends on `CoreFoundation`, not the other way around.
Original third-party PHPMailer source in Base3Tools stays in its upstream `PHPMailer\PHPMailer`
namespace; it is not a BASE3 plugin namespace.

No plugin-source directories are renamed in batch 2; consequently there are no old PHP files
to remove. `namespace-refactor-batch02-cleanup.sh` validates these modules and removes only
the two generated caches `tmp/classmap.php` and `tmp/ctorcache.php`, if present.

**Batch 2 local evidence:** PHP syntax checks of all changed PHP files; checked old names and
correct namespace/file positioning; BASE3 Backend standalone composition smoke test; isolated
Core/CoreFoundation/Base3Tools classmap generation; Base3Tools test bootstrap load; isolated
execution of the website's classmap rebuild script. Full project boot, website file-manager
smoke and Base3Template integration are not verified because their other implementation modules
have not yet been supplied in the new namespace format. PHPUnit is not installed here.
Do not add compatibility namespace aliases or invent missing dependencies.

### Complete per-module progress register

Inventory is based on the 27 directories provided for the target installation on
2026-11-13, including Core and CoreFoundation introduced in batch 1. Each batch updates
this register in place. "Converted" means source converted and locally inspected, not
that the complete production installation is yet compatible.

| Module | Source status | Package |
| --- | --- | --- |
| `AssistantFoundation` | Converted; integrated installation pending | 1 |
| `AssistantRuntime` | Converted; integrated installation pending | 1 |
| `Base3Backend` | Converted; integrated installation pending | 2 |
| `Base3ChatWebsite` | Converted; integrated installation pending | 2 |
| `Base3Template` | Converted; integrated installation pending | 2 |
| `Base3Tools` | Converted; integrated installation pending | 2 |
| `Chatbot` | Converted; integrated installation pending | 3 |
| `ChatbotExtensions` | Converted; integrated installation pending | 3 |
| `ClientStack` | Converted; integrated installation pending | 3 |
| `Core` | Converted; integrated installation pending | 1 |
| `CoreFoundation` | Converted; integrated installation pending | 1 |
| `CredentialFoundation` | Converted; integrated installation pending | 4 |
| `DataHawk` | Converted; integrated installation pending | 5 |
| `DatabaseWorkbench` | Converted; integrated installation pending | 5 |
| `FileBridge` | Converted; integrated installation pending | 5 |
| `KeyHarbor` | Converted; integrated installation pending | 5 |
| `MediaFoundation` | Converted; integrated installation pending | 4 |
| `MessageHub` | Converted; integrated installation pending | 5 |
| `MessagingFoundation` | Converted; integrated installation pending | 4 |
| `MissionBay` | Converted; integrated installation pending | 5 |
| `MissionBayReporting` | Converted; integrated installation pending | 5 |
| `MissionBayXrm` | Converted; integrated installation pending | 5 |
| `ModuledPage` | Converted; integrated installation pending | 4 |
| `PulseCore` | Converted; integrated installation pending | 5 |
| `ResourceFoundation` | Converted; integrated installation pending | 4 |
| `UiFoundation` | Converted; integrated installation pending | 4 |
| `Vizion` | Converted; integrated installation pending | 5 |

**Progress:** 27/27 converted in source packages through batch 5. Integrated deployment
verification remains pending. The tracked inventory must be reconciled against the
real deployed plugin directory and any module introduced after this snapshot.

### Batch 2 cross-batch verification scope

- The four batch 2 manifest namespaces are consistent and satisfy the agreed `Core -> CoreFoundation` direction.
- All batch 2 references to the then 19 pending plugins have already been changed to the intended `Base3\<Module>` class names. Their implementation sources and runtime correctness remain unverified until their own batch.
- External database/configuration/state/queue values and existing stored FQCNs still need a read-only deployment scan, as described above. No database mutation is contained here.
- Host-specific integrations and Composer-generated autoload artifacts must be verified against the eventual complete module set.
- The full runtime with mixed, unconverted manifests is intentionally not claimed operational.

## Batch 3: Chatbot, ChatbotExtensions and ClientStack

All three supplied plugins now use `Base3\<Module>` namespaces. This includes declarations,
imports, fully qualified references in PHP and editable text, plugin test classes, manifests,
README documentation and dependent calls to the previously migrated modules. Their manifest
requirements on `Base3Framework` have been replaced by `Core` and `CoreFoundation`.
Plugin/module names, technical `getName()` values, agent/tool/feature IDs and asset file
locations remain unchanged. Missing plugin dependencies are not replaced with aliases or
alternative implementations.

### Asset-loader and resolver verification

- The owning contract is `Base3\CoreFoundation\Api\IAssetResolver`.
- Its existing concrete `Base3\Core\AssetResolver` is injected by the migrated
  `Base3Template` project module, and `Base3\Core\BaseAssetResolver` remains available.
- `Base3\Base3Template\Header\AssetLoaderPageModule` references the logical URL
  `plugin/ClientStack/assets/assetloader/assetloader.min.js` through `IAssetResolver`.
- `Base3\ClientStack\Service\DefaultAssetService` registers the existing actual JS
  file in the ClientStack module's `assets` directory. Its module-configured assets use
  the existing `IModuleRegistry` to locate `local/assets.json` files.
- In an isolated filesystem integration test with the exact supplied asset, the resolver
  returned the expected `assets/ClientStack/assetloader/assetloader.min.js?t=<md5-prefix>`
  and the service registered the same local file. A Core logical asset location is
  still resolved consistently under `assets/Core/`.
- This verifies internal resolution and registration, **not** external HTTP serving.
  A real deployed web-server rewrite/symlink/public-assets configuration still needs an
  HTTP verification. Legacy templates may have direct logical asset URLs; they are not
  PHP namespaces and are not silently changed during the namespace refactoring.

### Verification and installation

The patch contains only the changed source-like files from the three supplied plugin trees,
this updated migration document and `namespace-refactor-batch03-cleanup.sh`.
No bundled minified JS, images, static binary assets or third-party code are re-packed.
No file in the three modules moved on disk, so the cleanup only safely removes the two
existing generated classmap caches, if present, after validating the new module manifests.

Apply from the framework root on a checkout where batches 1 and 2 were already installed:

```bash
unzip -o base3-namespace-batch03.zip
bash namespace-refactor-batch03-cleanup.sh
```

Checks in the isolated staging copy: all three module manifests and dependency direction;
PHP syntax of migrated PHP sources and tests; namespace to directory correspondence for
all files under the three module source/test trees; no old framework/module namespace
references in the tracked textual source; three plugin initialization smoke checks;
Chatbot event streaming including Unicode; ClientStack's six built-in asset registrations;
AssetLoader plus Core AssetResolver hash/path integration; and detection of all eleven
currently converted modules by the module registry.

**Unverified:** End-to-end runtime behavior with the remaining sixteen modules, full PHPUnit
and browser suites, embedded-host resolution and live HTTP asset serving. Persisted
settings, state, configuration, queues, migration history and databases must continue to be
assessed by read-only deployment scans. Conversion of the known PHP references is not proof
that externally persisted fully qualified class strings have been migrated.

### Modules pending at the time of batch 3

`CredentialFoundation`, `DatabaseWorkbench`, `DataHawk`, `FileBridge`, `KeyHarbor`,
`MediaFoundation`, `MessageHub`, `MessagingFoundation`, `MissionBay`,
`MissionBayReporting`, `MissionBayXrm`, `ModuledPage`, `PulseCore`,
`ResourceFoundation`, `UiFoundation`, `Vizion`.


## Batch 4: CredentialFoundation, MediaFoundation, MessagingFoundation, ModuledPage, ResourceFoundation, UiFoundation

Six modules from `fo.zip` are converted to the root namespace `Base3\<Module>`.
All existing PHP namespaces, `use` statements, FQCN references, tests, relevant documentation
and module manifests have been rewritten. Source-level checks found no remaining old
Core or plugin namespace references in these six packages. Both single-escaped and
JSON/PHP-string-escaped namespace forms were checked. Technical `getName()` values,
module identities, paths to templates and assets, and stored business identifiers
remain unchanged.

The five Foundation modules (`CredentialFoundation`, `MediaFoundation`,
`MessagingFoundation`, `ResourceFoundation`, `UiFoundation`) use CoreFoundation
contracts and declare `CoreFoundation` as their required BASE3 module. Their
source contains no direct `Base3\Core\` dependencies. `ModuledPage` additionally
consumes `Core` implementations (`Check`, `MvcView`, `ServiceLocator`), so its manifest
requires both `Core` and `CoreFoundation`. `CoreFoundation` remains independent from
`Core`; the direction `Core -> CoreFoundation` is not reversed.

The source package also contained ModuledPage tests in four different namespace
patterns. They are normalized to `Base3\ModuledPage\Test\...` so they match the
existing plugin test autoload path. Where these tests had implicitly resolved
production classes from their own namespace, the original production class is now
explicitly imported. The test subject, assertions and production behavior are not
altered.

### Installation

Install this package from the BASE3 root after namespace batches 1, 2 and 3
have been installed, using the matching supplied source revisions:

```bash
unzip -o base3-namespace-batch04.zip
bash namespace-refactor-batch04-cleanup.sh
```

No module source paths were moved in this batch. The script validates the six
new manifests, the Core/CoreFoundation dependency direction and the identity of
the target modules. It then removes only the existing generated files
`tmp/classmap.php` and `tmp/ctorcache.php` after checking that neither is a symlink
or a non-regular file. It is safe to repeat and does not delete configuration,
plugin sources, Git files, assets or persisted state.

### Local verification

- All 265 changed PHP source and test files pass `php -l` (PHP 8.4 CLI).
- Manifest JSON and TAB indentation validated for all six new manifests.
- Namespace-to-file-path matches checked in all six source and test trees.
- No old PHP class names from the known BASE3 namespace map or unprefixed
  plugin roots remain in their supplied text files.
- Isolated classmap generation succeeded with `Core`, `CoreFoundation` and
  all six new modules. All six new plugin classes loaded and initialized.
- HMAC sign and verification, ImageEdit builder, messaging and query DTOs,
  ModuledPage route parsing and UiFoundation settings base contract passed
  targeted PHP regression smoke checks.
- The 17-module staging registry recognized all 17 converted modules.

**Integration limit:** A classmap scan of all 17 currently available modules
still fails while `Base3ChatWebsite` requires `Base3\MissionBay\Resource\AbstractAgentResource`
from the not-yet-delivered `MissionBay` package. This is an unresolved source
dependency, not a reason to introduce a fallback or compatibility namespace.
The complete PHPUnit suite, real standalone/embedded HTTP startup, production
asset serving, pending plugin implementations, and persisted database/configuration
migration were not verified. PHPUnit is not installed in the local environment.

### Modules pending at completion of batch 4, fixed 27-module inventory

`DatabaseWorkbench`, `DataHawk`, `FileBridge`, `KeyHarbor`, `MessageHub`,
`MissionBay`, `MissionBayReporting`, `MissionBayXrm`, `PulseCore`, `Vizion`.

All remaining packages must receive the same exact namespace and reference
conversion. Reconcile this fixed source inventory with any additional modules
found in the real installation before claiming a complete deployment.


## Batch 5: final ten modules and all 27 manifests

This patch processes the ten modules supplied in `fi.zip`: `DatabaseWorkbench`,
`DataHawk`, `FileBridge`, `KeyHarbor`, `MessageHub`, `MissionBay`,
`MissionBayReporting`, `MissionBayXrm`, `PulseCore` and `Vizion`. The 27-module
inventory above is now complete as a **source migration**. This is not a claim
that all deployed host integrations and persisted runtime records are migrated.

### All module manifests

**All 27 `plugin/<Module>/base3.json` files are included in the final ZIP**, not
just the ten changed by this batch. Each has `manifestVersion: 1`, its existing
logical `name`, `namespace: Base3\<Module>`, its release version as supplied,
and dependencies expressed as module names. The `Base3Framework` hard dependency
is gone. The dependency graph is acyclic and `Core` depends on `CoreFoundation`
without a dependency in the opposite direction. No old root `src/` is used.

`MissionBayXrm` was delivered without a `base3.json`. Its newly created
manifest depends on `CoreFoundation`, `AssistantFoundation` and `MissionBay`,
matching direct source-level dependencies. Neither Git tags nor supplied
repository documentation established a release version. Accordingly the
manifest uses **`0.0.0` as an explicitly unknown/unreleased version marker**.
Do not interpret it as a published or verified version. The release owner
must assign the actual version when this module is versioned for distribution.

Every old plugin PHP root is now prefixed with `Base3`. Files that contain
third-party namespaces remain owned by their upstream package. Logical names
returned by `getName()`, server action identifiers, agent IDs, state keys,
asset paths and existing database schema names were preserved.

### Source findings and hidden workarounds (mandatory disclosure)

The following were confirmed in the current supplied code:

1. **Silent missing-manifest omission:** `bootstrap/Autoloader.php::register()`
   uses `if (!is_file($manifestPath)) continue;`. The existing
   `Core/ModuleRegistry::registerModule()` also returns silently when a
   manifest is missing. Thus a plugin may disappear from discovery instead
   of raising an explicit error. `MissionBayXrm` was affected; the real
   manifest is included. No new fallback or secondary registry was added.
2. **Silent class skip in the existing classmap:**
   `CoreFoundation/Base/AbstractClassMap::getModuleScanTargets()` skips a
   module with no `src/`; `scanClasses()` skips a file when the class
   predicted from its physical path does not exist. Incorrect namespaces
   can therefore go unnoticed if only the discovered class count is checked.
   This patch instead verifies the complete expected module inventory and
   all source namespace declarations; it does not build an alternate classmap.
3. **Obsolete direct includes:** `plugin/MissionBay/generate-token.php` still
   required three obsolete framework-root `src/` paths. It now uses the
   existing `bootstrap/Autoloader.php` and correctly finds the moved Core
   token implementation. Token generation semantics were otherwise retained.
4. **Test-local autoloaders:** two MissionBay CLI smoke scripts and one
   PulseCore test bootstrap used separate direct mappings to the old source
   layout. They now use the existing BASE3 bootstrap autoloader.
5. **Empty compatibility placeholders in MissionBay:**
   `src/Api/IAgentToolExecutionOutcome.php`,
   `src/Speech/ConfiguredSpeechToTextService.php` and
   `src/Dto/Mcp/McpRemoteToolResult.php` contain only comments and declare
   no type. They are not runtime compatibility adapters. They remain
   unchanged and are explicitly recorded for subsequent repository cleanup.
6. **Special test-owned namespaces:** four MissionBay tests still declare
   namespaces matching production areas so they can substitute PHP functions
   in those areas. These are intentional function-scope tests, not duplicate
   serving implementations. They were not blindly moved into `Test\`.

The first two findings are **remaining behavior in the existing Core owners**.
No permission has been given to change plugin-directory admission policy for
all deployment layouts in this namespace-only batch. Their existence and
impact must remain visible. Further host-specific bootstrap/autoloader
implementations have not been supplied, so no blanket absence claim applies.

### Verification performed locally

- Every one of the 27 manifest files parsed, matched the directory/module
  name and `Base3\<Module>` namespace, used TAB indentation and referenced
  only available modules. Their dependency graph was verified acyclic.
- CoreFoundation was checked independently against Core dependencies in
  126 PHP source files; no `Base3\Core\` reference was found.
- The existing classmap successfully discovered **all 27 modules** in an
  isolated assembled staging tree, including `MissionBayXrm`.
- `php -l` passed for all **1,760** changed PHP source and test files from
  batch 5 (PHP 8.4). The existing BASE3 namespace conversion check read
  4,462 text sources across the complete 27-module assembly and reported
  no old Core or plugin namespace roots.
- Two MissionBay guarded CLI tests passed with the existing autoloader.
  The PulseCore suite passed 249 PHP assertions, including authorization,
  session state, saved progress and revision protection checks.
- The final patch installation and its manifest-aware cleanup were checked
  separately, including repeated execution and refusal of unsafe cache
  deletion targets. The script removes only `tmp/classmap.php` and
  `tmp/ctorcache.php`, never a source or persisted data file.

**Not verified here:** PHPUnit (unavailable), a deployed HTTP/worker/agent
runtime, production concurrency, host embeddings such as ILIAS, Composer
classmap deployment, HTTP asset delivery and the contents of external
configuration, SettingsStore, StateStore, database migration history,
serialized objects, queues or stored workflows. Examine those sources
read-only before the coordinated installation release. This ZIP does not
modify persisted data or provide aliases for old PHP namespaces.

### Installation of batch 5

Apply only after batches 1 through 4 to a matching code checkout, in the
BASE3 framework root:

```bash
unzip -o base3-namespace-batch05.zip
bash namespace-refactor-batch05-cleanup.sh
```

The ZIP contains the changed sources/tests of the ten supplied modules,
**all 27 manifests**, this report, the expanded existing
`NamespaceMigrationIntegrityTest` and the cleanup script. The cleanup
checks the complete tracked manifest set and cache-file safety before
removing only the generated classmap files. It can be repeated safely.

A rollback must restore the last accepted revisions of all affected
repositories as one coordinated set, not mix pre-migration namespaces
with converted modules. The physical host and persisted-data checks
remain release gates.

## PHPUnit repair and namespace/header diagnostics after the 27-module migration

The deployed website was reported running after batch 5. The separate PHPUnit
entrypoint still contained references to types that had moved into
`CoreFoundation`, starting with `test/Core/DummyOutput.php`. The revised helper
extends the existing `Base3\CoreFoundation\Base\AbstractOutput` and does not
introduce a compatibility alias. Legacy root test names starting with
`Base3Test\` were corrected to the expected `Base3\Test\` convention. The
existing BASE3 bootstrap autoloader remains the sole autoload owner.

Two read-only checks are discovered through the *existing* `IDiagnosticCheck`
classmap contract in the installation scope:

- `PhpNamespaceDiagnosticCheck` validates namespaces and module references in
  PHP source, tests, first-party entrypoints and templates. It reports invalid
  or missing manifests and unloadable or invalid PHP as findings rather than
  silently skipping them.
- `PhpSourceHeaderDiagnosticCheck` validates the complete BASE3 copyright,
  author, license and *module-specific explanatory text* of files declaring a
  `Base3\...` namespace. Each plugin has its own description. Existing complete
  headers retain their original text; missing or incomplete headers receive
  the corresponding verified module text rather than the generic Core header.
  Root framework tests and the central autoloader use the Framework header.

The checks reuse `AbstractSourceDiagnosticCheck` and the one existing source
inventory. There is no new diagnostic runner, alternate classmap, autoloader,
namespace alias, service route or compatibility mode. The checks do not write
any source file or configuration. Third-party files under `vendor/` and
`node_modules/` are excluded and must be audited by their own package owners.
`CoreFoundation` retains no reference to `Base3\Core\`.

The project owner supplied the exact BASE3 Framework header. For plugins, the
header's initial ownership line and explanatory paragraph belong to the actual
module. The standard author and GPL-3.0 lines are common. Links were copied
from verified source headers or module manifests, not invented. The new
`CoreFoundation` header describes framework contracts and the one-way
`Core` dependency rather than incorrectly repeating the Core runtime description.

### Local validation of this repair patch

- All 27 module roots are included in the scan.
- The header check inspected 3,629 PHP files (including the two new test files),
  with 0 failures and 0 warnings.
- The namespace check inspected the same source tree and reported one existing
  unresolved dependency: `plugin/Core/src/Xrm/XrmSearchService.php` references
  `Base3\Custom\Display\XrmEntryDisplay`, which does not exist. A class with
  that name's unqualified suffix exists in `Base3Template`, but directly
  coupling the shared Core implementation to the website plugin would violate
  the documented dependency direction. This dependency requires a separate,
  source-owned behavior decision, not a compatibility alias or auto-rewrite.
- One other namespace diagnostic warning refers to
  `test/Dummy/ClassLoader.php`, a deliberate external `PHPUnitPHAR` test stub,
  not a BASE3 module class.
- The known failing `AbstractOutput` PHPUnit helper can now be instantiated
  through the existing module autoloader. File syntax and the check's negative
  cases were validated locally.
- A complete PHPUnit run on the deployed application is still required; the
  local artifact workspace does not contain PHPUnit. Do not infer a green
  `make test` from the successful PHP lint or standalone checks.

The patch's cleanup script only removes the generated classmap and constructor
caches after validating all removal targets. It does not remove or alter module
sources, database records, uploaded files or current runtime state.
