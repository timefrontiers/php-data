<?php

declare(strict_types=1);

namespace TimeFrontiers\Data;

/**
 * String manipulation utilities.
 */
class Text {

  /**
   * Truncate text to a maximum length at word boundary.
   *
   * @param string $text Text to truncate
   * @param int $max_length Maximum length
   * @param string $suffix Suffix to append if truncated
   * @return string Truncated text
   */
  public static function truncate(string $text, int $max_length = 150, string $suffix = '...'):string {
    if (\strlen($text) <= $max_length) {
      return $text;
    }

    // Find last space before max length
    $truncated = \substr($text, 0, $max_length);
    $last_space = \strrpos($truncated, ' ');

    if ($last_space !== false) {
      $truncated = \substr($truncated, 0, $last_space);
    }

    return $truncated . $suffix;
  }

  /**
   * Split string into chunks with separator.
   *
   * @param string $string String to split
   * @param int $chunk_size Characters per chunk
   * @param string $separator Separator between chunks
   * @return string Chunked string
   */
  public static function chunk(string $string, int $chunk_size = 3, string $separator = '-'):string {
    return \implode($separator, \str_split($string, $chunk_size));
  }

  /**
   * Convert string to slug (URL-safe).
   *
   * @param string $text Text to slugify
   * @param string $separator Word separator
   * @return string URL-safe slug
   */
  public static function slug(string $text, string $separator = '-'):string {
    // Convert to lowercase
    $text = \strtolower($text);

    // Replace non-alphanumeric with separator
    $text = \preg_replace('/[^a-z0-9]+/', $separator, $text);

    // Remove leading/trailing separators
    $text = \trim($text, $separator);

    // Remove duplicate separators
    $text = \preg_replace('/' . \preg_quote($separator, '/') . '+/', $separator, $text);

    return $text;
  }

  /**
   * Convert snake_case to camelCase.
   */
  public static function toCamelCase(string $string):string {
    return \lcfirst(self::toPascalCase($string));
  }

  /**
   * Convert snake_case to PascalCase.
   */
  public static function toPascalCase(string $string):string {
    return \str_replace(' ', '', \ucwords(\str_replace(['_', '-'], ' ', $string)));
  }

  /**
   * Convert camelCase/PascalCase to snake_case.
   */
  public static function toSnakeCase(string $string):string {
    return \strtolower(\preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
  }

  /**
   * Convert camelCase/PascalCase to kebab-case.
   */
  public static function toKebabCase(string $string):string {
    return \strtolower(\preg_replace('/(?<!^)[A-Z]/', '-$0', $string));
  }

  /**
   * Check if string starts with a given substring.
   */
  public static function startsWith(string $haystack, string $needle):bool {
    return \str_starts_with($haystack, $needle);
  }

  /**
   * Check if string ends with a given substring.
   */
  public static function endsWith(string $haystack, string $needle):bool {
    return \str_ends_with($haystack, $needle);
  }

  /**
   * Check if string contains a given substring.
   */
  public static function contains(string $haystack, string $needle):bool {
    return \str_contains($haystack, $needle);
  }

  /**
   * Mask a string (e.g., for displaying sensitive data).
   *
   * @param string $string String to mask
   * @param int $visible_start Visible characters at start
   * @param int $visible_end Visible characters at end
   * @param string $mask_char Character to use for masking
   * @return string Masked string
   */
  public static function mask(
    string $string,
    int $visible_start = 3,
    int $visible_end = 3,
    string $mask_char = '*'
  ):string {
    $length = \strlen($string);

    if ($length <= $visible_start + $visible_end) {
      return $string;
    }

    $start = \substr($string, 0, $visible_start);
    $end = \substr($string, -$visible_end);
    $mask_length = $length - $visible_start - $visible_end;

    return $start . \str_repeat($mask_char, $mask_length) . $end;
  }

  /**
   * Generate an excerpt from text.
   *
   * @param string $text Full text
   * @param int $length Excerpt length
   * @param string $suffix Suffix when truncated
   * @return string Excerpt
   */
  public static function excerpt(string $text, int $length = 200, string $suffix = '...'):string {
    // Strip HTML tags
    $text = \strip_tags($text);

    // Normalize whitespace
    $text = \preg_replace('/\s+/', ' ', $text);
    $text = \trim($text);

    return self::truncate($text, $length, $suffix);
  }

  /**
   * Count words in a string.
   */
  public static function wordCount(string $string):int {
    return \str_word_count($string);
  }

  /**
   * Limit string to a number of words.
   */
  public static function limitWords(string $string, int $limit = 50, string $suffix = '...'):string {
    $words = \preg_split('/\s+/', $string);

    if (\count($words) <= $limit) {
      return $string;
    }

    return \implode(' ', \array_slice($words, 0, $limit)) . $suffix;
  }
}
