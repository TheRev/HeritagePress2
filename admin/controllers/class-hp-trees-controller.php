<?php

/**
 * Trees Controller
 *
 * Handles all tree management functionality including CRUD operations,
 * tree validation, and tree-related AJAX requests
 */

if (!defined('ABSPATH')) {
  exit;
}

require_once plugin_dir_path(__FILE__) . 'class-hp-base-controller.php';

class HP_Trees_Controller extends HP_Base_Controller
{
  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct('trees');
    $this->capabilities = array(
      'manage_trees' => 'manage_genealogy',
      'edit_trees' => 'edit_genealogy',
      'delete_trees' => 'delete_genealogy'
    );
  }

  /**
   * Initialize the trees controller
   */
  public function init()
  {
    parent::init();
    // Trees-specific initialization
  }

  /**
   * Register hooks for trees management
   */
  public function register_hooks()
  {
    parent::register_hooks();

    // AJAX handlers for trees
    add_action('wp_ajax_hp_add_tree', array($this, 'ajax_add_tree'));
    add_action('wp_ajax_hp_update_tree', array($this, 'ajax_update_tree'));
    add_action('wp_ajax_hp_delete_tree', array($this, 'ajax_delete_tree'));
    add_action('wp_ajax_hp_clear_tree', array($this, 'ajax_clear_tree'));
    add_action('wp_ajax_hp_check_tree_id', array($this, 'ajax_check_tree_id'));
    add_action('wp_ajax_hp_get_branches', array($this, 'ajax_get_branches'));

    // AJAX handlers for branches
    add_action('wp_ajax_hp_add_branch', array($this, 'ajax_add_branch'));
    add_action('wp_ajax_hp_update_branch', array($this, 'ajax_update_branch'));
    add_action('wp_ajax_hp_delete_branch', array($this, 'ajax_delete_branch'));
  }

  /**
   * Handle AJAX requests for trees
   */
  public function handle_ajax()
  {
    // All AJAX handlers are registered in register_hooks
    // This method can be used for additional AJAX processing if needed
  }

  /**
   * Enqueue assets for trees management
   */
  public function enqueue_assets()
  {
    // Trees-specific CSS and JS would be enqueued here
    // For now, using the main admin assets
  }

  /**
   * Handle trees page actions
   */
  public function handle_trees_actions($current_tab)
  {
    if (!$this->check_capability('edit_genealogy')) {
      return;
    }

    // Handle form submissions
    if (isset($_POST['action'])) {
      switch ($_POST['action']) {
        case 'add_tree':
          $this->handle_add_tree();
          break;
        case 'update_tree':
          $this->handle_update_tree();
          break;
        case 'delete_tree':
          $this->handle_delete_tree();
          break;
        case 'clear_tree':
          $this->handle_clear_tree();
          break;
        case 'bulk_action':
          $this->handle_bulk_tree_actions();
          break;
      }
    }
  }

  /**
   * Handle adding a new tree
   */
  private function handle_add_tree()
  {
    // Verify nonce
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }    // Sanitize form data
    $tree_data = $this->sanitize_form_data($_POST, array(
      'gedcom' => 'text',
      'treename' => 'text',
      'description' => 'textarea',
      'secret' => 'int'
    ));

    // Validate required fields
    if (empty($tree_data['gedcom']) || empty($tree_data['treename'])) {
      $this->add_notice(__('Tree ID and Tree Name are required.', 'heritagepress'), 'error');
      return;
    }

    // Validate tree ID format
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $tree_data['gedcom'])) {
      $this->add_notice(__('Tree ID must be alphanumeric with underscores and hyphens only.', 'heritagepress'), 'error');
      return;
    }

    // Check if tree ID already exists
    if ($this->tree_id_exists($tree_data['gedcom'])) {
      $this->add_notice(__('Tree ID already exists. Please choose a different ID.', 'heritagepress'), 'error');
      return;
    }

    // Create the tree
    $result = $this->create_tree($tree_data);

    if ($result) {
      $this->add_notice(__('Tree created successfully!', 'heritagepress'), 'success');
    } else {
      $this->add_notice(__('Failed to create tree. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle updating an existing tree
   */
  private function handle_update_tree()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    $tree_id = sanitize_text_field($_POST['tree_id']);
    $tree_data = $this->sanitize_form_data($_POST, array(
      'treename' => 'text',
      'description' => 'textarea',
      'secret' => 'int'
    ));

    $result = $this->update_tree($tree_id, $tree_data);

    if ($result) {
      $this->add_notice(__('Tree updated successfully!', 'heritagepress'), 'success');
    } else {
      $this->add_notice(__('Failed to update tree. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle deleting a tree
   */
  private function handle_delete_tree()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!$this->check_capability('delete_genealogy')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    $tree_id = sanitize_text_field($_POST['tree_id']);
    $delete_data = isset($_POST['delete_data']) && $_POST['delete_data'] === '1';

    $result = $this->delete_tree($tree_id, $delete_data);

    if ($result) {
      $message = $delete_data ?
        __('Tree and all its data deleted successfully!', 'heritagepress') :
        __('Tree deleted successfully!', 'heritagepress');
      $this->add_notice($message, 'success');
    } else {
      $this->add_notice(__('Failed to delete tree. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle clearing tree data
   */
  private function handle_clear_tree()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    $tree_id = sanitize_text_field($_POST['tree_id']);
    $result = $this->clear_tree_data($tree_id);

    if ($result) {
      $this->add_notice(__('Tree data cleared successfully!', 'heritagepress'), 'success');
    } else {
      $this->add_notice(__('Failed to clear tree data. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle bulk tree actions
   */
  private function handle_bulk_tree_actions()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    $action = sanitize_text_field($_POST['bulk_action']);
    $tree_ids = array_map('sanitize_text_field', $_POST['tree_ids']);

    if (empty($tree_ids)) {
      $this->add_notice(__('No trees selected.', 'heritagepress'), 'error');
      return;
    }

    switch ($action) {
      case 'delete':
        $this->handle_bulk_action($action, $tree_ids, array($this, 'delete_tree'));
        break;
      case 'clear':
        $this->handle_bulk_action($action, $tree_ids, array($this, 'clear_tree_data'));
        break;
      default:
        $this->add_notice(__('Invalid bulk action.', 'heritagepress'), 'error');
    }
  }

  /**
   * Check if tree ID exists
   */
  private function tree_id_exists($tree_id)
  {
    global $wpdb;
    $trees_table = $wpdb->prefix . 'hp_trees';

    $count = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $trees_table WHERE gedcom = %s",
      $tree_id
    ));

    return $count > 0;
  }
  /**
   * Create a new tree
   */
  private function create_tree($tree_data)
  {
    global $wpdb;
    $trees_table = $wpdb->prefix . 'hp_trees';

    // Get current user info
    $current_user = wp_get_current_user();
    $owner_name = $current_user->display_name ?: $current_user->user_login;

    $result = $wpdb->insert(
      $trees_table,
      array(
        'gedcom' => $tree_data['gedcom'],
        'treename' => $tree_data['treename'],
        'description' => isset($tree_data['description']) ? $tree_data['description'] : '',
        'owner' => $owner_name,
        'secret' => isset($tree_data['secret']) ? $tree_data['secret'] : 0
        // date_created will be automatically set by CURRENT_TIMESTAMP
      ),
      array('%s', '%s', '%s', '%s', '%d')
    );

    return $result !== false;
  }

  /**
   * Update an existing tree
   */
  private function update_tree($tree_id, $tree_data)
  {
    global $wpdb;
    $trees_table = $wpdb->prefix . 'hp_trees';

    $update_data = array(
      'treename' => $tree_data['treename'],
      'changedate' => current_time('mysql')
    );

    if (isset($tree_data['description'])) {
      $update_data['description'] = $tree_data['description'];
    }
    if (isset($tree_data['secret'])) {
      $update_data['secret'] = $tree_data['secret'];
    }

    $result = $wpdb->update(
      $trees_table,
      $update_data,
      array('gedcom' => $tree_id),
      array('%s', '%s', '%s', '%d'),
      array('%s')
    );

    return $result !== false;
  }

  /**
   * Delete a tree
   */
  private function delete_tree($tree_id, $delete_data = true)
  {
    global $wpdb;

    if ($delete_data) {
      // Delete all tree data
      $this->delete_tree_data($tree_id, false);
    }

    // Delete the tree record
    $trees_table = $wpdb->prefix . 'hp_trees';
    $result = $wpdb->delete(
      $trees_table,
      array('gedcom' => $tree_id),
      array('%s')
    );

    return $result !== false;
  }

  /**
   * Clear tree data (keep tree record)
   */
  private function clear_tree_data($tree_id)
  {
    return $this->delete_tree_data($tree_id, true);
  }

  /**
   * Delete tree data from all related tables
   */
  private function delete_tree_data($tree_id, $data_only = true)
  {
    global $wpdb;

    // List of tables that contain tree data
    $tables = array(
      $wpdb->prefix . 'hp_people',
      $wpdb->prefix . 'hp_families',
      $wpdb->prefix . 'hp_sources',
      $wpdb->prefix . 'hp_events',
      $wpdb->prefix . 'hp_xnotes',
      $wpdb->prefix . 'hp_media'
    );

    $success = true;

    foreach ($tables as $table) {
      $result = $wpdb->delete(
        $table,
        array('gedcom' => $tree_id),
        array('%s')
      );

      if ($result === false) {
        $success = false;
      }
    }

    return $success;
  }

  /**
   * AJAX: Add tree
   */
  public function ajax_add_tree()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('edit_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    $tree_id = sanitize_text_field($_POST['tree_id']);
    $tree_name = sanitize_text_field($_POST['tree_name']);

    // Validate inputs
    if (empty($tree_id) || empty($tree_name)) {
      wp_send_json_error('Tree ID and Tree Name are required.');
    }

    // Validate tree ID format
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $tree_id)) {
      wp_send_json_error('Tree ID must be alphanumeric with underscores and hyphens only.');
    }

    // Check if tree ID already exists
    if ($this->tree_id_exists($tree_id)) {
      wp_send_json_error('Tree ID already exists. Please choose a different ID.');
    }    // Create the tree
    $tree_data = array(
      'gedcom' => $tree_id,
      'treename' => $tree_name,
      'description' => '',
      'owner' => wp_get_current_user()->user_login,
      'email' => wp_get_current_user()->user_email,
      'secret' => 0
    );

    $result = $this->create_tree($tree_data);

    if ($result) {
      wp_send_json_success(array(
        'message' => 'Tree created successfully!',
        'tree_id' => $tree_id,
        'tree_name' => $tree_name
      ));
    } else {
      wp_send_json_error('Failed to create tree.');
    }
  }

  /**
   * AJAX: Check tree ID availability
   */
  public function ajax_check_tree_id()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    $tree_id = sanitize_text_field($_POST['tree_id']);
    $exists = $this->tree_id_exists($tree_id);

    wp_send_json_success(array('exists' => $exists));
  }

  /**
   * AJAX: Delete tree
   */
  public function ajax_delete_tree()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('delete_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    $tree_id = sanitize_text_field($_POST['tree_id']);
    $delete_data = isset($_POST['delete_data']) && $_POST['delete_data'] === '1';

    $result = $this->delete_tree($tree_id, $delete_data);

    if ($result) {
      wp_send_json_success('Tree deleted successfully');
    } else {
      wp_send_json_error('Failed to delete tree');
    }
  }

  /**
   * AJAX: Clear tree data
   */
  public function ajax_clear_tree()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('edit_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    $tree_id = sanitize_text_field($_POST['tree_id']);
    $result = $this->clear_tree_data($tree_id);

    if ($result) {
      wp_send_json_success('Tree data cleared successfully');
    } else {
      wp_send_json_error('Failed to clear tree data');
    }
  }
  /**
   * AJAX: Get tree branches
   */
  public function ajax_get_branches()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    $tree_id = sanitize_text_field($_POST['tree_id'] ?? '');

    if (empty($tree_id)) {
      wp_send_json_error('Tree ID is required');
    }

    try {
      $branch_manager = new HP_Branch_Manager();
      $branches = $branch_manager->get_tree_branches($tree_id);

      wp_send_json_success(['branches' => $branches]);
    } catch (Exception $e) {
      error_log('HeritagePress Branch Error: ' . $e->getMessage());
      wp_send_json_error('Failed to retrieve branches');
    }
  }

  /**
   * Display the trees management page
   */
  public function display_page()
  {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Display any notices
    $this->display_notices();

    echo '<div class="wrap">';
    echo '<h1>' . __('Trees Management', 'heritagepress') . '</h1>';
    echo '<p>' . __('Manage your family trees.', 'heritagepress') . '</p>';
    echo '</div>';
  }

  /**
   * AJAX: Add branch
   */
  public function ajax_add_branch()
  {
    try {
      if (!$this->verify_nonce($_POST['nonce'])) {
        wp_send_json_error('Security check failed');
      }

      if (!$this->check_capability('edit_genealogy')) {
        wp_send_json_error('Insufficient permissions');
      }

      $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');
      $branch = sanitize_text_field($_POST['branch'] ?? '');
      $description = sanitize_text_field($_POST['description'] ?? '');
      $person_id = sanitize_text_field($_POST['personID'] ?? '');
      $ancestor_generations = intval($_POST['agens'] ?? 0);
      $descendant_generations = intval($_POST['dgens'] ?? 0);
      $descendant_ancestor_generations = intval($_POST['dagens'] ?? 0);
      $include_spouses = !empty($_POST['inclspouses']);

      $branch_manager = new HP_Branch_Manager();

      // If no branch ID provided, generate one based on description
      if (empty($branch)) {
        $base_name = preg_replace('/[^a-zA-Z0-9]+/', '', strtoupper($description));
        $base_name = substr($base_name, 0, 10);
        $branch = $branch_manager->generate_branch_id($gedcom, $base_name);
      }

      // Validate data
      $validation = $branch_manager->validate_branch_data([
        'gedcom' => $gedcom,
        'branch' => $branch,
        'description' => $description,
        'personID' => $person_id,
        'agens' => $ancestor_generations,
        'dgens' => $descendant_generations,
        'dagens' => $descendant_ancestor_generations
      ]);

      if (!$validation['valid']) {
        wp_send_json_error([
          'message' => 'Validation failed',
          'errors' => $validation['errors']
        ]);
      }

      // Add branch
      $result = $branch_manager->add_branch(
        $gedcom,
        $branch,
        $description,
        $person_id,
        $ancestor_generations,
        $descendant_generations,
        $descendant_ancestor_generations,
        $include_spouses
      );

      if ($result) {
        wp_send_json_success([
          'message' => 'Branch added successfully',
          'branch' => $branch_manager->get_branch($gedcom, $branch)
        ]);
      } else {
        wp_send_json_error('Failed to add branch');
      }
    } catch (Exception $e) {
      error_log('HeritagePress Branch Error: ' . $e->getMessage());
      wp_send_json_error('An error occurred while adding the branch');
    }
  }

  /**
   * AJAX: Update branch
   */
  public function ajax_update_branch()
  {
    try {
      if (!$this->verify_nonce($_POST['nonce'])) {
        wp_send_json_error('Security check failed');
      }

      if (!$this->check_capability('edit_genealogy')) {
        wp_send_json_error('Insufficient permissions');
      }

      $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');
      $branch = sanitize_text_field($_POST['branch'] ?? '');
      $update_data = [];

      // Only include fields that are present in the request
      if (isset($_POST['description'])) {
        $update_data['description'] = sanitize_text_field($_POST['description']);
      }

      if (isset($_POST['personID'])) {
        $update_data['personID'] = sanitize_text_field($_POST['personID']);
      }

      if (isset($_POST['agens'])) {
        $update_data['agens'] = intval($_POST['agens']);
      }

      if (isset($_POST['dgens'])) {
        $update_data['dgens'] = intval($_POST['dgens']);
      }

      if (isset($_POST['dagens'])) {
        $update_data['dagens'] = intval($_POST['dagens']);
      }

      if (isset($_POST['inclspouses'])) {
        $update_data['inclspouses'] = !empty($_POST['inclspouses']);
      }

      $branch_manager = new HP_Branch_Manager();

      if (!$branch_manager->branch_exists($gedcom, $branch)) {
        wp_send_json_error('Branch not found');
      }

      $result = $branch_manager->update_branch($gedcom, $branch, $update_data);

      if ($result) {
        wp_send_json_success([
          'message' => 'Branch updated successfully',
          'branch' => $branch_manager->get_branch($gedcom, $branch)
        ]);
      } else {
        wp_send_json_error('Failed to update branch');
      }
    } catch (Exception $e) {
      error_log('HeritagePress Branch Error: ' . $e->getMessage());
      wp_send_json_error('An error occurred while updating the branch');
    }
  }

  /**
   * AJAX: Delete branch
   */
  public function ajax_delete_branch()
  {
    try {
      if (!$this->verify_nonce($_POST['nonce'])) {
        wp_send_json_error('Security check failed');
      }

      if (!$this->check_capability('delete_genealogy')) {
        wp_send_json_error('Insufficient permissions');
      }

      $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');
      $branch = sanitize_text_field($_POST['branch'] ?? '');

      if (empty($gedcom) || empty($branch)) {
        wp_send_json_error('Tree and branch identifiers are required');
      }

      $branch_manager = new HP_Branch_Manager();

      if (!$branch_manager->branch_exists($gedcom, $branch)) {
        wp_send_json_error('Branch not found');
      }

      $result = $branch_manager->delete_branch($gedcom, $branch);

      if ($result) {
        wp_send_json_success('Branch deleted successfully');
      } else {
        wp_send_json_error('Failed to delete branch');
      }
    } catch (Exception $e) {
      error_log('HeritagePress Branch Error: ' . $e->getMessage());
      wp_send_json_error('An error occurred while deleting the branch');
    }
  }
}
