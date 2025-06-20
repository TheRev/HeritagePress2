<?php

/**
 * HP Timeline Events Controller
 *
 * Handles CRUD operations for timeline events.
 *
 * @package HeritagePress
 * @subpackage Admin
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

/**
 * Class HP_Timeline_Controller
 *
 * Manages timeline events with full CRUD operations
 */
class HP_Timeline_Controller
{
  /**
   * @var wpdb
   */
  private $wpdb;

  /**
   * @var string
   */
  private $table_name;

  /**
   * Constructor
   */
  public function __construct()
  {
    global $wpdb;
    $this->wpdb = $wpdb;
    $this->table_name = $wpdb->prefix . 'heritagepress_timelineevents';

    add_action('wp_ajax_hp_timeline_search', array($this, 'handle_timeline_search'));
    add_action('wp_ajax_hp_delete_timeline_event', array($this, 'handle_delete_timeline_event'));
    add_action('wp_ajax_hp_add_timeline_event', array($this, 'handle_add_timeline_event'));
    add_action('wp_ajax_hp_update_timeline_event', array($this, 'handle_update_timeline_event'));
    add_action('wp_ajax_hp_get_timeline_event', array($this, 'handle_get_timeline_event'));
  }

  /**
   * Get timeline events with search and pagination
   *
   * @param array $args Search arguments
   * @return array Results with events and pagination info
   */
  public function get_timeline_events($args = array())
  {
    $defaults = array(
      'search' => '',
      'limit' => 25,
      'offset' => 0,
      'orderby' => 'ABS(evyear), evmonth, evday',
      'order' => 'ASC'
    );

    $args = wp_parse_args($args, $defaults);

    // Build WHERE clause
    $where_conditions = array('1=1');
    $query_params = array();

    if (!empty($args['search'])) {
      $search = '%' . $this->wpdb->esc_like($args['search']) . '%';
      $where_conditions[] = '(evyear LIKE %s OR evtitle LIKE %s OR evdetail LIKE %s)';
      $query_params[] = $search;
      $query_params[] = $search;
      $query_params[] = $search;
    }

    $where_clause = implode(' AND ', $where_conditions);

    // Get total count
    $count_sql = $this->wpdb->prepare(
      "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}",
      $query_params
    );
    $total_events = $this->wpdb->get_var($count_sql);

    // Get events
    $limit_clause = '';
    if ($args['limit'] > 0) {
      $limit_clause = $this->wpdb->prepare(' LIMIT %d OFFSET %d', $args['limit'], $args['offset']);
    }

    $sql = $this->wpdb->prepare(
      "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY {$args['orderby']} {$args['order']}{$limit_clause}",
      $query_params
    );

    $events = $this->wpdb->get_results($sql, ARRAY_A);

    return array(
      'events' => $events,
      'total' => $total_events,
      'offset' => $args['offset'],
      'limit' => $args['limit']
    );
  }

  /**
   * Get a single timeline event by ID
   *
   * @param int $timeline_id Timeline event ID
   * @return array|null Timeline event data or null if not found
   */
  public function get_timeline_event($timeline_id)
  {
    $sql = $this->wpdb->prepare(
      "SELECT * FROM {$this->table_name} WHERE tleventID = %d",
      $timeline_id
    );

    return $this->wpdb->get_row($sql, ARRAY_A);
  }

  /**
   * Add a new timeline event
   *
   * @param array $data Timeline event data
   * @return int|false New timeline event ID or false on failure
   */
  public function add_timeline_event($data)
  {
    // Validate required fields
    if (empty($data['evyear']) || empty($data['evdetail'])) {
      return false;
    }

    // Sanitize and prepare data
    $event_data = array(
      'evday' => !empty($data['evday']) ? intval($data['evday']) : 0,
      'evmonth' => !empty($data['evmonth']) ? intval($data['evmonth']) : 0,
      'evyear' => sanitize_text_field($data['evyear']),
      'endday' => !empty($data['endday']) ? intval($data['endday']) : 0,
      'endmonth' => !empty($data['endmonth']) ? intval($data['endmonth']) : 0,
      'endyear' => !empty($data['endyear']) ? sanitize_text_field($data['endyear']) : '',
      'evtitle' => sanitize_text_field($data['evtitle']),
      'evdetail' => wp_kses_post($data['evdetail'])
    );

    $result = $this->wpdb->insert(
      $this->table_name,
      $event_data,
      array('%d', '%d', '%s', '%d', '%d', '%s', '%s', '%s')
    );

    if ($result === false) {
      return false;
    }

    $timeline_id = $this->wpdb->insert_id;

    // Log the action
    $this->log_admin_action('Add Timeline Event', "ID: {$timeline_id} - {$event_data['evdetail']}");

    return $timeline_id;
  }

