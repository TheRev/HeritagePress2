<?php

/**
 * View String Handler for HeritagePress
 *
 * Provides safe string operations for views
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_View_String_Handler
{
  /**
   * Get a safe string value
   */
  public static function get_string($value)
  {
    if ($value === null) {
      return '';
    }
    return (string)$value;
  }

  /**
   * Get safe SQL conditions
   */
  public static function get_sql_conditions($search)
  {
    global $wpdb;

    $where = '';
    $params = array();

    $search = self::get_string($search);
    if (!empty($search)) {
      $like = '%' . $wpdb->esc_like($search) . '%';
      $where = "WHERE albumname LIKE %s OR description LIKE %s OR keywords LIKE %s";
      $params = array($like, $like, $like);
    }

    return array($where, $params);
  }

  /**
   * Get safe table name
   */
  public static function get_table($name)
  {
    return esc_sql(self::get_string($name));
  }
}
