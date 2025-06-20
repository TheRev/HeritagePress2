<?php

/**
 * Event Type Controller
 *
 * Handles all event type management functionality including CRUD operations,
 * event type validation, and event type-related AJAX requests.
 */

if (!defined('ABSPATH')) {
  exit;
}

require_once plugin_dir_path(__FILE__) . '../../includes/controllers/class-hp-base-controller.php';

class HP_Event_Type_Controller extends HP_Base_Controller
{
  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct('event_type');
    $this->capabilities = array(
      'manage_event_types' => 'manage_genealogy',
      'edit_event_types' => 'edit_genealogy',
      'delete_event_types' => 'delete_genealogy'
    );
  }

  /**
   * Register hooks for event type management
   */
  public function register_hooks()
  {
    parent::register_hooks();

    // AJAX handlers for event types
    add_action('wp_ajax_hp_add_event_type', array($this, 'ajax_add_event_type'));
    add_action('wp_ajax_hp_update_event_type', array($this, 'ajax_update_event_type'));
    add_action('wp_ajax_hp_delete_event_type', array($this, 'ajax_delete_event_type'));
    add_action('wp_ajax_hp_get_event_type', array($this, 'ajax_get_event_type'));
    add_action('wp_ajax_hp_check_event_type_exists', array($this, 'ajax_check_event_type_exists'));
    add_action('wp_ajax_hp_get_event_types_list', array($this, 'ajax_get_event_types_list'));
    add_action('wp_ajax_hp_bulk_event_type_action', array($this, 'ajax_bulk_event_type_action'));
  }

  /**
   * Handle event type page actions
   */
  public function handle_event_type_actions($tab)
  {
    if (!$this->check_capability('edit_genealogy')) {
      return;
    }

    // Handle form submissions
    if (isset($_POST['action'])) {
      switch ($_POST['action']) {
        case 'add_event_type':
          $this->handle_add_event_type();
          break;
        case 'update_event_type':
          $this->handle_update_event_type();
          break;
        case 'delete_event_type':
          $this->handle_delete_event_type();
          break;
        case 'bulk_action':
          $this->handle_bulk_event_type_actions();
          break;
      }
    }
  }

  /**
   * Handle form submission - delegates to individual action handlers
   */
  public function handle_form_submission()
  {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
      $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'browse';
      $this->handle_event_type_actions($current_tab);
    }
  }

  /**
   * Handle adding a new event type
   */
  private function handle_add_event_type()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!$this->check_capability('edit_genealogy')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    // Sanitize and validate form data
    $event_type_data = $this->sanitize_event_type_data($_POST);

    // Validate required fields
    if (empty($event_type_data['tag']) || empty($event_type_data['display']) || empty($event_type_data['type'])) {
      $this->add_notice(__('Tag, Display name, and Type are required.', 'heritagepress'), 'error');
      return;
    }

    // Check if event type ID already exists
    if ($this->event_type_exists($event_type_data['eventtypeID'])) {
      $this->add_notice(__('Event Type ID already exists.', 'heritagepress'), 'error');
      return;
    }

    $result = $this->create_event_type($event_type_data);

    if ($result) {
      $this->add_notice(__('Event type created successfully!', 'heritagepress'), 'success');
      // Log admin action
      $this->log_admin_action('Added new event type: ' . $event_type_data['tag'] . ' ' . $event_type_data['type'] . ' - ' . $event_type_data['display']);
    } else {
      $this->add_notice(__('Failed to create event type. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle updating an existing event type
   */
  private function handle_update_event_type()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!$this->check_capability('edit_genealogy')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    $event_type_id = sanitize_text_field($_POST['eventtypeID']);
    $event_type_data = $this->sanitize_event_type_data($_POST);

    $result = $this->update_event_type($event_type_id, $event_type_data);

    if ($result) {
      $this->add_notice(__('Event type updated successfully!', 'heritagepress'), 'success');
      // Log admin action
      $this->log_admin_action('Modified event type: ' . $event_type_id);
    } else {
      $this->add_notice(__('Failed to update event type. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle deleting an event type
   */
  private function handle_delete_event_type()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!$this->check_capability('delete_genealogy')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    $event_type_id = sanitize_text_field($_POST['eventtypeID']);

    $result = $this->delete_event_type($event_type_id);

    if ($result) {
      $this->add_notice(__('Event type deleted successfully!', 'heritagepress'), 'success');
      // Log admin action
      $this->log_admin_action('Deleted event type: ' . $event_type_id);
    } else {
      $this->add_notice(__('Failed to delete event type. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle bulk event type actions
   */
  private function handle_bulk_event_type_actions()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    $action = sanitize_text_field($_POST['bulk_action']);
    $event_type_ids = isset($_POST['event_type_ids']) ? array_map('sanitize_text_field', $_POST['event_type_ids']) : array();

    if (empty($event_type_ids)) {
      $this->add_notice(__('No event types selected.', 'heritagepress'), 'error');
      return;
    }

    $success_count = 0;
    $error_count = 0;

    foreach ($event_type_ids as $event_type_id) {
      switch ($action) {
        case 'delete':
          if ($this->check_capability('delete_genealogy')) {
            $result = $this->delete_event_type($event_type_id);
            if ($result) {
              $success_count++;
            } else {
              $error_count++;
            }
          }
          break;
        case 'activate':
          $result = $this->update_event_type($event_type_id, array('keep' => 1));
          if ($result) {
            $success_count++;
          } else {
            $error_count++;
          }
          break;
        case 'deactivate':
          $result = $this->update_event_type($event_type_id, array('keep' => 0));
          if ($result) {
            $success_count++;
          } else {
            $error_count++;
          }
          break;
      }
    }

    if ($success_count > 0) {
      $this->add_notice(sprintf(__('%d event types processed successfully.', 'heritagepress'), $success_count), 'success');
    }
    if ($error_count > 0) {
      $this->add_notice(sprintf(__('%d event types failed to process.', 'heritagepress'), $error_count), 'error');
    }
  }

  /**
   * Create a new event type
   */
  public function create_event_type($event_type_data)
  {
    global $wpdb;

    try {
      $eventtypes_table = $wpdb->prefix . 'hp_eventtypes';

      $result = $wpdb->insert(
        $eventtypes_table,
        array(
          'eventtypeID' => $event_type_data['eventtypeID'],
          'tag' => $event_type_data['tag'],
          'description' => $event_type_data['description'],
          'display' => $event_type_data['display'],
          'type' => $event_type_data['type'],
          'keep' => $event_type_data['keep'],
          'collapse' => $event_type_data['collapse'],
          'ordernum' => $event_type_data['ordernum'],
          'ldsevent' => $event_type_data['ldsevent']
        ),
        array('%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d')
      );

      return $result !== false;
    } catch (Exception $e) {
      error_log('HeritagePress Event Type Creation Error: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * Update an existing event type
   */
  private function update_event_type($event_type_id, $event_type_data)
  {
    global $wpdb;

    try {
      $eventtypes_table = $wpdb->prefix . 'hp_eventtypes';

      $result = $wpdb->update(
        $eventtypes_table,
        $event_type_data,
        array('eventtypeID' => $event_type_id),
        array('%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d'),
        array('%s')
      );

      return $result !== false;
    } catch (Exception $e) {
      error_log('HeritagePress Event Type Update Error: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * Delete an event type
   */
  private function delete_event_type($event_type_id)
  {
    global $wpdb;

    try {
      // Check if event type is in use
      $events_table = $wpdb->prefix . 'hp_events';
      $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $events_table WHERE eventtypeID = %s",
        $event_type_id
      ));

      if ($count > 0) {
        $this->add_notice(sprintf(__('Cannot delete event type "%s" because it is being used by %d events.', 'heritagepress'), $event_type_id, $count), 'error');
        return false;
      }

      $eventtypes_table = $wpdb->prefix . 'hp_eventtypes';

      $result = $wpdb->delete(
        $eventtypes_table,
        array('eventtypeID' => $event_type_id),
        array('%s')
      );

      return $result !== false;
    } catch (Exception $e) {
      error_log('HeritagePress Event Type Deletion Error: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * Check if event type exists
   */
  public function event_type_exists($event_type_id)
  {
    global $wpdb;

    $eventtypes_table = $wpdb->prefix . 'hp_eventtypes';

    $count = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $eventtypes_table WHERE eventtypeID = %s",
      $event_type_id
    ));

    return $count > 0;
  }

  /**
   * Get all event types with filtering and sorting
   */
  public function get_event_types($filters = array())
  {
    global $wpdb;

    $eventtypes_table = $wpdb->prefix . 'hp_eventtypes';

    $where = '1=1';
    $params = array();

    // Filter by type
    if (!empty($filters['type'])) {
      $where .= ' AND type = %s';
      $params[] = $filters['type'];
    }

    // Filter by keep status
    if (isset($filters['keep'])) {
      $where .= ' AND keep = %d';
      $params[] = $filters['keep'];
    }

    // Search by tag or display name
    if (!empty($filters['search'])) {
      $where .= ' AND (tag LIKE %s OR display LIKE %s OR description LIKE %s)';
      $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
      $params[] = $search_term;
      $params[] = $search_term;
      $params[] = $search_term;
    }

    // Order by
    $order_by = 'ORDER BY ordernum ASC, tag ASC';
    if (!empty($filters['orderby'])) {
      switch ($filters['orderby']) {
        case 'tag':
          $order_by = 'ORDER BY tag ASC';
          break;
        case 'display':
          $order_by = 'ORDER BY display ASC';
          break;
        case 'type':
          $order_by = 'ORDER BY type ASC, tag ASC';
          break;
        case 'ordernum':
          $order_by = 'ORDER BY ordernum ASC, tag ASC';
          break;
      }
    }

    $query = "SELECT * FROM $eventtypes_table WHERE $where $order_by";

    if (!empty($params)) {
      return $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);
    } else {
      return $wpdb->get_results($query, ARRAY_A);
    }
  }

  /**
   * Sanitize event type form data
   */
  private function sanitize_event_type_data($data)
  {
    // Generate eventtypeID if not provided (for new event types)
    $event_type_id = !empty($data['eventtypeID']) ? sanitize_text_field($data['eventtypeID']) : sanitize_text_field($data['tag']);

    return array(
      'eventtypeID' => $event_type_id,
      'tag' => sanitize_text_field($data['tag'] ?? ''),
      'description' => sanitize_text_field($data['description'] ?? ''),
      'display' => sanitize_text_field($data['display'] ?? ''),
      'type' => sanitize_text_field($data['type'] ?? 'I'),
      'keep' => intval($data['keep'] ?? 1),
      'collapse' => intval($data['collapse'] ?? 0),
      'ordernum' => intval($data['ordernum'] ?? 0),
      'ldsevent' => intval($data['ldsevent'] ?? 0)
    );
  }

  /**
   * AJAX: Add event type
   */
  public function ajax_add_event_type()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('edit_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    try {
      $event_type_data = $this->sanitize_event_type_data($_POST);

      // Validate required fields
      if (empty($event_type_data['tag']) || empty($event_type_data['display']) || empty($event_type_data['type'])) {
        wp_send_json_error('Tag, Display name, and Type are required');
      }

      // Check if event type ID already exists
      if ($this->event_type_exists($event_type_data['eventtypeID'])) {
        wp_send_json_error('Event Type ID already exists');
      }

      $result = $this->create_event_type($event_type_data);

      if ($result) {
        wp_send_json_success(array(
          'id' => $event_type_data['eventtypeID'],
          'tag' => $event_type_data['tag'],
          'display' => $event_type_data['display'],
          'type' => $event_type_data['type'],
          'keep' => $event_type_data['keep'],
          'message' => 'Event type created successfully'
        ));
      } else {
        wp_send_json_error('Failed to create event type');
      }
    } catch (Exception $e) {
      error_log('HeritagePress Event Type Error: ' . $e->getMessage());
      wp_send_json_error('An error occurred while creating the event type');
    }
  }

  /**
   * AJAX: Update event type
   */
  public function ajax_update_event_type()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('edit_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    try {
      $event_type_id = sanitize_text_field($_POST['eventtypeID'] ?? '');
      if (empty($event_type_id)) {
        wp_send_json_error('Invalid event type ID');
      }

      $event_type_data = $this->sanitize_event_type_data($_POST);

      $result = $this->update_event_type($event_type_id, $event_type_data);

      if ($result) {
        wp_send_json_success(array(
          'id' => $event_type_id,
          'tag' => $event_type_data['tag'],
          'display' => $event_type_data['display'],
          'type' => $event_type_data['type'],
          'keep' => $event_type_data['keep'],
          'message' => 'Event type updated successfully'
        ));
      } else {
        wp_send_json_error('Failed to update event type');
      }
    } catch (Exception $e) {
      error_log('HeritagePress Event Type Error: ' . $e->getMessage());
      wp_send_json_error('An error occurred while updating the event type');
    }
  }

  /**
   * AJAX: Delete event type
   */
  public function ajax_delete_event_type()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('delete_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    try {
      $event_type_id = sanitize_text_field($_POST['eventtypeID'] ?? '');

      if (empty($event_type_id)) {
        wp_send_json_error('Invalid event type ID');
      }

      $result = $this->delete_event_type($event_type_id);

      if ($result) {
        wp_send_json_success('Event type deleted successfully');
      } else {
        wp_send_json_error('Failed to delete event type');
      }
    } catch (Exception $e) {
      error_log('HeritagePress Event Type Error: ' . $e->getMessage());
      wp_send_json_error('An error occurred while deleting the event type');
    }
  }

  /**
   * AJAX: Get event type details
   */
  public function ajax_get_event_type()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    try {
      $event_type_id = sanitize_text_field($_POST['eventtypeID'] ?? '');

      if (empty($event_type_id)) {
        wp_send_json_error('Invalid event type ID');
      }

      global $wpdb;
      $eventtypes_table = $wpdb->prefix . 'hp_eventtypes';

      $event_type = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $eventtypes_table WHERE eventtypeID = %s",
        $event_type_id
      ), ARRAY_A);

      if (!$event_type) {
        wp_send_json_error('Event type not found');
      }

      // Escape quotes in event type data
      foreach ($event_type as $key => $value) {
        if (is_string($value)) {
          $event_type[$key] = str_replace('"', '&#34;', $value ?? '');
        }
      }

      wp_send_json_success($event_type);
    } catch (Exception $e) {
      error_log('HeritagePress Event Type Error: ' . $e->getMessage());
      wp_send_json_error('An error occurred while retrieving the event type');
    }
  }

  /**
   * AJAX: Check if event type exists
   */
  public function ajax_check_event_type_exists()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    try {
      $event_type_id = sanitize_text_field($_POST['eventtypeID'] ?? '');

      if (empty($event_type_id)) {
        wp_send_json_error('Invalid event type ID');
      }

      $exists = $this->event_type_exists($event_type_id);

      wp_send_json_success(array(
        'exists' => $exists,
        'eventtypeID' => $event_type_id
      ));
    } catch (Exception $e) {
      error_log('HeritagePress Event Type Error: ' . $e->getMessage());
      wp_send_json_error('An error occurred while checking event type');
    }
  }

  /**
   * AJAX: Get event types list with filtering
   */
  public function ajax_get_event_types_list()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    try {
      $filters = array();

      if (!empty($_POST['search'])) {
        $filters['search'] = sanitize_text_field($_POST['search']);
      }

      if (!empty($_POST['type'])) {
        $filters['type'] = sanitize_text_field($_POST['type']);
      }

      if (isset($_POST['keep']) && $_POST['keep'] !== '') {
        $filters['keep'] = intval($_POST['keep']);
      }

      $event_types = $this->get_event_types($filters);

      wp_send_json_success(array('event_types' => $event_types));
    } catch (Exception $e) {
      error_log('HeritagePress Event Type Error: ' . $e->getMessage());
      wp_send_json_error('An error occurred while retrieving event types');
    }
  }

  /**
   * AJAX: Bulk event type actions
   */
  public function ajax_bulk_event_type_action()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    try {
      $action = sanitize_text_field($_POST['bulk_action'] ?? '');
      $event_type_ids = isset($_POST['event_type_ids']) ? array_map('sanitize_text_field', $_POST['event_type_ids']) : array();

      if (empty($event_type_ids)) {
        wp_send_json_error('No event types selected');
      }

      $success_count = 0;
      $error_count = 0;

      foreach ($event_type_ids as $event_type_id) {
        switch ($action) {
          case 'delete':
            if ($this->check_capability('delete_genealogy')) {
              $result = $this->delete_event_type($event_type_id);
              if ($result) {
                $success_count++;
              } else {
                $error_count++;
              }
            }
            break;
          case 'activate':
            $result = $this->update_event_type($event_type_id, array('keep' => 1));
            if ($result) {
              $success_count++;
            } else {
              $error_count++;
            }
            break;
          case 'deactivate':
            $result = $this->update_event_type($event_type_id, array('keep' => 0));
            if ($result) {
              $success_count++;
            } else {
              $error_count++;
            }
            break;
        }
      }

      $message = sprintf('%d event types processed successfully', $success_count);
      if ($error_count > 0) {
        $message .= sprintf(', %d failed', $error_count);
      }

      wp_send_json_success(array('message' => $message));
    } catch (Exception $e) {
      error_log('HeritagePress Event Type Error: ' . $e->getMessage());
      wp_send_json_error('An error occurred while processing bulk action');
    }
  }

  /**
   * Display the event type management page
   */
  public function display_page()
  {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Handle form submissions first
    $this->handle_form_submission();

    // Display any notices
    $this->display_notices();

    // Get current tab and determine view
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'browse';

    // Include the event type management view
    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/event-type-management.php';
  }
}
