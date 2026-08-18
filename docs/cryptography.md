# BASE3 Cryptography Service

## Purpose

This document explains the small cryptography abstraction provided by BASE3.

It covers:

* the `ICrypt` contract
* the built-in `OpensslCrypt` implementation
* encryption format and secret handling in the current implementation
* diagnostics
* appropriate and inappropriate use cases

---

## 1. `ICrypt`

The framework contract is:

```php
namespace Base3\Crypt\Api;

interface ICrypt {

	public function encrypt(string $str, string $secret): string;

	public function decrypt(string $str, string $secret): string;
}
```

The abstraction is deliberately small.

It provides reversible string encryption using a caller-supplied secret.

---

## 2. Built-in implementation

BASE3 currently ships:

```php
Base3\Crypt\Openssl\OpensslCrypt
```

It uses:

```text
AES-256-CBC
```

through the PHP OpenSSL extension.

---

## 3. Encryption flow

The current implementation:

```text
secret
  -> SHA-256 hash used as key material

plaintext
  -> AES-256-CBC with random IV

result
  -> encrypted OpenSSL string + ":" + base64 encoded IV
```

Conceptually:

```php
$encrypted = $crypt->encrypt($plaintext, $secret);
```

The returned string contains both encrypted data and the IV needed for decryption.

---

## 4. Decryption flow

```php
$plaintext = $crypt->decrypt($encrypted, $secret);
```

The implementation splits the stored value at `:` and uses the supplied secret to reconstruct the key.

The caller must therefore retain the same secret used for encryption.

---

## 5. Diagnostics

`OpensslCrypt` implements `ICheck`.

Its dependency check reports whether the PHP OpenSSL extension is loaded.

This can be surfaced by the framework diagnostics described in `checks-diagnostics.md`.

---

## 6. Dependency injection

Consumers that only need reversible encryption should depend on:

```php
ICrypt
```

Example:

```php
final class ProtectedValueService {

	public function __construct(
		private readonly ICrypt $crypt
	) {}
}
```

The project decides which implementation fills the service slot.

---

## 7. What `ICrypt` does not define

The current contract does not define:

* secret storage
* key rotation
* key identifiers
* authenticated encryption semantics
* password hashing
* digital signatures
* public-key encryption
* token generation

Do not infer those guarantees from the existence of `ICrypt`.

---

## 8. Passwords are a different problem

Reversible encryption should not be used as a password hashing mechanism.

Password verification should use a dedicated password-hashing approach appropriate to the authentication backend.

`ICrypt` exists for values that must later be decrypted.

---

## 9. Configured secrets

A project may resolve the secret through a deployment-specific mechanism such as `IConfigValueResolver`.

That keeps secret source selection separate from encryption:

```text
IConfigValueResolver
  where does the secret come from?

ICrypt
  encrypt or decrypt using that secret
```

See `configvalue.md`.

---

## 10. Current implementation boundary

`OpensslCrypt` is the current framework implementation, but its wire format is not separately standardized by `ICrypt`.

If encrypted values need to survive a backend replacement, the project must deliberately preserve format compatibility or perform a migration.

Consumers should not parse the `ciphertext:iv` representation themselves.

---

## 11. Summary

```text
ICrypt
  reversible string encryption contract

OpensslCrypt
  AES-256-CBC implementation using PHP OpenSSL
```

Keep secret sourcing, password hashing, token storage, and other security concerns in their own architectural boundaries.