  /**
   * Update a timeline event
   *
   * @param int $timeline_id Timeline event ID
   * @param array $data Updated data
   * @return bool Success status
   */
  public function update_timeline_event($timeline_id, $data)
  {
    // Validate required fields
    if (empty($data['evyear']) || empty($data['evdetail'])) {
      return false;
    }

    // Sanitize and prepare data
    $event_data = array(
      'evday' => !empty($data['evday']) ? intval($data['evday']) : 0,
      'evmonth' => !empty($data['evmonth']) ? intval($data['evmonth']) : 0,
      'evyear' => sanitize_text_field($data['evyear']),
      'endday' => !empty($data['endday']) ? intval($data['endday']) : 0,
      'endmonth' => !empty($data['endmonth']) ? intval($data['endmonth']) : 0,
      'endyear' => !empty($data['endyear']) ? sanitize_text_field($data['endyear']) : '',
      'evtitle' => sanitize_text_field($data['evtitle']),
      'evdetail' => wp_kses_post($data['evdetail'])
    );

    $result = $this->wpdb->update(
      $this->table_name,
      $event_data,
      array('tleventID' => $timeline_id),
      array('%d', '%d', '%s', '%d', '%d', '%s', '%s', '%s'),
      array('%d')
    );

    if ($result === false) {
      return false;
    }

    // Log the action
    $this->log_admin_action('Update Timeline Event', "ID: {$timeline_id}");

    return true;
  }

  /**
   * Delete a timeline event
   *
   * @param int $timeline_id Timeline event ID
   * @return bool Success status
   */
  public function delete_timeline_event($timeline_id)
  {
    $result = $this->wpdb->delete(
      $this->table_name,
      array('tleventID' => $timeline_id),
      array('%d')
    );

    if ($result === false) {
      return false;
    }

    // Log the action
    $this->log_admin_action('Delete Timeline Event', "ID: {$timeline_id}");

    return true;
  }

  /**
   * Delete multiple timeline events
   *
   * @param array $timeline_ids Array of timeline event IDs
   * @return int Number of deleted events
   */
  public function delete_multiple_timeline_events($timeline_ids)
  {
    if (empty($timeline_ids) || !is_array($timeline_ids)) {
      return 0;
    }

    $deleted_count = 0;
    foreach ($timeline_ids as $timeline_id) {
      if ($this->delete_timeline_event($timeline_id)) {
        $deleted_count++;
      }
    }

    return $deleted_count;
  }

  /**
   * Validate timeline event data
   *
   * @param array $data Timeline event data
   * @return array Validation errors
   */
  public function validate_timeline_event($data)
  {
    $errors = array();

    // Required field validation
    if (empty($data['evyear'])) {
      $errors[] = __('Year is required.', 'heritagepress');
    }

    if (empty($data['evdetail'])) {
      $errors[] = __('Event detail is required.', 'heritagepress');
    }

    // Date validation
    if (!empty($data['endyear']) && !empty($data['evyear'])) {
      if (intval($data['endyear']) < intval($data['evyear'])) {
        $errors[] = __('Ending year cannot be less than beginning year.', 'heritagepress');
      }
    }

    // End date requirements
    if (empty($data['endyear']) && (!empty($data['endmonth']) || !empty($data['endday']))) {
      $errors[] = __('If you enter a day or month for the ending date, you must also enter an ending year.', 'heritagepress');
    }

    // Day/month validation
    if ((!empty($data['evday']) && empty($data['evmonth'])) ||
      (!empty($data['endday']) && empty($data['endmonth']))
    ) {
      $errors[] = __('If you select a day, you must also select a month.', 'heritagepress');
    }

    return $errors;
  }

