<?php

/**
 * HeritagePress Admin Trees Controller
 *
 * Handles admin page functionality for trees management
 *
 */

if (!defined('ABSPATH')) {
  exit;
}

require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/controllers/class-hp-base-controller.php';

// Ensure all required WordPress functions are loaded for admin and AJAX
if (!function_exists('current_user_can')) {
  require_once ABSPATH . 'wp-includes/capabilities.php';
}
if (!function_exists('sanitize_textarea_field')) {
  require_once ABSPATH . 'wp-includes/formatting.php';
}
if (!function_exists('sanitize_email')) {
  require_once ABSPATH . 'wp-includes/formatting.php';
}
if (!function_exists('wp_get_current_user')) {
  require_once ABSPATH . 'wp-includes/pluggable.php';
}
if (!function_exists('wp_redirect')) {
  require_once ABSPATH . 'wp-includes/pluggable.php';
}
if (!function_exists('check_ajax_referer')) {
  require_once ABSPATH . 'wp-includes/pluggable.php';
}

class HP_Admin_Trees_Controller extends HP_Base_Controller
{
  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct('admin-trees');
    $this->capabilities = array(
      'manage_trees' => 'manage_options',
      'edit_trees' => 'manage_options',
      'delete_trees' => 'manage_options'
    );

