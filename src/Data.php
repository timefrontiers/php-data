<?php

declare(strict_types=1);

namespace TimeFrontiers\Data;

/**
 * Legacy facade for backward compatibility.
 *
 * This class wraps the new modular classes to maintain backward compatibility
 * with existing code using TymFrontiers\Data.
 *
 * NEW CODE SHOULD USE THE INDIVIDUAL CLASSES:
 *   - Encryption::encrypt() instead of Data::encrypt()
 *   - Password::hash() instead of Data::pwdHash()
 *   - Random::alphanumeric() instead of Data::uniqueRand()
 *   - Signer::sign() instead of Data::signString()
 *   - etc.
 *
 * @deprecated Use individual classes instead
 */
class Data {

  // Random constants (for backward compatibility)
  public const RAND_LOWERCASE = 'lowercase';
  public const RAND_UPPERCASE = 'uppercase';
  public const RAND_NUMBERS = 'numbers';
  public const RAND_MIXED = 'mixed';
  public const RAND_MIXED_UPPER = 'mixedupper';
  public const RAND_MIXED_LOWER = 'mixedlower';

  private static ?Encryption $_encryption = null;

  public array $errors = [];

  public function __construct() {
    // Initialize encryption with legacy key location
    self::_initEncryption();
  }

  // === Configuration ===

  /**
   * Set the encryption key file location.
   * Call once at bootstrap for legacy static methods.
   */
  public static function setKeyFile(string $key_or_file):void {
    Encryption::setKeyFile($key_or_file);
    self::$_encryption = null; // Reset cached instance
  }

  /**
   * Set the signing key.
   */
  public static function setSigningKey(string $key):void {
    Signer::setKey($key);
  }

  // === Encryption ===

  /**
   * @deprecated Use Encryption::encrypt()
   */
  public static function encrypt(string $data, string $enc_key = ''):string {
    self::_initEncryption();
    $key = !empty($enc_key) ? $enc_key : null;
    return self::$_encryption->encrypt($data, $key);
  }

  /**
   * @deprecated Use Encryption::decrypt()
   */
  public static function decrypt(string $data, string $enc_key = ''):?string {
    self::_initEncryption();
    $key = !empty($enc_key) ? $enc_key : null;
    return self::$_encryption->decrypt($data, $key);
  }

  /**
   * @deprecated Use Encryption::encodeEncrypt()
   */
  public static function encodeEncrypt(string $data, string $enc_key = ''):string {
    self::_initEncryption();
    $key = !empty($enc_key) ? $enc_key : null;
    return self::$_encryption->encodeEncrypt($data, $key);
  }

  /**
   * @deprecated Use Encryption::decodeDecrypt()
   */
  public static function decodeDecrypt(string $data, string $enc_key = ''):?string {
    self::_initEncryption();
    $key = !empty($enc_key) ? $enc_key : null;
    return self::$_encryption->decodeDecrypt($data, $key);
  }

  /**
   * @deprecated Use Encryption::keyBackup()
   */
  public static function keyBackup():?string {
    return Encryption::keyBackup();
  }

  /**
   * @deprecated Use Encryption::setKeyFile()
   */
  public static function restoreKey(string $key):void {
    Encryption::setKeyFile($key);
  }

  // === Signing ===

  /**
   * @deprecated Use Signer::sign() with HMAC
   */
  public static function signString(string $string):string {
    // Use legacy SHA1 signing for backward compatibility
    return Signer::legacySign($string);
  }

  /**
   * @deprecated Use Signer::verify() with HMAC
   */
  public static function isSignString(string $signed_string):string|false {
    // Use legacy SHA1 verification for backward compatibility
    return Signer::legacyVerify($signed_string);
  }

  // === Password ===

  /**
   * @deprecated Use Password::hash()
   */
  public static function pwdHash(string $password):string {
    // Use legacy bcrypt for backward compatibility with existing hashes
    return Password::legacyHash($password);
  }

  /**
   * @deprecated Use Password::verify()
   */
  public static function pwdCheck(string $password, string $hash):bool {
    // password_verify handles both legacy crypt and modern hashes
    return Password::verify($password, $hash);
  }

  // === Random ===

