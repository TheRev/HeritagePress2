<?php

/**
 * Base View Class
 *
 * Provides common functionality for HeritagePress views.
 *
 * @package HeritagePress
 */

require_once plugin_dir_path(__FILE__) . '../traits/trait-hp-string-handler.php';

class HP_Base_View
{
  use HP_String_Handler;

  /**
   * Get request data with type safety
   */    public function get_request_var($key, $default = '')
  {
    return isset($_REQUEST[$key]) ? $this->safe_text($_REQUEST[$key]) : $default;
  }

  /**
   * Build safe SQL conditions
   */
  public function build_where_conditions($conditions)
  {
    global $wpdb;

    $where = array();
    $params = array();

    foreach ($conditions as $condition) {
      [$sql, $args] = $this->safe_like($condition['field'], $condition['value']);
      $where[] = $sql;
      $params = array_merge($params, $args);
    }

    return [
      'where' => !empty($where) ? 'WHERE ' . implode(' OR ', $where) : '',
      'params' => $params
    ];
  }
}
