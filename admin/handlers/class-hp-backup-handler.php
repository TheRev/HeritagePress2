<?php

/**
 * HeritagePress Backup Handler
 *
 * Handles AJAX requests for backup operations
 *
 * @package    HeritagePress
 * @subpackage Admin\Handlers
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Backup_Handler
{

  /**
   * Initialize the handler
   */
  public static function init()
  {
    add_action('wp_ajax_hp_backup_table', [self::class, 'handle_backup_table']);
    add_action('wp_ajax_hp_backup_structure', [self::class, 'handle_backup_structure']);
    add_action('wp_ajax_hp_delete_backup', [self::class, 'handle_delete_backup']);
    add_action('wp_ajax_hp_download_backup', [self::class, 'handle_download_backup']);
    add_action('wp_ajax_hp_get_backup_info', [self::class, 'handle_get_backup_info']);
  }

  /**
   * Handle backup table AJAX request
   */
  public static function handle_backup_table()
  {
    // Check nonce and permissions
    if (!check_ajax_referer('hp_backup_operation', 'nonce', false)) {
      wp_send_json_error(['message' => __('Security check failed', 'heritagepress')]);
    }

    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => __('You do not have permission to perform this action', 'heritagepress')]);
    }

    $table = isset($_POST['table']) ? sanitize_text_field($_POST['table']) : '';

    if (empty($table)) {
      wp_send_json_error(['message' => __('No table specified', 'heritagepress')]);
    }

    // Get backup options
    $include_sql = isset($_POST['include_sql']) ? (bool) $_POST['include_sql'] : true;
    $include_create = isset($_POST['include_create']) ? (bool) $_POST['include_create'] : true;
    $include_drop = isset($_POST['include_drop']) ? (bool) $_POST['include_drop'] : true;

    // Create backup controller
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/controllers/class-hp-backup-controller.php';
    $backup_controller = new HP_Backup_Controller();

    // Check if this is a valid table
    $genealogy_tables = $backup_controller->get_genealogy_tables();
    if ($table != 'all' && !in_array($table, $genealogy_tables)) {
      wp_send_json_error(['message' => __('Invalid table name', 'heritagepress')]);
    }

    // Handle multi-table backup
    if ($table === 'all') {
      $selected_tables = isset($_POST['selected_tables']) ? array_map('sanitize_text_field', $_POST['selected_tables']) : $genealogy_tables;

      $results = [];
      $success_count = 0;

      foreach ($selected_tables as $single_table) {
        if (in_array($single_table, $genealogy_tables)) {
          $result = $backup_controller->backup_table($single_table, $include_sql, $include_create, $include_drop);
          $results[$single_table] = $result;

          if ($result['success']) {
            $success_count++;
          }
        }
      }

      $message = sprintf(
        __('Backup completed for %d of %d tables', 'heritagepress'),
        $success_count,
        count($selected_tables)
      );

      wp_send_json_success([
        'message' => $message,
        'results' => $results
      ]);
    } else {
      // Single table backup
      $result = $backup_controller->backup_table($table, $include_sql, $include_create, $include_drop);

      if ($result['success']) {
        wp_send_json_success($result);
      } else {
        wp_send_json_error($result);
      }
    }
  }

  /**
   * Handle backup structure AJAX request
   */
  public static function handle_backup_structure()
  {
    // Check nonce and permissions
    if (!check_ajax_referer('hp_backup_operation', 'nonce', false)) {
      wp_send_json_error(['message' => __('Security check failed', 'heritagepress')]);
    }

    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => __('You do not have permission to perform this action', 'heritagepress')]);
    }

    // Create backup controller
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/controllers/class-hp-backup-controller.php';
    $backup_controller = new HP_Backup_Controller();

    // Perform table structure backup
    $result = $backup_controller->backup_structure();

    if ($result['success']) {
      wp_send_json_success($result);
    } else {
      wp_send_json_error($result);
    }
  }

  /**
   * Handle delete backup AJAX request
   */
  public static function handle_delete_backup()
  {
    // Check nonce and permissions
    if (!check_ajax_referer('hp_backup_operation', 'nonce', false)) {
      wp_send_json_error(['message' => __('Security check failed', 'heritagepress')]);
    }

    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => __('You do not have permission to perform this action', 'heritagepress')]);
    }

    $table = isset($_POST['table']) ? sanitize_text_field($_POST['table']) : '';

    if (empty($table)) {
      wp_send_json_error(['message' => __('No table specified', 'heritagepress')]);
    }

    // Create backup controller
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/controllers/class-hp-backup-controller.php';
    $backup_controller = new HP_Backup_Controller();

    // Delete backup
    $result = $backup_controller->delete_backup($table);

    if ($result['success']) {
      wp_send_json_success($result);
    } else {
      wp_send_json_error($result);
    }
  }

  /**
   * Handle download backup request
   */
  public static function handle_download_backup()
  {
    // Check nonce and permissions
    if (!check_ajax_referer('hp_download_backup', '_wpnonce', false)) {
      wp_die(__('Security check failed', 'heritagepress'));
    }

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have permission to perform this action', 'heritagepress'));
    }

    $file = isset($_GET['file']) ? sanitize_file_name($_GET['file']) : '';

    if (empty($file)) {
      wp_die(__('No file specified', 'heritagepress'));
    }

    // Create backup controller
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/controllers/class-hp-backup-controller.php';
    $backup_controller = new HP_Backup_Controller();

    $backup_dir = $backup_controller->get_backup_dir();
    $file_path = $backup_dir . '/' . $file;

    // Verify file exists and is within the backup directory
    if (!file_exists($file_path) || strpos($file_path, $backup_dir) !== 0) {
      wp_die(__('File not found or invalid', 'heritagepress'));
    }

    // Set headers for download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));

    // Clear output buffer
    ob_clean();
    flush();

    // Output file content
    readfile($file_path);
    exit;
  }

  /**
   * Handle get backup info AJAX request
   */
  public static function handle_get_backup_info()
  {
    // Check nonce and permissions
    if (!check_ajax_referer('hp_backup_operation', 'nonce', false)) {
      wp_send_json_error(['message' => __('Security check failed', 'heritagepress')]);
    }

    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => __('You do not have permission to perform this action', 'heritagepress')]);
    }

    $table = isset($_POST['table']) ? sanitize_text_field($_POST['table']) : '';

    if (empty($table)) {
      wp_send_json_error(['message' => __('No table specified', 'heritagepress')]);
    }

    // Create backup controller
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/controllers/class-hp-backup-controller.php';
    $backup_controller = new HP_Backup_Controller();

    // Get backup info
    $result = $backup_controller->get_backup_info($table);

    wp_send_json_success($result);
  }
}
