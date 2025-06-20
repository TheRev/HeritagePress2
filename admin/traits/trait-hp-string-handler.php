<?php

/**
 * String Handler Trait
 *
 * Provides string handling utilities for HeritagePress controllers
 * to prevent null values from being passed to WordPress functions.
 *
 * @package HeritagePress
 */

trait HP_String_Handler
{
  /**
   * Ensure a value is a valid string
   *
   * @param mixed $value The value to check
   * @return string
   */
  protected function ensure_string($value)
  {
    if ($value === null) {
      return '';
    }
    return (string)$value;
  }

  /**
   * Safely prepare SQL with guaranteed string values
   *
   * @param string $query The query with placeholders
   * @param array $args The arguments to prepare
   * @return string
   */
  protected function safe_prepare($query, $args = array())
  {
    global $wpdb;

    // Ensure query is a string
    $query = $this->ensure_string($query);

    // Ensure all args are properly typed strings
    $safe_args = array_map(function ($arg) {
      return $this->ensure_string($arg);
    }, (array)$args);

    return $wpdb->prepare($query, $safe_args);
  }

  /**
   * Safely escape a table name
   *
   * @param string $table The table name
   * @return string
   */
  protected function safe_table($table)
  {
    return esc_sql($this->ensure_string($table));
  }

  /**
   * Safely escape text for a field
   *
   * @param mixed $text The text to escape
   * @return string
   */
  protected function safe_text($text)
  {
    return sanitize_text_field($this->ensure_string($text));
  }

  /**
   * Safely escape text for a textarea
   *
   * @param mixed $text The text to escape
   * @return string
   */
  protected function safe_textarea($text)
  {
    return sanitize_textarea_field($this->ensure_string($text));
  }

  /**
   * Safely prepare a LIKE clause
   *
   * @param string $field The field name
   * @param string $value The search value
   * @return array [sql, params]
   */
  protected function safe_like($field, $value)
  {
    global $wpdb;

    $field = $this->ensure_string($field);
    $value = $this->ensure_string($value);

    $like = '%' . $wpdb->esc_like($value) . '%';
    return [
      "$field LIKE %s",
      [$like]
    ];
  }
}
