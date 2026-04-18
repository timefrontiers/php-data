<?php

declare(strict_types=1);

namespace TimeFrontiers\Data;

/**
 * AES-256-CBC encryption with flexible key management.
 *
 * Key resolution priority:
 * 1. Constructor injection (raw key or file path)
 * 2. Static setKeyFile() configuration
 *
 * A key MUST be provided via one of these paths — there is no implicit
 * fallback location and no auto-generation.
 *
 * Usage:
 *   // Instance with file path or raw base64 key
 *   $enc = new Encryption('/secure/path/my.key');
 *   $encrypted = $enc->encrypt($data);
 *
 *   // Static configuration (set once at bootstrap)
 *   Encryption::setKeyFile('/secure/path/my.key');
 *   $encrypted = Encryption::enc($data);
 *
 *   // Generate a fresh key for a new project
 *   $key = Encryption::generateKey();
 */
class Encryption {

  private const CIPHER = 'aes-256-cbc';
  private const SEPARATOR = '::';

  private static ?string $_static_key = null;
  private static ?string $_static_key_file = null;

  private string $_key;

  /**
   * @param string|null $key Raw encryption key (base64) OR path to key file
   */
  public function __construct(?string $key = null) {
    $this->_key = $this->_resolveKey($key);
  }

  /**
   * Configure the key used by the static `enc()` / `dec()` helpers.
   * Call once at bootstrap. Accepts either a raw base64 key or a path
   * to a file containing one.
   */
  public static function setKeyFile(string $key_or_file):void {
    if (\file_exists($key_or_file) && \is_readable($key_or_file)) {
      self::$_static_key_file = $key_or_file;
      self::$_static_key = self::_readKeyFile($key_or_file);
    } else {
      // Treat as raw key
      self::$_static_key = $key_or_file;
      self::$_static_key_file = null;
    }
  }

  /**
   * Get currently configured key (for backup/migration).
   */
  public static function keyBackup():?string {
    return self::$_static_key;
  }

  /**
   * Encrypt data.
   *
   * @param string $data Data to encrypt
   * @param string|null $key Optional override key (raw or file path)
   * @return string Encrypted string (base64_data::iv)
   */
  public function encrypt(string $data, ?string $key = null):string {
    $enc_key = $key !== null ? $this->_resolveKey($key) : $this->_key;
    $decoded_key = \base64_decode($enc_key);

    $iv_length = \openssl_cipher_iv_length(self::CIPHER);
    $iv = \openssl_random_pseudo_bytes($iv_length);

    $encrypted = \openssl_encrypt($data, self::CIPHER, $decoded_key, 0, $iv);

    if ($encrypted === false) {
      throw new \RuntimeException('Encryption failed');
    }

    return $encrypted . self::SEPARATOR . \base64_encode($iv);
  }

  /**
   * Decrypt data.
   *
   * @param string $data Encrypted string (base64_data::iv)
   * @param string|null $key Optional override key (raw or file path)
   * @return string|null Decrypted string or null on failure
   */
  public function decrypt(string $data, ?string $key = null):?string {
    $enc_key = $key !== null ? $this->_resolveKey($key) : $this->_key;
    $decoded_key = \base64_decode($enc_key);

    $parts = \explode(self::SEPARATOR, $data);
    if (\count($parts) !== 2) {
      return null;
    }

    [$encrypted_data, $iv_base64] = $parts;
    $iv = \base64_decode($iv_base64);

    $decrypted = \openssl_decrypt($encrypted_data, self::CIPHER, $decoded_key, 0, $iv);

    return $decrypted === false ? null : $decrypted;
  }

  /**
   * Encrypt and base64 encode (URL-safe).
   */
  public function encodeEncrypt(string $data, ?string $key = null):string {
    return \base64_encode($this->encrypt($data, $key));
  }

  /**
   * Base64 decode and decrypt.
   */
  public function decodeDecrypt(string $data, ?string $key = null):?string {
    $decoded = \base64_decode($data);
    if ($decoded === false) {
      return null;
    }
    return $this->decrypt($decoded, $key);
  }

  /**
   * Generate a new encryption key.
   */
  public static function generateKey():string {
    return \base64_encode(\openssl_random_pseudo_bytes(32));
  }

  // === Static convenience methods (use the key set via setKeyFile) ===

  /**
   * Static encrypt (uses the key configured via setKeyFile).
   */
  public static function enc(string $data, ?string $key = null):string {
    $instance = new self();
    return $instance->encrypt($data, $key);
  }

  /**
   * Static decrypt (uses configured key).
   */
  public static function dec(string $data, ?string $key = null):?string {
    $instance = new self();
    return $instance->decrypt($data, $key);
  }

  // === Private methods ===

  /**
   * Resolve key from the explicit argument or static configuration.
   * Throws when no key is available — callers must configure one.
   */
  private function _resolveKey(?string $key):string {
    // 1. Explicit key provided (raw or file path)
    if ($key !== null) {
      if (\file_exists($key) && \is_readable($key)) {
        return self::_readKeyFile($key);
      }
      return $key; // Raw key
    }

    // 2. Static configuration
    if (self::$_static_key !== null) {
      return self::$_static_key;
    }

    throw new \RuntimeException(
      'Encryption key not configured. Pass a key to the constructor or call Encryption::setKeyFile() at bootstrap.'
    );
  }

  /**
   * Read and clean key from file.
   */
  private static function _readKeyFile(string $path):string {
    $content = \file_get_contents($path);
    if ($content === false) {
      throw new \RuntimeException("Cannot read key file: {$path}");
    }

    return \trim($content);
  }

  /**
   * Get the current key (instance).
   */
  public function getKey():string {
    return $this->_key;
  }
}
