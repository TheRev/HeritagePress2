<?php

/**
 * Form Handler Class
 * Handles all form submissions for HeritagePress
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Form_Handler
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
    add_action('admin_post_hp_save_settings', array($this, 'handle_save_settings'));
    add_action('admin_post_hp_import_gedcom', array($this, 'handle_import_gedcom'));
    add_action('admin_post_hp_export_gedcom', array($this, 'handle_export_gedcom'));
  }

  /**
   * Handle settings save
   */
  public function handle_save_settings()
  {
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'hp_save_settings')) {
      wp_die('Invalid nonce');
    }

    if (!current_user_can('manage_options')) {
      wp_die('Permission denied');
    }

    // Process settings
    $settings = array(
      'tree_privacy' => isset($_POST['tree_privacy']) ? sanitize_text_field($_POST['tree_privacy']) : 'private',
      'date_format' => isset($_POST['date_format']) ? sanitize_text_field($_POST['date_format']) : 'Y-m-d',
      'name_format' => isset($_POST['name_format']) ? sanitize_text_field($_POST['name_format']) : 'first_last'
    );

    update_option('heritagepress_settings', $settings);

    // Redirect back with success message
    wp_redirect(add_query_arg('settings-updated', 'true', wp_get_referer()));
    exit;
  }

  /**
   * Handle GEDCOM import form
   */
  public function handle_import_gedcom()
  {
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'hp_import_gedcom')) {
      wp_die('Invalid nonce');
    }

    if (!current_user_can('manage_options')) {
      wp_die('Permission denied');
    }

    $tree_id = isset($_POST['tree_id']) ? sanitize_text_field($_POST['tree_id']) : '';
    $file = isset($_FILES['gedcom']) ? $_FILES['gedcom'] : null;

    if (!$file || !$tree_id) {
      wp_die('Missing required fields');
    }

    try {
      require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/class-hp-gedcom-importer.php';
      $importer = new HP_GEDCOM_Importer();
      $result = $importer->import($file['tmp_name'], $tree_id);

      wp_redirect(add_query_arg(array(
        'page' => 'heritagepress-import',
        'import-success' => 'true',
        'records' => $result['total_records']
      ), admin_url('admin.php')));
      exit;
    } catch (Exception $e) {
      wp_die($e->getMessage());
    }
  }

  /**
   * Handle GEDCOM export form
   */
  public function handle_export_gedcom()
  {
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'hp_export_gedcom')) {
      wp_die('Invalid nonce');
    }

    if (!current_user_can('manage_options')) {
      wp_die('Permission denied');
    }

    $tree_id = isset($_POST['tree_id']) ? sanitize_text_field($_POST['tree_id']) : '';

    if (!$tree_id) {
      wp_die('Missing tree ID');
    }

    try {
      require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/class-hp-gedcom-exporter.php';
      $exporter = new HP_GEDCOM_Exporter();
      $file = $exporter->export($tree_id);

      header('Content-Type: text/plain');
      header('Content-Disposition: attachment; filename="' . sanitize_file_name($tree_id . '.ged') . '"');
      readfile($file);
      unlink($file); // Clean up temporary file
      exit;
    } catch (Exception $e) {
      wp_die($e->getMessage());
    }
  }
}
