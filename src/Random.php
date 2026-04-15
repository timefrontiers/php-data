<?php

declare(strict_types=1);

namespace TimeFrontiers\Data;

/**
 * Cryptographically secure random string generation.
 *
 * All methods use random_bytes() / random_int() which are CSPRNG-backed.
 *
 * Usage:
 *   $code = Random::alphanumeric(8);      // "Kj7mNp2X"
 *   $hex = Random::hex(16);               // "a1b2c3d4e5f6..."
 *   $token = Random::base64(32);          // URL-safe base64
 *   $digits = Random::numeric(6);         // "847293"
 */
class Random {

  // Character sets
  public const LOWERCASE = 'abcdefghijklmnopqrstuvwxyz';
  public const UPPERCASE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  public const LETTERS = self::LOWERCASE . self::UPPERCASE;
  public const DIGITS = '0123456789';
  public const ALPHANUMERIC = self::LETTERS . self::DIGITS;

  // Exclude ambiguous characters (0/O, 1/I/l)
  public const UNAMBIGUOUS_LOWER = 'abcdefghjkmnpqrstuvwxyz';
  public const UNAMBIGUOUS_UPPER = 'ABCDEFGHJKMNPQRSTUVWXYZ';
  public const UNAMBIGUOUS_DIGITS = '23456789';
  public const UNAMBIGUOUS = self::UNAMBIGUOUS_LOWER . self::UNAMBIGUOUS_UPPER . self::UNAMBIGUOUS_DIGITS;

  /**
   * Generate random bytes.
   *
   * @param int $length Number of bytes
   * @return string Raw binary string
   */
  public static function bytes(int $length):string {
    return \random_bytes($length);
  }

  /**
   * Generate random hex string.
   *
   * @param int $length Length of output (each byte = 2 hex chars)
   * @return string Hex-encoded string
   */
  public static function hex(int $length = 32):string {
    $bytes = (int) \ceil($length / 2);
    return \substr(\bin2hex(\random_bytes($bytes)), 0, $length);
  }

  /**
   * Generate URL-safe base64 string.
   *
   * @param int $length Approximate length (actual may vary slightly)
   * @return string Base64-encoded string (URL-safe)
   */
  public static function base64(int $length = 32):string {
    $bytes = (int) \ceil($length * 0.75);
    $base64 = \base64_encode(\random_bytes($bytes));

    // Make URL-safe
    $base64 = \strtr($base64, '+/', '-_');
    $base64 = \rtrim($base64, '=');

    return \substr($base64, 0, $length);
  }

  /**
   * Generate random string from character set.
   *
   * @param int $length Output length
   * @param string $chars Character set to use
   * @return string Random string
   */
  public static function string(int $length, string $chars = self::ALPHANUMERIC):string {
    $max = \strlen($chars) - 1;
    $result = '';

    for ($i = 0; $i < $length; $i++) {
      $result .= $chars[\random_int(0, $max)];
    }

    return $result;
  }

  /**
   * Generate alphanumeric string.
   *
   * @param int $length Output length
   * @param bool $ambiguous Include ambiguous chars (0/O, 1/I/l)
   * @return string Random alphanumeric string
   */
  public static function alphanumeric(int $length = 16, bool $ambiguous = true):string {
    $chars = $ambiguous ? self::ALPHANUMERIC : self::UNAMBIGUOUS;
    return self::string($length, $chars);
  }

  /**
   * Generate numeric string (digits only).
   *
   * @param int $length Output length
   * @return string Random numeric string
   */
  public static function numeric(int $length = 6):string {
    return self::string($length, self::DIGITS);
  }

  /**
   * Generate lowercase string.
   *
   * @param int $length Output length
   * @param bool $ambiguous Include ambiguous chars
   * @return string Random lowercase string
   */
  public static function lowercase(int $length = 16, bool $ambiguous = true):string {
    $chars = $ambiguous ? self::LOWERCASE : self::UNAMBIGUOUS_LOWER;
    return self::string($length, $chars);
  }

  /**
   * Generate uppercase string.
   *
   * @param int $length Output length
   * @param bool $ambiguous Include ambiguous chars
   * @return string Random uppercase string
   */
  public static function uppercase(int $length = 16, bool $ambiguous = true):string {
    $chars = $ambiguous ? self::UPPERCASE : self::UNAMBIGUOUS_UPPER;
    return self::string($length, $chars);
  }

  /**
   * Generate mixed case with numbers.
   *
   * @param int $length Output length
   * @param bool $ambiguous Include ambiguous chars
   * @return string Random mixed string
   */
  public static function mixed(int $length = 16, bool $ambiguous = true):string {
    return self::alphanumeric($length, $ambiguous);
  }

  /**
   * Generate a unique ID with optional prefix.
   *
   * @param string $prefix Prefix to prepend
   * @param int $length Random portion length
   * @return string Unique ID
   */
  public static function uniqueId(string $prefix = '', int $length = 16):string {
    $random = self::alphanumeric($length, false);
    return $prefix !== '' ? $prefix . '_' . $random : $random;
  }

  /**
   * Generate a UUID v4.
   *
   * @return string UUID in format xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
   */
  public static function uuid():string {
    $bytes = \random_bytes(16);

    // Set version to 4
    $bytes[6] = \chr(\ord($bytes[6]) & 0x0f | 0x40);
    // Set variant to RFC 4122
    $bytes[8] = \chr(\ord($bytes[8]) & 0x3f | 0x80);

    return \sprintf(
      '%08s-%04s-%04s-%04s-%012s',
      \bin2hex(\substr($bytes, 0, 4)),
      \bin2hex(\substr($bytes, 4, 2)),
      \bin2hex(\substr($bytes, 6, 2)),
      \bin2hex(\substr($bytes, 8, 2)),
      \bin2hex(\substr($bytes, 10, 6))
    );
  }

  /**
   * Generate random integer in range.
   *
   * @param int $min Minimum value (inclusive)
   * @param int $max Maximum value (inclusive)
   * @return int Random integer
   */
  public static function int(int $min = 0, int $max = PHP_INT_MAX):int {
    return \random_int($min, $max);
  }

  /**
   * Pick random element from array.
   *
   * @param array $array Array to pick from
   * @return mixed Random element
   */
  public static function pick(array $array):mixed {
    if (empty($array)) {
      throw new \InvalidArgumentException('Cannot pick from empty array');
    }

    $keys = \array_keys($array);
    $index = \random_int(0, \count($keys) - 1);

    return $array[$keys[$index]];
  }

  /**
   * Shuffle array (cryptographically secure).
   *
   * @param array $array Array to shuffle
   * @return array Shuffled array
   */
  public static function shuffle(array $array):array {
    $keys = \array_keys($array);
    $count = \count($keys);

    // Fisher-Yates shuffle with random_int
    for ($i = $count - 1; $i > 0; $i--) {
      $j = \random_int(0, $i);
      [$keys[$i], $keys[$j]] = [$keys[$j], $keys[$i]];
    }

    $shuffled = [];
    foreach ($keys as $key) {
      $shuffled[$key] = $array[$key];
    }

    return $shuffled;
  }
}
