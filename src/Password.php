<?php

declare(strict_types=1);

namespace TimeFrontiers\Data;

/**
 * Modern password hashing using Argon2ID (preferred) or bcrypt (fallback).
 *
 * Usage:
 *   $hash = Password::hash('secret123');
 *   $valid = Password::verify('secret123', $hash);
 *
 *   // Check if rehash needed (algorithm/cost changed)
 *   if (Password::needsRehash($hash)) {
 *     $new_hash = Password::hash($password);
 *     // Update in database
 *   }
 */
class Password {

  // Argon2ID options (PHP 7.3+)
  public const ALGO_ARGON2ID = PASSWORD_ARGON2ID;
  public const ALGO_ARGON2I = PASSWORD_ARGON2I;
  public const ALGO_BCRYPT = PASSWORD_BCRYPT;

  private static int $_algorithm = PASSWORD_ARGON2ID;
  private static array $_options = [];

  /**
   * Configure the hashing algorithm and options.
   *
   * For Argon2ID (default):
   *   Password::configure(Password::ALGO_ARGON2ID, [
   *     'memory_cost' => PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
   *     'time_cost' => PASSWORD_ARGON2_DEFAULT_TIME_COST,
   *     'threads' => PASSWORD_ARGON2_DEFAULT_THREADS,
   *   ]);
   *
   * For bcrypt:
   *   Password::configure(Password::ALGO_BCRYPT, ['cost' => 12]);
   */
  public static function configure(int $algorithm, array $options = []):void {
    self::$_algorithm = $algorithm;
    self::$_options = $options;
  }

  /**
   * Hash a password.
   *
   * @param string $password Plain text password
   * @return string Password hash
   * @throws \RuntimeException If hashing fails
   */
  public static function hash(string $password):string {
    $hash = \password_hash($password, self::$_algorithm, self::$_options);

    if ($hash === false) {
      throw new \RuntimeException('Password hashing failed');
    }

    return $hash;
  }

  /**
   * Verify a password against a hash.
   *
   * @param string $password Plain text password to check
   * @param string $hash Stored hash to verify against
   * @return bool True if password matches
   */
  public static function verify(string $password, string $hash):bool {
    return \password_verify($password, $hash);
  }

  /**
   * Check if a hash needs to be rehashed.
   *
   * Call this on successful login to upgrade old hashes
   * when algorithm or cost parameters change.
   *
   * @param string $hash The hash to check
   * @return bool True if rehash is recommended
   */
  public static function needsRehash(string $hash):bool {
    return \password_needs_rehash($hash, self::$_algorithm, self::$_options);
  }

  /**
   * Get information about a hash.
   *
   * @param string $hash The hash to analyze
   * @return array Hash info (algo, algoName, options)
   */
  public static function getInfo(string $hash):array {
    return \password_get_info($hash);
  }

  /**
   * Verify password and rehash if needed (convenience method).
   *
   * Returns [verified: bool, new_hash: ?string]
   * If new_hash is not null, update it in your database.
   *
   * @param string $password Plain text password
   * @param string $hash Current stored hash
   * @return array{verified: bool, new_hash: ?string}
   */
  public static function verifyAndRehash(string $password, string $hash):array {
    $verified = self::verify($password, $hash);

    if ($verified && self::needsRehash($hash)) {
      return [
        'verified' => true,
        'new_hash' => self::hash($password),
      ];
    }

    return [
      'verified' => $verified,
      'new_hash' => null,
    ];
  }
}
