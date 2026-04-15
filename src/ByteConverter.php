<?php

declare(strict_types=1);

namespace TimeFrontiers\Data;

/**
 * Byte size conversion utilities.
 */
class ByteConverter {

  public const BYTE = 1;
  public const KB = 1024;
  public const MB = 1048576;        // 1024 * 1024
  public const GB = 1073741824;     // 1024 * 1024 * 1024
  public const TB = 1099511627776;  // 1024 * 1024 * 1024 * 1024

  private const UNITS = [
    'b' => self::BYTE,
    'byte' => self::BYTE,
    'bytes' => self::BYTE,
    'kb' => self::KB,
    'kilobyte' => self::KB,
    'kilobytes' => self::KB,
    'mb' => self::MB,
    'megabyte' => self::MB,
    'megabytes' => self::MB,
    'gb' => self::GB,
    'gigabyte' => self::GB,
    'gigabytes' => self::GB,
    'tb' => self::TB,
    'terabyte' => self::TB,
    'terabytes' => self::TB,
  ];

  /**
   * Convert to bytes.
   *
   * @param int|float $value Value to convert
   * @param string $from Source unit (kb, mb, gb, tb)
   * @return int Bytes
   */
  public static function toBytes(int|float $value, string $from = 'mb'):int {
    $from = \strtolower($from);

    if (!isset(self::UNITS[$from])) {
      throw new \InvalidArgumentException("Unknown unit: {$from}");
    }

    return (int) ($value * self::UNITS[$from]);
  }

  /**
   * Convert from bytes to another unit.
   *
   * @param int $bytes Bytes to convert
   * @param string $to Target unit (kb, mb, gb, tb)
   * @param int $precision Decimal precision
   * @return float Converted value
   */
  public static function fromBytes(int $bytes, string $to = 'mb', int $precision = 2):float {
    $to = \strtolower($to);

    if (!isset(self::UNITS[$to])) {
      throw new \InvalidArgumentException("Unknown unit: {$to}");
    }

    return \round($bytes / self::UNITS[$to], $precision);
  }

  /**
   * Convert between units.
   *
   * @param int|float $value Value to convert
   * @param string $from Source unit
   * @param string $to Target unit
   * @param int $precision Decimal precision
   * @return float Converted value
   */
  public static function convert(int|float $value, string $from, string $to, int $precision = 2):float {
    $bytes = self::toBytes($value, $from);
    return self::fromBytes($bytes, $to, $precision);
  }

  /**
   * Format bytes to human-readable string.
   *
   * @param int $bytes Bytes to format
   * @param int $precision Decimal precision
   * @return string Formatted string (e.g., "1.5 GB")
   */
  public static function format(int $bytes, int $precision = 2):string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    $bytes = \max($bytes, 0);
    $pow = $bytes > 0 ? \floor(\log($bytes, 1024)) : 0;
    $pow = \min($pow, \count($units) - 1);

    $value = $bytes / (1024 ** $pow);

    return \round($value, $precision) . ' ' . $units[$pow];
  }

  /**
   * Parse a size string (e.g., "10MB", "1.5 GB") to bytes.
   *
   * @param string $size Size string
   * @return int Bytes
   */
  public static function parse(string $size):int {
    $size = \trim($size);

    if (\preg_match('/^([\d.]+)\s*([a-zA-Z]+)$/', $size, $matches)) {
      $value = (float) $matches[1];
      $unit = \strtolower($matches[2]);

      return self::toBytes($value, $unit);
    }

    // Assume bytes if no unit
    if (\is_numeric($size)) {
      return (int) $size;
    }

    throw new \InvalidArgumentException("Cannot parse size: {$size}");
  }

  /**
   * Compare two sizes.
   *
   * @param string|int $size1 First size (bytes or string like "10MB")
   * @param string|int $size2 Second size
   * @return int -1 if size1 < size2, 0 if equal, 1 if size1 > size2
   */
  public static function compare(string|int $size1, string|int $size2):int {
    $bytes1 = \is_string($size1) ? self::parse($size1) : $size1;
    $bytes2 = \is_string($size2) ? self::parse($size2) : $size2;

    return $bytes1 <=> $bytes2;
  }

  // === Convenience methods ===

  public static function kbToBytes(int|float $kb):int {
    return self::toBytes($kb, 'kb');
  }

  public static function mbToBytes(int|float $mb):int {
    return self::toBytes($mb, 'mb');
  }

  public static function gbToBytes(int|float $gb):int {
    return self::toBytes($gb, 'gb');
  }

  public static function bytesToKb(int $bytes, int $precision = 2):float {
    return self::fromBytes($bytes, 'kb', $precision);
  }

  public static function bytesToMb(int $bytes, int $precision = 2):float {
    return self::fromBytes($bytes, 'mb', $precision);
  }

  public static function bytesToGb(int $bytes, int $precision = 2):float {
    return self::fromBytes($bytes, 'gb', $precision);
  }
}
