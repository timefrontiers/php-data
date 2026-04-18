# TimeFrontiers PHP Data

PHP data utilities — encryption, password hashing, random generation, and more.

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

## Installation

```bash
composer require timefrontiers/php-data
```

## Requirements

- PHP 8.1+
- ext-openssl

## Quick Start

```php
use TimeFrontiers\Data\{Encryption, Password, Random, Signer};

// Encryption
$enc = new Encryption('/secure/path/my.key');
$encrypted = $enc->encrypt('sensitive data');
$decrypted = $enc->decrypt($encrypted);

// Password hashing
$hash = Password::hash('secret123');
$valid = Password::verify('secret123', $hash);

// Random strings
$token = Random::alphanumeric(32);
$code = Random::numeric(6);

// String signing
Signer::setKey('your-secret-key');
$signed = Signer::sign('user_id=123');
$original = Signer::verify($signed);
```

## Classes

| Class | Purpose |
|-------|---------|
| `Encryption` | AES-256-CBC encryption/decryption |
| `Password` | Modern password hashing (Argon2ID) |
| `Random` | Cryptographically secure random generation |
| `Signer` | HMAC-SHA256 string signing |
| `ByteConverter` | Size unit conversions |

> String manipulation, HTTP responses, and phone utilities now live in
> [`timefrontiers/php-core`](../php-core) (`Str`, `Http\Http`, `Phone`). This
> package is focused strictly on data-manipulation primitives.

---

## Encryption

### Key Configuration

```php
use TimeFrontiers\Data\Encryption;

// Option 1: Provide key file path
$enc = new Encryption('/secure/path/encryption.key');

// Option 2: Provide raw base64 key
$enc = new Encryption('base64-encoded-key-here');
```

A key **must** be supplied. There is no implicit fallback path — calling
`new Encryption()` without a prior `setKeyFile()` will throw.

### Static Configuration

```php
// Configure once at bootstrap
Encryption::setKeyFile('/secure/path/encryption.key');

// Then use static methods anywhere
$encrypted = Encryption::enc($data);
$decrypted = Encryption::dec($encrypted);
```

### Usage

```php
$enc = new Encryption($key_file);

// Basic encrypt/decrypt
$encrypted = $enc->encrypt('secret data');
$decrypted = $enc->decrypt($encrypted);

// URL-safe (base64 encoded)
$encoded = $enc->encodeEncrypt('secret data');
$decoded = $enc->decodeDecrypt($encoded);

// Per-operation key override
$encrypted = $enc->encrypt($data, '/different/key/file');
$encrypted = $enc->encrypt($data, 'different-raw-key');

// Generate new key
$new_key = Encryption::generateKey();
```

### Key File Format

Key files should contain a base64-encoded 32-byte key:

```
Th1sIs4Base64EncodedKeyOf32Bytes==
```

Generate one with:

```php
file_put_contents('/secure/path/my.key', Encryption::generateKey());
chmod('/secure/path/my.key', 0600);
```

---

## Password

### Modern Usage (Argon2ID)

```php
use TimeFrontiers\Data\Password;

// Hash a password
$hash = Password::hash('secret123');

// Verify
if (Password::verify('secret123', $hash)) {
  // Valid!
}

// Check if rehash needed (algorithm/cost changed)
if (Password::needsRehash($hash)) {
  $new_hash = Password::hash($password);
  // Update in database
}

// Or use convenience method
$result = Password::verifyAndRehash($password, $stored_hash);
if ($result['verified']) {
  if ($result['new_hash']) {
    // Update database with $result['new_hash']
  }
}
```

### Configuration

```php
// Use bcrypt instead of Argon2ID
Password::configure(Password::ALGO_BCRYPT, ['cost' => 12]);

// Argon2ID with custom options
Password::configure(Password::ALGO_ARGON2ID, [
  'memory_cost' => 65536,
  'time_cost' => 4,
  'threads' => 3,
]);
```

---

## Random

All methods use `random_bytes()` / `random_int()` (CSPRNG).

```php
use TimeFrontiers\Data\Random;

// Strings
$alphanumeric = Random::alphanumeric(16);       // "Kj7mNp2XaB9cD4eF"
$numeric = Random::numeric(6);                   // "847293"
$hex = Random::hex(32);                          // "a1b2c3d4..."
$base64 = Random::base64(32);                    // URL-safe base64

// Without ambiguous chars (0/O, 1/I/l)
$unambiguous = Random::alphanumeric(8, false);  // "Kj7mNp2X"

// Custom character set
$custom = Random::string(10, 'ACGT');           // "ACGTACGTAC"

// Case-specific
$lower = Random::lowercase(8);
$upper = Random::uppercase(8);

// UUID v4
$uuid = Random::uuid();  // "550e8400-e29b-41d4-a716-446655440000"

// Unique ID with prefix
$id = Random::uniqueId('usr', 12);  // "usr_Kj7mNp2XaB9c"

// Random integer
$num = Random::int(1, 100);

// Pick from array
$item = Random::pick(['red', 'green', 'blue']);

// Secure shuffle
$shuffled = Random::shuffle($array);
```

---

## Signer

Sign strings to detect tampering (URLs, cookies, form data).

```php
use TimeFrontiers\Data\Signer;

// Configure once at bootstrap
Signer::setKey('your-secret-key');

// Sign
$signed = Signer::sign('user_id=123');
// Returns: "user_id=123--a1b2c3d4e5f6..."

// Verify
$original = Signer::verify($signed);
// Returns: "user_id=123" or false if tampered

// Check validity
if (Signer::isValid($signed)) {
  // Signature is valid
}

// Per-operation key
$signed = Signer::sign($data, 'different-key');
```

---

## ByteConverter

```php
use TimeFrontiers\Data\ByteConverter;

// Convert to bytes
$bytes = ByteConverter::toBytes(10, 'mb');      // 10485760
$bytes = ByteConverter::mbToBytes(10);          // 10485760

// Convert from bytes
$mb = ByteConverter::fromBytes(10485760, 'mb'); // 10.0
$mb = ByteConverter::bytesToMb(10485760);       // 10.0

// Convert between units
$gb = ByteConverter::convert(1024, 'mb', 'gb'); // 1.0

// Human-readable format
ByteConverter::format(1536000);                 // "1.46 MB"

// Parse string
ByteConverter::parse('10 MB');                  // 10485760
ByteConverter::parse('1.5 GB');                 // 1610612736
```

---

## Security defaults

| Primitive | Algorithm |
|-----------|-----------|
| Random generation | `random_int()` / `random_bytes()` (CSPRNG) |
| String signing | HMAC-SHA256 |
| Password hashing | `password_hash()` with Argon2ID (bcrypt configurable) |
| Encryption | AES-256-CBC with random IV per message |

## License

[MIT License](LICENSE)
