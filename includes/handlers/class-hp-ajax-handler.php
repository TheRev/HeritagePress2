<?php

/**
 * AJAX Handler Class
 * Handles all AJAX requests for HeritagePress
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_AJAX_Handler
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
    // AJAX actions for logged in users
    add_action('wp_ajax_hp_import_gedcom', array($this, 'handle_import_gedcom'));
    add_action('wp_ajax_hp_save_tree', array($this, 'handle_save_tree'));
    add_action('wp_ajax_hp_delete_tree', array($this, 'handle_delete_tree'));
    add_action('wp_ajax_hp_get_tree_data', array($this, 'handle_get_tree_data'));

    // AJAX actions for non-logged in users
    add_action('wp_ajax_nopriv_hp_public_tree_data', array($this, 'handle_public_tree_data'));
  }

  /**
   * Handle GEDCOM import
   */
  public function handle_import_gedcom()
  {
    check_ajax_referer('hp_ajax_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error('Permission denied');
    }

    $file = isset($_FILES['gedcom']) ? $_FILES['gedcom'] : null;
    $tree_id = isset($_POST['tree_id']) ? sanitize_text_field($_POST['tree_id']) : '';

    if (!$file || !$tree_id) {
      wp_send_json_error('Missing required fields');
    }

    try {
      require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/class-hp-gedcom-importer.php';
      $importer = new HP_GEDCOM_Importer();
      $result = $importer->import($file['tmp_name'], $tree_id);
      wp_send_json_success($result);
    } catch (Exception $e) {
      wp_send_json_error($e->getMessage());
    }
  }

  /**
   * Handle tree save
   */
  public function handle_save_tree()
  {
    check_ajax_referer('hp_ajax_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error('Permission denied');
    }

    $tree_data = isset($_POST['tree_data']) ? $_POST['tree_data'] : array();

    if (empty($tree_data)) {
      wp_send_json_error('No tree data provided');
    }

    try {
      require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/core/class-hp-tree-manager.php';
      $tree_manager = new HP_Tree_Manager();
      $result = $tree_manager->save_tree($tree_data);
      wp_send_json_success($result);
    } catch (Exception $e) {
      wp_send_json_error($e->getMessage());
    }
  }

  /**
   * Handle tree deletion
   */
  public function handle_delete_tree()
  {
    check_ajax_referer('hp_ajax_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error('Permission denied');
    }

    $tree_id = isset($_POST['tree_id']) ? sanitize_text_field($_POST['tree_id']) : '';

    if (!$tree_id) {
      wp_send_json_error('No tree ID provided');
    }

    try {
      require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/core/class-hp-tree-manager.php';
      $tree_manager = new HP_Tree_Manager();
      $result = $tree_manager->delete_tree($tree_id);
      wp_send_json_success($result);
    } catch (Exception $e) {
      wp_send_json_error($e->getMessage());
    }
  }

  /**
   * Handle get tree data
   */
  public function handle_get_tree_data()
  {
    check_ajax_referer('hp_ajax_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error('Permission denied');
    }

    $tree_id = isset($_GET['tree_id']) ? sanitize_text_field($_GET['tree_id']) : '';

    if (!$tree_id) {
      wp_send_json_error('No tree ID provided');
    }

    try {
      require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/core/class-hp-tree-manager.php';
      $tree_manager = new HP_Tree_Manager();
      $result = $tree_manager->get_tree($tree_id);
      wp_send_json_success($result);
    } catch (Exception $e) {
      wp_send_json_error($e->getMessage());
    }
  }

  /**
   * Handle public tree data
   */
  public function handle_public_tree_data()
  {
    $tree_id = isset($_GET['tree_id']) ? sanitize_text_field($_GET['tree_id']) : '';

    if (!$tree_id) {
      wp_send_json_error('No tree ID provided');
    }

    try {
      require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/core/class-hp-tree-manager.php';
      $tree_manager = new HP_Tree_Manager();
      $result = $tree_manager->get_public_tree_data($tree_id);
      wp_send_json_success($result);
    } catch (Exception $e) {
      wp_send_json_error($e->getMessage());
    }
  }
}
