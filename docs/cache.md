# BASE3 URL Cache

## Purpose

This document explains the cache subsystem currently present in BASE3.

The current `ICache` contract is intentionally narrow. It is not a general key/value cache API. It exists to cache remote URL resources and return local/public cache URLs.

---

## 1. `ICache`

The framework contract is:

```php
namespace Base3\Cache\Api;

interface ICache {

	public function getCacheUrl($url, $refresh = false);

	public function getCacheUrls($urls, $refresh = false);
}
```

The API deals with URL resources.

It should not be confused with:

* `IStateStore`
* `ISettingsStore`
* application object caches
* HTTP response caching middleware

---

## 2. Main use case

The intended flow is:

```text
remote URL
  -> ICache
  -> local cached file
  -> public URL to cached file
```

This is useful when an application wants a stable local copy of externally hosted media or files.

---

## 3. Single URL and multiple URLs

For one resource:

```php
$cached = $cache->getCacheUrl($url);
```

For multiple resources:

```php
$cached = $cache->getCacheUrls($urls);
```

The optional `$refresh` flag asks the implementation to invalidate or replace an existing cache record.

The exact returned structure is implementation-defined by the current broad legacy signatures. Consumers should verify the selected backend semantics.

---

## 4. `FileCache`

The built-in implementation is:

```php
Base3\Cache\FileCache
```

Despite the name, it uses both:

* database metadata
* files under the public/userfiles cache area

The current destination directory is:

```text
userfiles/cache/
```

The database stores metadata including:

```text
hash
value
extension
timeout
```

The cache lookup key is the SHA-1 hash of the original URL.

---

## 5. Cache file layout

`FileCache` generates a random cache value and distributes files into a two-level directory structure derived from that value.

Conceptually:

```text
userfiles/cache/ab/cd/<generated-value>.<extension>
```

The public URL is built using the configured base URL plus the cache path.

---

## 6. Refresh behavior

With:

```php
$refresh = true
```

`FileCache` deletes the existing database metadata for the URL hash before creating a new cache record.

Without refresh, an existing record is reused and its timeout is extended.

The current default timeout is approximately one month.

---

## 7. Storage requirements

`FileCache` expects:

* a database service
* a configuration service with the base URL
* writable cache directories
* remote resource access through the PHP runtime

The current implementation is legacy infrastructure and uses `ServiceLocator` internally.

New consuming code should still depend on `ICache`, not on `FileCache` internals.

---

## 8. `DelegateCacheMicroservice`

The framework also contains:

```php
Base3\Cache\DelegateCacheMicroservice
```

It extends `AbstractMicroservice` and implements `ICache`.

Its purpose is to expose a configured cache service through the BASE3 microservice mechanism.

This is a delegation endpoint, not a second cache model.

See `microservices.md`.

---

## 9. Important current implementation behavior

The current code contains legacy return-shape behavior worth knowing when integrating directly:

* `FileCache::getCacheUrls()` returns an associative result keyed by original URL
* `FileCache::getCacheUrl()` currently forwards to `getCacheUrls()` and therefore returns that collection shape rather than extracting one scalar URL
* `DelegateCacheMicroservice::getCacheUrls()` currently delegates to `getCacheUrl()`

These are current implementation details, not stronger guarantees that should be copied into new APIs.

If stricter single-versus-many semantics are required, correct the responsible implementation boundary rather than introducing a compensating wrapper in consumers.

---

## 10. Cache versus State Store

Use this cache for cached URL resources.

Use `IStateStore` for operational truth such as:

* locks
* cursors
* timestamps
* checkpoints

A cached external file may be regenerated. Operational state may directly control behavior and must not be treated as disposable cache content.

---

## 11. Cache versus browser cache

`ICache` does not manage browser cache headers.

It materializes externally addressed resources into a BASE3-controlled cache location.

HTTP cache-control policy is a separate concern.

---

## 12. Dependency guidance

When a plugin needs this capability, type-hint:

```php
Base3\Cache\Api\ICache
```

Do not depend on the database table or generated path structure unless your plugin explicitly owns that concrete backend.

---

## 13. Summary

The current BASE3 cache subsystem is specialized:

```text
ICache
  URL resource cache contract

FileCache
  database-indexed local file cache

DelegateCacheMicroservice
  remote/delegated exposure of a cache service
```

It is not intended as the framework's general state or settings store.
