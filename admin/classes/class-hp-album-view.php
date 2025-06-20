<?php

/**
 * Album View Class
 *
 * Handles album listing view with safe string operations
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Album_View
{
  private $wpdb;
  private $search;
  private $offset;
  private $per_page;

  public function __construct()
  {
    global $wpdb;
    $this->wpdb = $wpdb;

    // Get and sanitize input
    $this->search = isset($_GET['search']) ? (string)sanitize_text_field($_GET['search']) : '';
    $this->offset = isset($_GET['offset']) ? abs(intval($_GET['offset'])) : 0;
    $this->per_page = 25;
  }

  /**
   * Get safe string value
   */
  private function get_string($value)
  {
    if ($value === null) {
      return '';
    }
    return (string)$value;
  }

  /**
   * Get table name safely
   */
  private function get_table_name()
  {
    return esc_sql($this->get_string($this->wpdb->prefix . 'hp_albums'));
  }

  /**
   * Build WHERE clause safely
   */
  private function build_where()
  {
    $where = '';
    $params = array();

    if (!empty($this->search)) {
      $like = '%' . $this->wpdb->esc_like($this->search) . '%';
      $where = "WHERE albumname LIKE %s OR description LIKE %s OR keywords LIKE %s";
      $params = array($like, $like, $like);
    }

    return array($where, $params);
  }

  /**
   * Get albums with pagination
   */
  public function get_albums()
  {
    $table = $this->get_table_name();
    list($where, $params) = $this->build_where();

    // Get total count
    $count_query = "SELECT COUNT(albumID) as total FROM $table $where";
    $total_rows = !empty($params)
      ? (int)$this->wpdb->get_var($this->wpdb->prepare($count_query, $params))
      : (int)$this->wpdb->get_var($count_query);

    // Get paginated results
    $limit = $this->wpdb->prepare(" LIMIT %d, %d", array($this->offset, $this->per_page));
    $query = "SELECT * FROM $table $where ORDER BY albumname ASC" . $limit;
    $albums = !empty($params)
      ? $this->wpdb->get_results($this->wpdb->prepare($query, $params))
      : $this->wpdb->get_results($query);

    // Sanitize album data to prevent null value warnings
    if (is_array($albums)) {
      foreach ($albums as $album) {
        foreach ($album as $key => $value) {
          if ($value === null) {
            $album->$key = '';
          }
        }
      }
    }

    return array(
      'total_rows' => $total_rows,
      'albums' => $albums,
      'offset' => $this->offset,
      'per_page' => $this->per_page,
      'search_string' => $this->search
    );
  }
}
