# Namespace refactoring: PHPUnit discovery and diagnostic output correction

## Scope

This is a correction to the previously installed BASE3 namespace migration. It does not change application routing, permissions, persistence, manifests, module registration, or production namespace ownership.

## PHPUnit failures reported on 2026-11-13

The supplied run contained 8,241 tests, 43 errors, 79 failures, and three PHPUnit runner warnings. The source-backed corrections address these causes:

- `test/Configuration/ConfigFile/ConfigFileTest.php` still declared the old configuration namespace and instantiated the old relative class name. The test now imports `Base3\Core\Configuration\ConfigFile\ConfigFile` and defines its configuration constant in the owning namespace. The adjacent configuration edge-case fixture was corrected as well.
- FileLogger, FileToken, FileXrm, and MediaXrm test fixtures defined directory constants under pre-migration namespaces. Their constants now match the actual production namespaces. The isolated Bootstrap test defines the correct ModuledPage directory constant before loading `GeneratedPage`.
- Two PhotoMosaic tests imported the tool as `Base3\Base3ChatWebsite\Base3\MissionBay\...`. Both now import the actual `Base3\Base3ChatWebsite\MissionBay\PhotoMosaicDemoAgentTool`.
- The Microservice master test asserted an obsolete `src/` location; it now verifies the real production connector in `plugin/Core/src/`.
- `plugin/Base3Backend/test/BackendPluginCompositionTest.php` was a standalone executable smoke test, not a subclass of PHPUnit `TestCase`. When PHPUnit discovered that file it **executed the script and printed output before ordinary tests ran**. Sending output can make later `http_response_code()` calls fail with `false` in CLI, explaining the common pattern in the reported HTTP-status assertions and type errors. The script is now `BackendPluginCompositionSmoke.php` and remains independently runnable. This corrects the actual suite-discovery owner; no global output buffer or production endpoint change was introduced.
- The Chronos and MailParser examples were `IPage` implementations, not PHPUnit test cases. They now have corresponding `*ExamplePage.php` files/classes instead of misleading `*Test.php` names.

The accompanying cleanup script removes the three obsolete PHPUnit-discoverable files only when their contents match the previously supplied migration. A changed local file blocks cleanup; repeated execution is safe.

## Diagnostics

`AbstractSourceDiagnosticCheck::report()` remains the single owner of source-diagnostic summaries. It now shows up to three path-specific examples (maximum 150 characters each) and an explicit `(+N)` remainder. Counts remain exact in structured result details. The existing two Core `DiagnosticChecks` translation sections include `{sample}` in failure and warning messages in all twelve languages, so the existing `DiagnosticScopeDisplay` renders these examples without a second report format or UI.

## Verification and limits

Completed locally: PHP syntax checks of changed files; twelve INI catalog parsing checks; class autoload and basic initialization; standalone Backend composition smoke with the full supplied module set; actual namespace/header diagnostic runs; fixture-based checks of example limits, counts, and translated German output; and correction of test-only directory constants and production connector paths. Patch extraction and checksum-safe cleanup are tested independently.

The complete PHPUnit suite cannot be run in this build container because PHPUnit and the site's root vendor dependencies are not present. **The reported 43 errors, 79 failures, and runner warnings are source-addressed but not claimed to be fully resolved until `make test` passes in the user's installation.** The reported 44 classmap naming issues, seven legacy migration markers, missing module LICENSE files, and broader translation-catalog gaps are outside this bounded test/bootstrap and diagnostic-output correction.
