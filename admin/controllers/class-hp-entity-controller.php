<?php

/**
 * Entity Controller
 *
 * Handles geographic entity management (states and countries) for place standardization
 */

if (!defined('ABSPATH')) {
  exit;
}

require_once plugin_dir_path(__FILE__) . '../../includes/controllers/class-hp-base-controller.php';

class HP_Entity_Controller extends HP_Base_Controller
{
  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct('entity');
    $this->capabilities = array(
      'manage_entities' => 'manage_genealogy',
      'edit_entities' => 'edit_genealogy',
      'delete_entities' => 'delete_genealogy'
    );
  }

  /**
   * Register hooks for entity management
   */
  public function register_hooks()
  {
    parent::register_hooks();

    // AJAX handlers for entities
    add_action('wp_ajax_hp_add_entity', array($this, 'ajax_add_entity'));
    add_action('wp_ajax_hp_delete_entity', array($this, 'ajax_delete_entity'));
    add_action('wp_ajax_hp_get_entities', array($this, 'ajax_get_entities'));
    add_action('wp_ajax_hp_check_entity_exists', array($this, 'ajax_check_entity_exists'));
  }

  /**
   * Handle entity page actions
   */
  public function handle_entity_actions($tab)
  {
    if (!$this->check_capability('edit_genealogy')) {
      return;
    }

    // Handle form submissions
    if (isset($_POST['action'])) {
      switch ($_POST['action']) {
        case 'add_entity':
          $this->handle_add_entity();
          break;
        case 'delete_entity':
          $this->handle_delete_entity();
          break;
        case 'bulk_action':
          $this->handle_bulk_entity_actions();
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
      $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'states';
      $this->handle_entity_actions($current_tab);
    }
  }

  /**
   * Handle adding a new entity
   */
  private function handle_add_entity()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!$this->check_capability('edit_genealogy')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    $entity_type = sanitize_text_field($_POST['entity_type']);
    $entity_name = sanitize_text_field($_POST['entity_name']);

    // Validate inputs
    if (empty($entity_type) || empty($entity_name)) {
      $this->add_notice(__('Entity type and name are required.', 'heritagepress'), 'error');
      return;
    }

    if (!in_array($entity_type, ['state', 'country'])) {
      $this->add_notice(__('Invalid entity type.', 'heritagepress'), 'error');
      return;
    }

    $result = $this->add_entity($entity_type, $entity_name);

    if ($result === true) {
      $this->add_notice(sprintf(__('%s "%s" added successfully!', 'heritagepress'), ucfirst($entity_type), $entity_name), 'success');
    } elseif ($result === 'exists') {
      $this->add_notice(sprintf(__('%s "%s" already exists.', 'heritagepress'), ucfirst($entity_type), $entity_name), 'warning');
    } else {
      $this->add_notice(__('Failed to add entity. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle deleting an entity
   */
  private function handle_delete_entity()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!$this->check_capability('delete_genealogy')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    $entity_type = sanitize_text_field($_POST['entity_type']);
    $entity_name = sanitize_text_field($_POST['entity_name']);

    $result = $this->delete_entity($entity_type, $entity_name);

    if ($result) {
      $this->add_notice(sprintf(__('%s "%s" deleted successfully!', 'heritagepress'), ucfirst($entity_type), $entity_name), 'success');
    } else {
      $this->add_notice(__('Failed to delete entity. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle bulk entity actions
   */
  private function handle_bulk_entity_actions()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    $action = sanitize_text_field($_POST['bulk_action']);
    $entities = isset($_POST['entities']) ? $_POST['entities'] : array();

    if (empty($entities)) {
      $this->add_notice(__('No entities selected.', 'heritagepress'), 'error');
      return;
    }

    $success_count = 0;
    $error_count = 0;

    foreach ($entities as $entity_data) {
      $entity_type = sanitize_text_field($entity_data['type']);
      $entity_name = sanitize_text_field($entity_data['name']);

      switch ($action) {
        case 'delete':
          if ($this->check_capability('delete_genealogy')) {
            $result = $this->delete_entity($entity_type, $entity_name);
            if ($result) {
              $success_count++;
            } else {
              $error_count++;
            }
          }
          break;
      }
    }

    if ($success_count > 0) {
      $this->add_notice(sprintf(__('%d entities processed successfully.', 'heritagepress'), $success_count), 'success');
    }
    if ($error_count > 0) {
      $this->add_notice(sprintf(__('%d entities failed to process.', 'heritagepress'), $error_count), 'error');
    }
  }

  /**
   * Add a new entity
   */
  private function add_entity($entity_type, $entity_name)
  {
    global $wpdb;

    $table_name = $this->get_entity_table($entity_type);
    $column_name = $this->get_entity_column($entity_type);

    if (!$table_name || !$column_name) {
      return false;
    }

    // Check if entity already exists
    if ($this->entity_exists($entity_type, $entity_name)) {
      return 'exists';
    }

    // Insert entity
    $result = $wpdb->insert(
      $table_name,
      array($column_name => $entity_name),
      array('%s')
    );

    if ($result !== false) {
      // Log the action
      $this->log_admin_action(sprintf('Added %s: %s', $entity_type, $entity_name));
      return true;
    }

    return false;
  }

  /**
   * Delete an entity
   */
  private function delete_entity($entity_type, $entity_name)
  {
    global $wpdb;

    $table_name = $this->get_entity_table($entity_type);
    $column_name = $this->get_entity_column($entity_type);

    if (!$table_name || !$column_name) {
      return false;
    }

    $result = $wpdb->delete(
      $table_name,
      array($column_name => $entity_name),
      array('%s')
    );

    if ($result !== false) {
      // Log the action
      $this->log_admin_action(sprintf('Deleted %s: %s', $entity_type, $entity_name));
      return true;
    }

    return false;
  }

  /**
   * Check if entity exists
   */
  private function entity_exists($entity_type, $entity_name)
  {
    global $wpdb;

    $table_name = $this->get_entity_table($entity_type);
    $column_name = $this->get_entity_column($entity_type);

    if (!$table_name || !$column_name) {
      return false;
    }

    $count = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $table_name WHERE $column_name = %s",
      $entity_name
    ));

    return $count > 0;
  }

  /**
   * Get entities of a specific type
   */
  private function get_entities($entity_type, $limit = 1000, $offset = 0)
  {
    global $wpdb;

    $table_name = $this->get_entity_table($entity_type);
    $column_name = $this->get_entity_column($entity_type);

    if (!$table_name || !$column_name) {
      return array();
    }

    $results = $wpdb->get_results($wpdb->prepare(
      "SELECT $column_name as name FROM $table_name ORDER BY $column_name LIMIT %d OFFSET %d",
      $limit,
      $offset
    ), ARRAY_A);

    return $results ? $results : array();
  }

  /**
   * Get entity table name
   */
  private function get_entity_table($entity_type)
  {
    global $wpdb;

    switch ($entity_type) {
      case 'state':
        return $wpdb->prefix . 'hp_states';
      case 'country':
        return $wpdb->prefix . 'hp_countries';
      default:
        return false;
    }
  }

  /**
   * Get entity column name
   */
  private function get_entity_column($entity_type)
  {
    switch ($entity_type) {
      case 'state':
        return 'state_name';
      case 'country':
        return 'country_name';
      default:
        return false;
    }
  }

  /**
   * Log admin action
   */
  private function log_admin_action($message)
  {
    // TODO: Implement admin logging if not already available
    error_log('HeritagePress Entity Action: ' . $message);
  }

  /**
   * AJAX: Add entity
   */
  public function ajax_add_entity()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('edit_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    $entity_type = sanitize_text_field($_POST['entity_type']);
    $entity_name = sanitize_text_field($_POST['entity_name']);

    if (empty($entity_type) || empty($entity_name)) {
      wp_send_json_error('Entity type and name are required');
    }

    if (!in_array($entity_type, ['state', 'country'])) {
      wp_send_json_error('Invalid entity type');
    }

    $result = $this->add_entity($entity_type, $entity_name);

    if ($result === true) {
      wp_send_json_success(array(
        'message' => sprintf('%s "%s" added successfully', ucfirst($entity_type), $entity_name),
        'entity' => array('type' => $entity_type, 'name' => $entity_name)
      ));
    } elseif ($result === 'exists') {
      wp_send_json_error(sprintf('%s "%s" already exists', ucfirst($entity_type), $entity_name));
    } else {
      wp_send_json_error('Failed to add entity');
    }
  }

  /**
   * AJAX: Delete entity
   */
  public function ajax_delete_entity()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('delete_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    $entity_type = sanitize_text_field($_POST['entity_type']);
    $entity_name = sanitize_text_field($_POST['entity_name']);

    $result = $this->delete_entity($entity_type, $entity_name);

    if ($result) {
      wp_send_json_success(array(
        'message' => sprintf('%s "%s" deleted successfully', ucfirst($entity_type), $entity_name)
      ));
    } else {
      wp_send_json_error('Failed to delete entity');
    }
  }

  /**
   * AJAX: Get entities
   */
  public function ajax_get_entities()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    $entity_type = sanitize_text_field($_POST['entity_type']);
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 1000;
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

    if (!in_array($entity_type, ['state', 'country'])) {
      wp_send_json_error('Invalid entity type');
    }

    $entities = $this->get_entities($entity_type, $limit, $offset);

    wp_send_json_success(array(
      'entities' => $entities,
      'type' => $entity_type
    ));
  }

  /**
   * AJAX: Check if entity exists
   */
  public function ajax_check_entity_exists()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    $entity_type = sanitize_text_field($_POST['entity_type']);
    $entity_name = sanitize_text_field($_POST['entity_name']);

    if (!in_array($entity_type, ['state', 'country'])) {
      wp_send_json_error('Invalid entity type');
    }

    $exists = $this->entity_exists($entity_type, $entity_name);

    wp_send_json_success(array(
      'exists' => $exists,
      'entity_type' => $entity_type,
      'entity_name' => $entity_name
    ));
  }

  /**
   * Display the entity management page
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
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'states';

    // Include the entity management view
    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/entity-management.php';
  }
}
