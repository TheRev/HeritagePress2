<?php

/**
 * HeritagePress Clear Tree Handler
 *
 * Replicates HeritagePress admin_cleartree.php functionality
 * Direct endpoint for clearing tree data with proper logging
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Clear_Tree_Handler
{
  /**
   * Constructor
   */
  public function __construct()
  {
    $this->init_hooks();
  }

  /**
   * Initialize hooks
   */
  private function init_hooks()
  {
    // Handle GET requests to clear tree (like HeritagePress)
    add_action('wp_loaded', array($this, 'handle_clear_tree_request'));
  }

  /**
   * Handle direct clear tree requests
   * Replicates HeritagePress admin_cleartree.php behavior
   */
  public function handle_clear_tree_request()
  {
    // Check if this is a clear tree request
    if (!isset($_GET['hp_action']) || $_GET['hp_action'] !== 'clear_tree') {
      return;
    }

    // Verify user is in admin area and has permissions
    if (!is_admin()) {
      wp_die(__('Access denied.', 'heritagepress'));
    }

    if (!current_user_can('delete_genealogy')) {
      $message = __('You do not have sufficient permissions to clear tree data.', 'heritagepress');
      wp_redirect(admin_url('admin.php?page=heritagepress-trees&message=' . urlencode($message)));
      exit;
    }

    // Get and validate tree ID
    $gedcom = isset($_GET['gedcom']) ? sanitize_text_field($_GET['gedcom']) : '';
    $tree = isset($_GET['tree']) ? sanitize_text_field($_GET['tree']) : $gedcom;

    if (empty($gedcom)) {
      $gedcom = '';
    }

    // Verify tree exists
    if (!empty($gedcom) && !$this->tree_exists($gedcom)) {
      $message = __('Tree not found.', 'heritagepress');
      wp_redirect(admin_url('admin.php?page=heritagepress-trees&message=' . urlencode($message)));
      exit;
    }

    // Verify nonce if provided (recommended but HeritagePress doesn't use this)
    if (isset($_GET['_wpnonce']) && !wp_verify_nonce($_GET['_wpnonce'], 'heritagepress_clear_tree_' . $gedcom)) {
      wp_die(__('Security check failed.', 'heritagepress'));
    }

    // Perform tree clearing
    $result = $this->clear_tree_data($gedcom);

    // Log the action (replicates HeritagePress adminwritelog)
    $this->log_tree_action($gedcom, $tree);

    // Prepare success message
    if (!empty($gedcom)) {
      $tree_name = $this->get_tree_name($gedcom);
      $message = sprintf(__('Tree "%s" was successfully cleared.', 'heritagepress'), $tree_name);
    } else {
      $message = __('Tree was successfully cleared.', 'heritagepress');
    }

    // Redirect back to trees admin (replicates HeritagePress behavior)
    wp_redirect(admin_url('admin.php?page=heritagepress-trees&message=' . urlencode($message)));
    exit;
  }

  /**
   * Check if tree exists
   */
  private function tree_exists($gedcom)
  {
    global $wpdb;
    $trees_table = $wpdb->prefix . 'hp_trees';

    $count = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$trees_table} WHERE gedcom = %s",
      $gedcom
    ));

    return intval($count) > 0;
  }

  /**
   * Get tree name for display
   */
  private function get_tree_name($gedcom)
  {
    global $wpdb;
    $trees_table = $wpdb->prefix . 'hp_trees';

    $tree_name = $wpdb->get_var($wpdb->prepare(
      "SELECT treename FROM {$trees_table} WHERE gedcom = %s",
      $gedcom
    ));

    return $tree_name ? $tree_name : $gedcom;
  }

  /**
   * Clear tree data but keep tree configuration
   * Replicates the core clearing functionality
   */
  private function clear_tree_data($gedcom)
  {
    global $wpdb;

    // List of tables that contain tree data (matches HeritagePress pattern)
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
      $wpdb->prefix . 'hp_branchlinks',
      $wpdb->prefix . 'hp_repositories',
      $wpdb->prefix . 'hp_citations'
    );

    $success = true;

    // Clear data from each table
    foreach ($tables as $table) {
      // Check if table exists before attempting to clear
      $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");

      if ($table_exists) {
        $result = $wpdb->delete(
          $table,
          array('gedcom' => $gedcom),
          array('%s')
        );

        if ($result === false) {
          $success = false;
          error_log("HeritagePress: Failed to clear data from table {$table} for tree {$gedcom}");
        }
      }
    }

    return $success;
  }

  /**
   * Log tree clearing action
   * Replicates HeritagePress adminwritelog functionality
   */
  private function log_tree_action($gedcom, $tree)
  {
    $current_user = wp_get_current_user();
    $action = sprintf(__('Tree cleared: %s', 'heritagepress'), $tree);

    // Log to WordPress error log
    error_log("HeritagePress: {$action} by {$current_user->user_login}");

    // Could also log to custom admin log table if implemented
    // This would match HeritagePress's adminlog.php functionality
    do_action('heritagepress_log_admin_action', 'clear_tree', $gedcom, $action, $current_user->ID);
  }

  /**
   * Generate clear tree URL (helper method)
   * Creates URLs that match HeritagePress pattern
   */
  public static function get_clear_tree_url($gedcom, $tree_name = null)
  {
    $args = array(
      'hp_action' => 'clear_tree',
      'gedcom' => $gedcom
    );

    if ($tree_name) {
      $args['tree'] = $tree_name;
    }

    // Add nonce for security
    $args['_wpnonce'] = wp_create_nonce('heritagepress_clear_tree_' . $gedcom);

    return add_query_arg($args, admin_url('admin.php'));
  }
}

// Initialize the clear tree handler
new HP_Clear_Tree_Handler();
