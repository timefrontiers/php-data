<?php

declare(strict_types=1);

namespace TimeFrontiers\Data;

/**
 * AES-256-CBC encryption with flexible key management.
 *
 * Key resolution priority:
 * 1. Constructor injection (raw key or file path)
 * 2. Static setKeyFile() configuration
 * 3. Fallback to legacy location: PRJ_ROOT/.system/appdata/tymfrontiers-cdn/php-data/data.key
 * 4. Auto-generate if nothing exists
 *
 * Usage:
 *   // New style (instance)
 *   $enc = new Encryption('/secure/path/my.key');
 *   $encrypted = $enc->encrypt($data);
 *
 *   // Or with raw key
 *   $enc = new Encryption(key: $base64_key);
 *
 *   // Legacy style (static config)
 *   Encryption::setKeyFile('/secure/path/my.key');
 *   $encrypted = Encryption::enc($data);
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
   * Configure key file for static/legacy usage.
   * Call once at bootstrap.
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

  // === Static methods for legacy/facade usage ===

  /**
   * Static encrypt (uses configured key).
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
   * Resolve key from various sources.
   */
  private function _resolveKey(?string $key):string {
    // 1. Explicit key provided
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

    // 3. Fallback to legacy location
    return $this->_loadLegacyKey();
  }

  /**
   * Load key from legacy file location.
   */
  private function _loadLegacyKey():string {
    $prj_root = $this->_getProjectRoot();
    $key_file = $prj_root . '/.system/appdata/timefrontiers/php-data/data.key';

    if (\file_exists($key_file) && \is_readable($key_file)) {
      $key = self::_readKeyFile($key_file);
      self::$_static_key = $key;
      self::$_static_key_file = $key_file;
      return $key;
    }

    // Auto-generate
    return $this->_generateAndSaveKey($key_file);
  }

  /**
   * Generate a new key and save to file.
   */
  private function _generateAndSaveKey(string $key_file):string {
    $dir = \dirname($key_file);

    if (!\file_exists($dir)) {
      if (!\mkdir($dir, 0700, true)) {
        throw new \RuntimeException("Cannot create key directory: {$dir}");
      }
    }

    $key = self::generateKey();

    if (\file_put_contents($key_file, $key) === false) {
      throw new \RuntimeException("Cannot write key file: {$key_file}");
    }

    \chmod($key_file, 0600);

    self::$_static_key = $key;
    self::$_static_key_file = $key_file;

    return $key;
  }

  /**
   * Read and clean key from file.
   */
  private static function _readKeyFile(string $path):string {
    $content = \file_get_contents($path);
    if ($content === false) {
      throw new \RuntimeException("Cannot read key file: {$path}");
    }

    // Clean up (remove <?php if present, trim whitespace)
    $key = \trim(\str_replace('<?php', '', $content));

    return $key;
  }

  /**
   * Get project root directory.
   */
  private function _getProjectRoot():string {
    // Check for helper functions
    if (\function_exists('\Catali\get_constant')) {
      $root = \Catali\get_constant('PRJ_ROOT');
      if ($root) return $root;
    }

    if (\function_exists('\get_constant')) {
      $root = \get_constant('PRJ_ROOT');
      if ($root) return $root;
    }

    // Check constant directly
    if (\defined('PRJ_ROOT')) {
      return PRJ_ROOT;
    }

    throw new \RuntimeException(
      "PRJ_ROOT not defined. Either define PRJ_ROOT constant or provide key/key_file explicitly."
    );
  }

  /**
   * Get the current key (instance).
   */
  public function getKey():string {
    return $this->_key;
  }
}
