# BASE3 Cryptography Service

## Purpose

BASE3 provides discoverable, reversible encryption implementations through the
framework-owned `Base3\Crypt\Api\ICrypt` contract. Consumers select a method
independently of their secret source, data storage, and authorization model.
Implementations shipped with BASE3 live in `Base3Framework/src/Crypt/`.

This contract is for server-side encryption, not password hashing, digital
signatures, or end-to-end encrypted collaboration.

## 1. `ICrypt` contract

`ICrypt` extends `IBase` and defines:

```php
namespace Base3\Crypt\Api;

use Base3\Api\IBase;

interface ICrypt extends IBase {

    public function getMethod(): string;

    public function encrypt(string $str, string $secret): string;

    public function decrypt(string $str, string $secret): string;
}
```

- `IBase::getName()` is a unique, lowercase class-map identifier, for example
  `sodiumcrypt`. The class map can instantiate a selected implementation by
  `ICrypt` interface and this name.
- `getMethod()` is the persistent, versioned ciphertext format identifier.
  A stored value must retain its method identifier so the matching
  implementation can be selected on subsequent reads.
- `encrypt()` and `decrypt()` accept caller-supplied secrets. Implementations
  must not resolve, log, persist, or return the secret.
- Invalid, unavailable, or unverifiable encrypted data must not silently fall
  back to plaintext. Compatibility limitations of legacy methods are noted
  below.

Adding `IBase` and `getMethod()` to `ICrypt` is a public-contract change.
Existing custom implementations need a stable `getName()` and `getMethod()`.
In the supplied BASE3 source, `OpensslCrypt` was the only existing concrete
implementation and has been updated accordingly.

## 2. Framework methods

| Class | `getName()` | `getMethod()` | Status |
|---|---|---|---|
| `Base3\Crypt\Openssl\OpensslCrypt` | `opensslcrypt` | `aes-256-cbc-legacy-v1` | Legacy compatibility; unauthenticated encryption |
| `Base3\Crypt\Sodium\SodiumCrypt` | `sodiumcrypt` | `xchacha20poly1305-v1` | Recommended for new encrypted values |

### OpenSSL: existing legacy representation

The existing implementation is left wire-format compatible:

1. Calculate the SHA-256 **hex string** of the supplied secret as OpenSSL key
   material, following the historical implementation.
2. Encrypt with AES-256-CBC and a random IV.
3. Store the OpenSSL base64 ciphertext, a colon, and the base64 IV.

`OpensslCrypt` does **not** authenticate ciphertext. An attacker with write
access to ciphertext may modify it without reliable detection. Preserve this
implementation to read data already encrypted in that format, not as the new
chat encryption default. Changing the method name does not modify old
ciphertext bytes.

### Sodium: authenticated encryption

`SodiumCrypt` uses PHP's Sodium extension with
`XChaCha20-Poly1305-IETF` authenticated encryption. Each encryption generates a
fresh 24-byte random nonce and an authentication tag. Its stable textual
format is `v1:` followed by strict base64 encoding of `nonce || ciphertext || tag`.
An incorrect secret, invalid version, corrupted payload, or failed integrity
check raises an exception. There is no plaintext fallback.

A caller-supplied high-entropy secret of **at least 32 bytes** is required.
The implementation uses libsodium BLAKE2b to derive a fixed-size encryption
key from that secret. This is not a slow password derivation function: use a
cryptographically random deployment secret, not a human password.

Example of generating a deployment secret:

```sh
php -r 'echo base64_encode(random_bytes(32)), PHP_EOL;'
```

Store the resulting string in a protected secret source. Keep its exact value
stable until every ciphertext encrypted with it has been re-encrypted or
permanently deleted.

## 3. Choosing an implementation

Discover framework implementations through `IClassMap` and the common `ICrypt`
contract, for example:

```php
$crypt = $classMap->getInstanceByInterfaceName(ICrypt::class, 'sodiumcrypt');

if ($crypt->getMethod() !== 'xchacha20poly1305-v1') {
    throw new \RuntimeException('Unexpected encryption method.');
}

$ciphertext = $crypt->encrypt($plaintext, $secret);
$plaintext = $crypt->decrypt($ciphertext, $secret);
```

The caller is responsible for keeping the method and key identifier with the
encrypted data. A database can hold several versions simultaneously, provided
an appropriate implementation and secret remain available for each version.
Changing a configured write method must not reinterpret stored ciphertext.

The configuration value `none` signifies **no encryption by the storage
consumer**. It is not an `ICrypt` implementation; unencrypted records must be
explicitly marked and must never be passed through a crypto implementation.

## 4. Secret storage and rotation

`IConfigValueResolver` already separates secret **source** from its **use**.
Supported modes include `env`, `file`, `configuration`, and `fixed`, with new
modes discoverable through `IConfigValueModeResolver`. Prefer an environment
secret or a protected file outside the webroot; restrict permissions, backup
access, and logs. Direct fixed values in editable settings are not recommended
for encryption keys.

`ICrypt` does not define a key registry, rotation schedule, `key_id`, or secret
storage. Consumers must keep a nonsecret key identifier alongside ciphertext,
resolve the matching historical secret at read time, and perform any
re-encryption through a separately controlled migration. If the key cannot be
resolved, decryption must fail explicitly; never substitute `none`.

## 5. Scope and limitations

- Adding a framework crypto method does **not** activate encryption in any
  module. Conversation storage remains unchanged until its owner explicitly
  adopts the contract. Optional encryption may default to `none`.
- Server-side encryption allows the authorized server to decrypt data; it is
  **not** end-to-end encryption. Sharing a chat requires separate server-side
  authorization, even if encrypted at rest.
- Encrypting only message `content` is insufficient if titles, opening
  messages, serialized payloads, logs, caches, or backups also contain personal
  information. Each storing module must inventory and protect its own copies.
- Encryption does not erase plaintext that existed previously in database
  backups or logs. It also does not satisfy a deletion request by itself.
- Encrypted data can exceed the length of its plaintext; consumers must size
  columns or binary payload storage accordingly.

See `configvalue.md` for the existing secret-source system and
`privacy-accessibility.md` for BASE3 privacy requirements.