  /**
   * Handle AJAX timeline search
   */
  public function handle_timeline_search()
  {
    check_ajax_referer('hp_timeline_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $search = sanitize_text_field($_POST['search'] ?? '');
    $page = intval($_POST['page'] ?? 1);
    $per_page = 25;
    $offset = ($page - 1) * $per_page;

    $results = $this->get_timeline_events(array(
      'search' => $search,
      'limit' => $per_page,
      'offset' => $offset
    ));

    wp_send_json_success($results);
  }

  /**
   * Handle AJAX timeline event deletion
   */
  public function handle_delete_timeline_event()
  {
    check_ajax_referer('hp_timeline_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $timeline_id = intval($_POST['timeline_id'] ?? 0);

    if (!$timeline_id) {
      wp_send_json_error(__('Invalid timeline event ID.', 'heritagepress'));
    }

    if ($this->delete_timeline_event($timeline_id)) {
      wp_send_json_success(__('Timeline event deleted successfully.', 'heritagepress'));
    } else {
      wp_send_json_error(__('Failed to delete timeline event.', 'heritagepress'));
    }
  }

  /**
   * Handle AJAX timeline event addition
   */
  public function handle_add_timeline_event()
  {
    check_ajax_referer('hp_timeline_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $data = $_POST['timeline_data'] ?? array();

    // Validate data
    $errors = $this->validate_timeline_event($data);
    if (!empty($errors)) {
      wp_send_json_error(implode(' ', $errors));
    }

    $timeline_id = $this->add_timeline_event($data);

    if ($timeline_id) {
      wp_send_json_success(array(
        'message' => __('Timeline event added successfully.', 'heritagepress'),
        'timeline_id' => $timeline_id
      ));
    } else {
      wp_send_json_error(__('Failed to add timeline event.', 'heritagepress'));
    }
  }

  /**
   * Handle AJAX timeline event update
   */
  public function handle_update_timeline_event()
  {
    check_ajax_referer('hp_timeline_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $timeline_id = intval($_POST['timeline_id'] ?? 0);
    $data = $_POST['timeline_data'] ?? array();

    if (!$timeline_id) {
      wp_send_json_error(__('Invalid timeline event ID.', 'heritagepress'));
    }

    // Validate data
    $errors = $this->validate_timeline_event($data);
    if (!empty($errors)) {
      wp_send_json_error(implode(' ', $errors));
    }

    if ($this->update_timeline_event($timeline_id, $data)) {
      wp_send_json_success(__('Timeline event updated successfully.', 'heritagepress'));
    } else {
      wp_send_json_error(__('Failed to update timeline event.', 'heritagepress'));
    }
  }

  /**
   * Handle AJAX get single timeline event
   */
  public function handle_get_timeline_event()
  {
    check_ajax_referer('hp_timeline_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $timeline_id = intval($_POST['timeline_id'] ?? 0);

    if (!$timeline_id) {
      wp_send_json_error(__('Invalid timeline event ID.', 'heritagepress'));
    }

    $event = $this->get_timeline_event($timeline_id);

    if ($event) {
      wp_send_json_success($event);
    } else {
      wp_send_json_error(__('Timeline event not found.', 'heritagepress'));
    }
  }

  /**
   * Get month names array
   *
   * @return array Month names
   */
  public function get_months()
  {
    return array(
      1 => __('January', 'heritagepress'),
      2 => __('February', 'heritagepress'),
      3 => __('March', 'heritagepress'),
      4 => __('April', 'heritagepress'),
      5 => __('May', 'heritagepress'),
      6 => __('June', 'heritagepress'),
      7 => __('July', 'heritagepress'),
      8 => __('August', 'heritagepress'),
      9 => __('September', 'heritagepress'),
      10 => __('October', 'heritagepress'),
      11 => __('November', 'heritagepress'),
      12 => __('December', 'heritagepress')
    );
  }

  /**
   * Format timeline event date
   *
   * @param array $event Timeline event data
   * @param bool $start_date Whether to format start date (true) or end date (false)
   * @return string Formatted date
   */
  public function format_event_date($event, $start_date = true)
  {
    $prefix = $start_date ? 'ev' : 'end';
    $day = $event[$prefix . 'day'];
    $month = $event[$prefix . 'month'];
    $year = $event[$prefix . 'year'];

    if (empty($year)) {
      return '';
    }

    $date_parts = array();

    if (!empty($day) && !empty($month)) {
      $months = $this->get_months();
      $date_parts[] = $day;
      $date_parts[] = $months[$month];
    } elseif (!empty($month)) {
      $months = $this->get_months();
      $date_parts[] = $months[$month];
    }

    $date_parts[] = $year;

    return implode(' ', $date_parts);
  }

  /**
   * Log admin action
   *
   * @param string $action Action name
   * @param string $details Action details
   */
  private function log_admin_action($action, $details)
  {
    // Simple logging - in real implementation this could write to a log table
    error_log("HeritagePress Admin: {$action} - {$details}");
  }
}
