<?php

declare(strict_types=1);

namespace TimeFrontiers\Data;

/**
 * String signing using HMAC-SHA256.
 *
 * Used to detect tampering in data that travels through untrusted channels
 * (URLs, cookies, hidden form fields, etc).
 *
 * Usage:
 *   // Configure once at bootstrap
 *   Signer::setKey('your-secret-key');
 *
 *   // Sign a string
 *   $signed = Signer::sign('user_id=123');
 *   // Returns: "user_id=123--a1b2c3d4..."
 *
 *   // Verify and extract
 *   $original = Signer::verify($signed);
 *   // Returns: "user_id=123" or false if tampered
 */
class Signer {

  private const SEPARATOR = '--';
  private const ALGORITHM = 'sha256';

  private static ?string $_key = null;

  /**
   * Set the signing key.
   * Call once at bootstrap.
   *
   * @param string $key Secret key for HMAC signing
   */
  public static function setKey(string $key):void {
    if (empty($key)) {
      throw new \InvalidArgumentException('Signing key cannot be empty');
    }
    self::$_key = $key;
  }

  /**
   * Sign a string.
   *
   * @param string $data String to sign
   * @param string|null $key Optional key override
   * @return string Signed string (data--signature)
   */
  public static function sign(string $data, ?string $key = null):string {
    $key = $key ?? self::_getKey();
    $signature = \hash_hmac(self::ALGORITHM, $data, $key);

    return $data . self::SEPARATOR . $signature;
  }

  /**
   * Verify a signed string and return the original data.
   *
   * @param string $signed_string The signed string to verify
   * @param string|null $key Optional key override
   * @return string|false Original data if valid, false if tampered/invalid
   */
  public static function verify(string $signed_string, ?string $key = null):string|false {
    $key = $key ?? self::_getKey();

    $parts = \explode(self::SEPARATOR, $signed_string);
    if (\count($parts) !== 2) {
      return false;
    }

    [$data, $provided_signature] = $parts;

    $expected_signature = \hash_hmac(self::ALGORITHM, $data, $key);

    // Constant-time comparison
    if (\hash_equals($expected_signature, $provided_signature)) {
      return $data;
    }

    return false;
  }

  /**
   * Check if a string is validly signed (without extracting data).
   *
   * @param string $signed_string The signed string to check
   * @param string|null $key Optional key override
   * @return bool True if signature is valid
   */
  public static function isValid(string $signed_string, ?string $key = null):bool {
    return self::verify($signed_string, $key) !== false;
  }

  /**
   * Generate a secure random key for signing.
   *
   * @param int $length Key length in bytes (default 32 = 256 bits)
   * @return string Hex-encoded key
   */
  public static function generateKey(int $length = 32):string {
    return \bin2hex(\random_bytes($length));
  }

  /**
   * Get the configured key.
   */
  private static function _getKey():string {
    if (self::$_key === null) {
      throw new \RuntimeException(
        'Signing key not configured. Call Signer::setKey() at bootstrap.'
      );
    }

    return self::$_key;
  }
}
