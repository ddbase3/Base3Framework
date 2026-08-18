# BASE3 Token System

## Purpose

This document explains the BASE3 token abstraction and its built-in file-backed implementation.

It covers:

* the `IToken` contract
* token scopes and entity ids
* token lifetime
* creation, validation, deletion, and cleanup
* `FileToken`
* `TokenProxy`
* how authentication and SSO use tokens
* the distinction between the public token contract and extra implementation methods

---

## 1. What a BASE3 token is

A token is a temporary secret associated with:

```text
scope + id
```

Example:

```text
scope: password-reset
id: 42
```

The token service can create one or more temporary token values for that pair and later validate or remove them.

Typical uses include:

* browser authentication persistence
* single sign-on handoff
* password reset links
* email confirmation
* one-time validation flows

---

## 2. `IToken`

The framework contract is:

```php
interface IToken {

	public function create($scope, $id, $size = 32, $duration = 3600);

	public function check($scope, $id, $token);

	public function delete($scope, $id, $token);

	public function clean($scope, $id);
}
```

The interface is intentionally storage-neutral.

Consumers should not assume that tokens are stored in files, a database, or another service.

---

## 3. Scope

The scope identifies the purpose of a token.

Examples used by current framework authentication code include:

```text
authentication
singlesignon
internalhmacauth
```

A plugin should use a stable scope specific to its use case.

Do not reuse an unrelated scope only because the token format happens to be compatible.

---

## 4. Entity id

The `id` identifies the subject inside the scope.

It may be a string or integer depending on the calling system.

Examples:

```text
user id
account key
request owner
external identity
```

The combination of scope and id is the logical storage bucket.

---

## 5. Creating a token

```php
$token = $tokens->create('password-reset', $userId, 32, 3600);
```

Arguments:

```text
scope
id
size
  token source size used by the implementation

duration
  lifetime in seconds
```

The caller receives the plaintext token that must be transferred to the party that will later present it.

The storage backend decides how the validation representation is stored.

---

## 6. Checking a token

```php
if (!$tokens->check('password-reset', $userId, $token)) {
	return;
}
```

`check()` only answers whether the provided value is currently valid for the scope and id.

It does not consume the token automatically.

For a one-time flow, explicitly delete it after successful validation:

```php
if ($tokens->check($scope, $id, $token)) {
	$tokens->delete($scope, $id, $token);
	// continue
}
```

`SingleSignOnAuth` uses this pattern.

---

## 7. Deleting and cleaning

Delete one token:

```php
$tokens->delete($scope, $id, $token);
```

Remove all tokens for the scope/id pair:

```php
$tokens->clean($scope, $id);
```

Use `clean()` when the whole token set for one logical subject must be invalidated.

---

## 8. `FileToken`

The built-in implementation is:

```php
Base3\Token\FileToken\FileToken
```

It stores token metadata below:

```text
DIR_LOCAL/FileToken/
```

The filename is derived from:

```text
sanitized scope + md5(id)
```

Each file contains JSON records with:

```text
token
  SHA-1 hash of the plaintext token

timeout
  absolute expiry timestamp
```

The plaintext token itself is not written to the token file.

---

## 9. Expiration handling in `FileToken`

When a token is created or saved, expired records in the same file are removed before the new record is written.

When checking or deleting, expired entries are ignored or removed from the in-memory record set.

The current implementation does not run a global cleanup job across all token files.

Expiration is therefore primarily enforced during access to a particular scope/id file.

---

## 10. Token generation in `FileToken`

The implementation generates random bytes with OpenSSL and returns a hexadecimal string:

```php
bin2hex(openssl_random_pseudo_bytes($size))
```

It stores only a hash of that returned token.

`FileToken` also implements `ICheck` so diagnostics can verify:

* OpenSSL availability
* writability of the token directory

---

## 11. Extra `FileToken::save()` method

`FileToken` currently exposes an additional method:

```php
save($scope, $id, $token, $duration = 3600)
```

This stores an externally supplied token value.

It is used by `InternalHmacAuth` to remember already-used request tokens.

Important:

```text
save() is not part of IToken
```

Reusable consumers that depend only on `IToken` must not assume that this method exists.

If externally supplied token persistence becomes a general framework requirement, the contract itself should be extended deliberately rather than consumers probing implementations at runtime.

---

## 12. `TokenProxy`

`TokenProxy` implements `IToken` and forwards all interface methods to a supplied connector object.

This supports remote or delegated token implementations while keeping consumers on the `IToken` API.

The proxy does not define additional policy. It is a forwarding adapter.

---

## 13. Authentication integration

Current authentication code uses tokens in several places.

### Persistent cookie authentication

`CookieAuth` stores:

```text
userid
token
```

in the browser cookie and validates the token through its configured token service.

### Single sign-on

`SingleSignOnServerAuth` creates a short-lived token in the `singlesignon` scope.

`SingleSignOnAuth` validates and consumes it.

### Internal microservice replay protection

`InternalHmacAuth` uses token storage to reject reuse of an internal request token.

See `accesscontrol-authentication.md` and `microservices.md`.

---

## 14. Tokens versus sessions

Tokens and sessions solve different problems.

```text
ISession
  browser/request session state

IToken
  scoped temporary secrets that can be presented later
```

A persistent-login cookie may combine both systems, but the abstractions remain separate.

---

## 15. Tokens versus ConfigValue secrets

`IToken` manages runtime-created temporary secrets.

`IConfigValueResolver` resolves configured values such as API keys or deployment secrets.

Do not store a long-lived configured API key as a temporary `IToken` record.

See `configvalue.md`.

---

## 16. Project wiring

Consumers should depend on `IToken`:

```php
public function __construct(
	private readonly IToken $tokens
) {}
```

The project chooses the implementation.

A file-backed implementation is suitable only when its filesystem and concurrency characteristics fit the deployment.

---

## 17. Summary

```text
IToken
  storage-neutral scoped token contract

FileToken
  local JSON-backed token implementation

TokenProxy
  forwarding adapter to another token implementation
```

Use stable scopes, explicit expiry, and explicit deletion for one-time flows.