    $this->init_hooks();
  }

  /**
   * Initialize hooks
   */
  private function init_hooks()
  {
    // Handle form submissions before headers are sent
    add_action('admin_init', array($this, 'handle_form_submissions'));

    // Register AJAX handlers
    add_action('wp_ajax_hp_add_tree', array($this, 'ajax_add_tree'));
  }

  /**
   * Handle admin form submissions
   */
  public function handle_form_submissions()
  {
    // Only handle on our admin page
    if (!isset($_GET['page']) || $_GET['page'] !== 'heritagepress-trees') {
      return;
    }

    // Handle add tree form submission
    if (isset($_POST['action']) && $_POST['action'] === 'add_tree') {
      $this->handle_add_tree_form();
    }

    // Handle update tree form submission
    if (isset($_POST['action']) && $_POST['action'] === 'update_tree') {
      $this->handle_update_tree_form();
    }

    // Handle bulk actions
    if (isset($_POST['action']) && $_POST['action'] !== '-1' && !empty($_POST['tree_ids'])) {
      $this->handle_bulk_actions();
    }
  }

  /**
   * Handle add tree form submission (for non-AJAX context)
   */
  private function handle_add_tree_form()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'heritagepress_add_tree')) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    // Check permissions
    if (!current_user_can('manage_options')) {
      $this->add_notice(__('You do not have sufficient permissions.', 'heritagepress'), 'error');
      return;
    }    // Process the form for adding a genealogy tree
    global $wpdb;
    $trees_table = $wpdb->prefix . 'hp_trees';

    // Sanitize and validate data
    $gedcom = preg_replace("/\s*/", "", sanitize_text_field($_POST['gedcom']));
    $treename = sanitize_text_field($_POST['treename']);
    $description = sanitize_textarea_field($_POST['description']);
    $owner = sanitize_text_field($_POST['owner']);
    $email = sanitize_email($_POST['email']);
    $address = sanitize_textarea_field($_POST['address']);
    $city = sanitize_text_field($_POST['city']);
    $state = sanitize_text_field($_POST['state']);
    $country = sanitize_text_field($_POST['country']);
    $zip = sanitize_text_field($_POST['zip']);
    $phone = sanitize_text_field($_POST['phone']);

    // Handle checkboxes
    $private = isset($_POST['private']) ? 1 : 0;
    $disallowgedcreate = isset($_POST['disallowgedcreate']) ? 1 : 0;
    $disallowpdf = isset($_POST['disallowpdf']) ? 1 : 0;

    // Validate required fields
    if (empty($gedcom)) {
      $this->add_notice(__('Tree ID is required.', 'heritagepress'), 'error');
      return;
    }

    if (empty($treename)) {
      $this->add_notice(__('Tree Name is required.', 'heritagepress'), 'error');
      return;
    }

    // Validate tree ID format
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $gedcom)) {
      $this->add_notice(__('Tree ID must contain only letters, numbers, underscores, and hyphens.', 'heritagepress'), 'error');
      return;
    }

    // Check for existing tree
    $existing = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $trees_table WHERE gedcom = %s",
      $gedcom
    ));

    if ($existing > 0) {
      $this->add_notice(__('Tree ID already exists. Please choose a different ID.', 'heritagepress'), 'error');
      return;
    }

    // Insert tree record
    $result = $wpdb->insert(
      $trees_table,
      array(
        'gedcom' => $gedcom,
        'treename' => $treename,
        'description' => $description,
        'owner' => $owner,
        'email' => $email,
        'address' => $address,
        'city' => $city,
        'state' => $state,
        'country' => $country,
        'zip' => $zip,
        'phone' => $phone,
        'secret' => $private,
        'disallowgedcreate' => $disallowgedcreate,
        'disallowpdf' => $disallowpdf,
        'date_created' => current_time('mysql')
      ),
      array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s')
    );

    if ($result === 1) {
      // Log the action
      $current_user = wp_get_current_user();
      error_log("HeritagePress: Tree added - $gedcom/$treename by " . $current_user->user_login);

      // Handle redirect based on submit button
      if (isset($_POST['submitx'])) {
        // Save and return to trees list
        $message = sprintf(__('Tree "%s" was successfully added.', 'heritagepress'), stripslashes($treename));
        $redirect_url = add_query_arg(
          array(
            'page' => 'heritagepress-trees',
            'message' => urlencode($message)
          ),
          admin_url('admin.php')
        );
      } else {
        // Save and edit tree
        $redirect_url = add_query_arg(
          array(
            'page' => 'heritagepress-trees',
            'tab' => 'edit',
            'tree' => $gedcom,
            'action' => 'edit'
          ),
          admin_url('admin.php')
        );
      }

      wp_redirect($redirect_url);
      exit;
    } else {
      $this->add_notice(__('Failed to create tree. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle update tree form submission
   */
  private function handle_update_tree_form()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'heritagepress_update_tree')) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    // Check permissions
    if (!current_user_can('manage_options')) {
      $this->add_notice(__('You do not have sufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    global $wpdb;
    $trees_table = $wpdb->prefix . 'hp_trees';

    $tree_id = sanitize_text_field($_POST['tree_id']);

    // Update tree data
    $update_data = array(
      'treename' => sanitize_text_field($_POST['treename']),
      'description' => sanitize_textarea_field($_POST['description']),
      'owner' => sanitize_text_field($_POST['owner']),
      'email' => sanitize_email($_POST['email']),
      'address' => sanitize_textarea_field($_POST['address']),
      'city' => sanitize_text_field($_POST['city']),
      'state' => sanitize_text_field($_POST['state']),
      'country' => sanitize_text_field($_POST['country']),
      'zip' => sanitize_text_field($_POST['zip']),
      'phone' => sanitize_text_field($_POST['phone']),
      'secret' => isset($_POST['private']) ? 1 : 0,
      'disallowgedcreate' => isset($_POST['disallowgedcreate']) ? 1 : 0,
      'disallowpdf' => isset($_POST['disallowpdf']) ? 1 : 0
    );

    $result = $wpdb->update(
      $trees_table,
      $update_data,
      array('gedcom' => $tree_id),
      array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d'),
      array('%s')
    );

    if ($result !== false) {
      $this->add_notice(__('Tree updated successfully!', 'heritagepress'), 'success');
    } else {
      $this->add_notice(__('Failed to update tree.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle bulk actions on trees
   */
  private function handle_bulk_actions()
  {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'heritagepress_bulk_trees')) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!current_user_can('manage_options')) {
      $this->add_notice(__('You do not have sufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    $tree_ids = array_map('sanitize_text_field', $_POST['tree_ids']);
    $action = sanitize_text_field($_POST['action']);

    global $wpdb;
    $trees_table = $wpdb->prefix . 'hp_trees';

    switch ($action) {
      case 'delete':
        foreach ($tree_ids as $tree_id) {
          // Delete tree and all associated data
          $this->delete_tree_completely($tree_id);
        }
        $this->add_notice(sprintf(__('%d trees deleted.', 'heritagepress'), count($tree_ids)), 'success');
        break;

      case 'clear_data':
        foreach ($tree_ids as $tree_id) {
          // Clear tree data but keep tree record
          $this->clear_tree_data($tree_id);
        }
        $this->add_notice(sprintf(__('%d trees cleared.', 'heritagepress'), count($tree_ids)), 'success');
        break;
    }
  }

  /**
   * Delete tree completely (tree record and all data)
   */
  private function delete_tree_completely($tree_id)
  {
    global $wpdb;

    // Delete from related tables first
    $tables = array(
      $wpdb->prefix . 'hp_people',
      $wpdb->prefix . 'hp_families',
      $wpdb->prefix . 'hp_children',
      $wpdb->prefix . 'hp_events',
      $wpdb->prefix . 'hp_sources',
      $wpdb->prefix . 'hp_media',
      $wpdb->prefix . 'hp_medialinks',
      $wpdb->prefix . 'hp_xnotes',
      $wpdb->prefix . 'hp_notelinks',
      $wpdb->prefix . 'hp_branches',
      $wpdb->prefix . 'hp_branchlinks'
    );

    foreach ($tables as $table) {
      $wpdb->delete($table, array('gedcom' => $tree_id), array('%s'));
    }

    // Finally delete the tree record
    $trees_table = $wpdb->prefix . 'hp_trees';
    $wpdb->delete($trees_table, array('gedcom' => $tree_id), array('%s'));
  }

  /**
   * Clear tree data but keep tree record
   */
  private function clear_tree_data($tree_id)
  {
    global $wpdb;

    // Clear data from related tables but keep tree record
    $tables = array(
      $wpdb->prefix . 'hp_people',
      $wpdb->prefix . 'hp_families',
      $wpdb->prefix . 'hp_children',
      $wpdb->prefix . 'hp_events',
      $wpdb->prefix . 'hp_sources',
      $wpdb->prefix . 'hp_media',
      $wpdb->prefix . 'hp_medialinks',
      $wpdb->prefix . 'hp_xnotes',
      $wpdb->prefix . 'hp_notelinks',
      $wpdb->prefix . 'hp_branchlinks'
    );

    foreach ($tables as $table) {
      $wpdb->delete($table, array('gedcom' => $tree_id), array('%s'));
    }
  }

  /**
   * AJAX: Add new tree (for import modal)
   */
  public function ajax_add_tree()
  {
    check_ajax_referer('heritagepress_add_tree', 'nonce');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(__('You do not have sufficient permissions.', 'heritagepress'));
    }
    global $wpdb;
    $trees_table = $wpdb->prefix . 'hp_trees';
    $gedcom = preg_replace("/\s*/", "", sanitize_text_field($_POST['tree_id']));
    $treename = sanitize_text_field($_POST['tree_name']);
    if (empty($gedcom) || empty($treename)) {
      wp_send_json_error(__('Tree ID and Name are required.', 'heritagepress'));
    }
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $gedcom)) {
      wp_send_json_error(__('Tree ID must be alphanumeric (letters, numbers, underscores, hyphens).', 'heritagepress'));
    }
    $existing = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $trees_table WHERE gedcom = %s",
      $gedcom
    ));
    if ($existing > 0) {
      wp_send_json_error(__('Tree ID already exists.', 'heritagepress'));
    }
    $result = $wpdb->insert(
      $trees_table,
      array(
        'gedcom' => $gedcom,
        'treename' => $treename,
        'date_created' => current_time('mysql')
      ),
      array('%s', '%s', '%s')
    );
    if ($result === 1) {
      wp_send_json_success(array('gedcom' => $gedcom, 'treename' => $treename));
    } else {
      wp_send_json_error(__('Failed to create tree.', 'heritagepress'));
    }
  }
}

// Initialize the admin trees controller
new HP_Admin_Trees_Controller();