  /**
   * @deprecated Use Random::alphanumeric()
   */
  public static function uniqueRand(
    string $salt = '',
    int $len = 6,
    string $case = 'mixed',
    bool $asn = true,
    string $dbase = '',
    string $tbl = '',
    string $col = ''
  ):string {
    // Build character set based on case
    $chars = match ($case) {
      'lowercase' => Random::LOWERCASE,
      'uppercase' => Random::UPPERCASE,
      'numbers' => Random::DIGITS,
      'mixedupper' => Random::UPPERCASE . Random::DIGITS,
      'mixedlower' => Random::LOWERCASE . Random::DIGITS,
      default => !empty($salt) ? $salt : Random::ALPHANUMERIC,
    };

    // Remove ambiguous characters if requested
    if (!$asn) {
      $chars = \str_replace(['0', 'o', 'O', 'i', '1', 'I', 'l'], '', $chars);
    }

    $code = Random::string($len, $chars);

    // Database uniqueness check (requires MultiForm - legacy behavior)
    if (!empty($dbase) && !empty($tbl) && !empty($col)) {
      if (\class_exists('\TimeFrontiers\MultiForm')) {
        $form = new \TimeFrontiers\MultiForm($dbase, $tbl);
        $exists = $form->findBySql("SELECT * FROM :db:.:tbl: WHERE `{$col}` = '{$code}' LIMIT 1");
        if (!empty($exists)) {
          return self::uniqueRand($salt, $len, $case, $asn, $dbase, $tbl, $col);
        }
      }
    }

    return $code;
  }

  /**
   * @deprecated Use Random::numeric()
   */
  public static function genCode(int $len = 5):string {
    return Random::numeric($len);
  }

  /**
   * @deprecated Use Random::alphanumeric()
   */
  public static function genAlnumeric(string $chars = '', int $len = 6):string {
    if (!empty($chars) && \strlen($chars) > $len) {
      return Random::string($len, $chars);
    }
    return Random::alphanumeric($len, false);
  }

  /**
   * @deprecated Use Random::base64()
   */
  public static function genSalt(int $len):string {
    $base64 = Random::base64($len);
    return \str_replace(['-', '_'], ['+', '/'], $base64);
  }

  // === Text ===

  /**
   * @deprecated Use Text::truncate()
   */
  public static function getLen(string $text = '', int $len = 0):string {
    $len = $len > 5 ? $len : 150;
    return Text::truncate($text, $len, ' ..');
  }

  /**
   * @deprecated Use Text::chunk()
   */
  public static function charSplit(string $char, int $len = 3, string $split_str = '-'):string {
    return Text::chunk($char, $len, $split_str);
  }

  // === Byte Conversion ===

  /**
   * @deprecated Use ByteConverter::toBytes()
   */
  public function toByte(int $val = 1, string $from = 'mb'):int {
    return ByteConverter::toBytes($val, $from);
  }

  /**
   * @deprecated Use ByteConverter::fromBytes()
   */
  public function fromByte(int $bytes, string $to = 'mb'):float {
    return ByteConverter::fromBytes($bytes, $to);
  }

  // === Output ===

  /**
   * @deprecated Use Output::json()
   */
  public static function outprint(mixed $data, string $method = 'json', string $wrapper = '', bool $echo = true):?string {
    if ($method === 'json') {
      if (!empty($wrapper)) {
        return Output::jsonp($data, $wrapper, !$echo);
      }
      return Output::json($data, !$echo);
    }

    $output = \print_r($data, true);
    if ($echo) {
      echo $output;
      return null;
    }
    return $output;
  }

  /**
   * @deprecated Use Output::writeOut()
   */
  public static function writeOut(
    int $error_type,
    string $error_message,
    array $errors = [],
    array $more_prop_val = [],
    string $output_type = 'json',
    bool $return = false
  ):?string {
    return Output::writeOut($error_type, $error_message, $errors, $more_prop_val, $output_type, $return);
  }

  // === Utilities ===

  /**
   * Generate new encryption key.
   */
  public function encKey():string {
    return Encryption::generateKey();
  }

  // === Private ===

  private static function _initEncryption():void {
    if (self::$_encryption === null) {
      self::$_encryption = new Encryption();
    }
  }
}
