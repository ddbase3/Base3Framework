# PHPUnit bootstrap test and hook listener output correction

## Scope

This patch addresses the remaining reported PHPUnit error after the namespace migration and removes the two hook-listener log lines emitted during the full PHPUnit run. It does not change production routing, the container setup, authorization, page rendering, migrations, manifests or the Foundation dependency direction.

## Root cause and correction

- `test/Core/BootstrapTest.php` executed the real bootstrap with the default site request. On installations with an `index` page configuration, the service selector reached `GeneratedPage`, then `PageHeader::getHtml()`, where the minimal bootstrap-only container did not define the application-specific `navigation` service. The test now supplies a deterministic, non-rendering `out=help` request with a synthetic name, restores `$_GET` and `$_REQUEST` in `finally`, and keeps every original container/lifecycle assertion. The production `PageHeader` and service selector are unchanged.
- `plugin/Core/src/Hook/BootstrapLoggingHookListener.php` previously called `error_log()` in `handle()` even though the sample listener is inactive by default. The direct invocation tests printed hook messages. The logging side effect was removed while preserving the listener's subscribed hooks and return value. A regression test verifies that `handle()` writes no error log.
- `test/Hook/HookManagerTest.php` defined an extra `Base3\Hook\error_log()` function to shadow logging, but the actual listener lives in `Base3\Core\Hook`. This obsolete, ineffective test workaround has been removed. No replacement suppression layer was introduced.

## Evidence

The user-provided full suite run reported 8,244 tests, 36,562 assertions and exactly one remaining error in `BootstrapTest`, plus hook log messages and 21 notices. This patch targets that last error and the hook messages only. The notices are unchanged by this scope.

Locally passed: PHP lint for all four changed files; an isolated execution of the original BootstrapTest assertions against Core, CoreFoundation and ModuledPage; restoration of request superglobals; a direct silent-listener check with the PHP error log redirected to a temporary file; the output-free hook subscriptions and return value; extraction into a clean patch footprint; and repeatable cleanup execution.

The complete 8,244-test PHPUnit suite was not executed locally because the site-specific Composer/PHPUnit dependencies are not available in this workspace. The definitive regression check is `make test` on the user installation.

## Installation

Run from the BASE3 root:

```sh
unzip -o base3-phpunit-bootstrap-listener-fix.zip
bash namespace-refactor-bootstrap-listener-cleanup.sh
make test
```

No file was moved or renamed in this patch, so the mandatory cleanup script has no deletions to perform. No classmap cache needs invalidation.
