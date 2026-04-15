<?php

declare(strict_types=1);

namespace TimeFrontiers\Data;

/**
 * Output formatting utilities.
 */
class Output {

  /**
   * Output data as JSON.
   *
   * @param mixed $data Data to output
   * @param bool $return Return instead of echo
   * @param int $flags JSON encoding flags
   * @return string|null JSON string if $return is true
   */
  public static function json(mixed $data, bool $return = false, int $flags = 0):?string {
    $json = \json_encode($data, $flags);

    if ($return) {
      return $json;
    }

    echo $json;
    return null;
  }

  /**
   * Output data as JSONP (callback wrapped).
   *
   * @param mixed $data Data to output
   * @param string $callback Callback function name
   * @param bool $return Return instead of echo
   * @return string|null JSONP string if $return is true
   */
  public static function jsonp(mixed $data, string $callback, bool $return = false):?string {
    // Sanitize callback name
    if (!\preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $callback)) {
      throw new \InvalidArgumentException('Invalid callback name');
    }

    $json = \json_encode($data);
    $output = "{$callback}({$json})";

    if ($return) {
      return $output;
    }

    echo $output;
    return null;
  }

  /**
   * Create a standardized API response.
   *
   * @param bool $success Success status
   * @param string $message Response message
   * @param mixed $data Response data
   * @param array $errors Error details
   * @param array $meta Additional metadata
   * @return array Formatted response array
   */
  public static function response(
    bool $success,
    string $message = '',
    mixed $data = null,
    array $errors = [],
    array $meta = []
  ):array {
    $response = [
      'success' => $success,
      'message' => $message,
    ];

    if ($data !== null) {
      $response['data'] = $data;
    }

    if (!empty($errors)) {
      $response['errors'] = $errors;
    }

    if (!empty($meta)) {
      $response['meta'] = $meta;
    }

    return $response;
  }

  /**
   * Create a success response.
   */
  public static function success(string $message = 'Success', mixed $data = null, array $meta = []):array {
    return self::response(true, $message, $data, [], $meta);
  }

  /**
   * Create an error response.
   */
  public static function error(string $message = 'Error', array $errors = [], mixed $data = null):array {
    return self::response(false, $message, $data, $errors);
  }

  /**
   * Output and exit with JSON response.
   *
   * @param mixed $data Data to output
   * @param int $status_code HTTP status code
   */
  public static function jsonExit(mixed $data, int $status_code = 200):never {
    \http_response_code($status_code);
    \header('Content-Type: application/json; charset=utf-8');
    echo \json_encode($data);
    exit;
  }

  /**
   * Output success response and exit.
   */
  public static function successExit(string $message = 'Success', mixed $data = null, int $status_code = 200):never {
    self::jsonExit(self::success($message, $data), $status_code);
  }

  /**
   * Output error response and exit.
   */
  public static function errorExit(string $message = 'Error', array $errors = [], int $status_code = 400):never {
    self::jsonExit(self::error($message, $errors), $status_code);
  }

  /**
   * Legacy writeOut method.
   *
   * @deprecated Use response() or json() instead
   */
  public static function writeOut(
    int $error_type,
    string $message,
    array $errors = [],
    array $extra = [],
    string $format = 'json',
    bool $return = false
  ):?string {
    $output = \array_merge($extra, [
      'status' => $error_type . '.' . \count($errors),
      'message' => $message,
      'errors' => $errors,
    ]);

    if ($format === 'json') {
      return self::json($output, $return);
    }

    // Plain text fallback
    $text = "Status: {$output['status']}\nMessage: {$message}";

    if ($return) {
      return $text;
    }

    echo $text;
    return null;
  }
}
