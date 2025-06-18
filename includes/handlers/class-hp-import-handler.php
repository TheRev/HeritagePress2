<?php

/**
 * Import Handler
 *
 * Handles form submissions and AJAX requests for import functionality
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Import_Handler
{
  /**
   * Initialize the handler
   */
  public function __construct()
  {
    $this->register_hooks();
  }

  /**
   * Register WordPress hooks
   */
  private function register_hooks()
  {
    // Handle form submissions via admin-post.php
    add_action('admin_post_hp_import_gedcom', array($this, 'handle_gedcom_import'));

    // Add nonce verification and redirect on failed auth
    add_action('admin_post_nopriv_hp_import_gedcom', array($this, 'handle_unauthorized_access'));

    // AJAX handlers
    add_action('wp_ajax_hp_validate_gedcom', array($this, 'ajax_validate_gedcom'));
    add_action('wp_ajax_hp_start_import', array($this, 'ajax_start_import'));
    add_action('wp_ajax_hp_check_import_status', array($this, 'ajax_check_import_status'));
  }

  /**
   * Handle GEDCOM import form submission
   */
  public function handle_gedcom_import()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_import_gedcom')) {
      wp_die(__('Security check failed. Please try again.', 'heritagepress'));
    }

    // Check user capabilities
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to perform this action.', 'heritagepress'));
    }

    // Get form data
    $tree_id = isset($_POST['tree_id']) ? sanitize_text_field($_POST['tree_id']) : '';
    $import_type = isset($_POST['import_type']) ? sanitize_text_field($_POST['import_type']) : 'file';

    // Validate required fields
    if (empty($tree_id)) {
      $this->redirect_with_error(__('Please select a tree for import.', 'heritagepress'));
      return;
    }

    try {
      $result = false;

      if ($import_type === 'file' && isset($_FILES['gedcom_file'])) {
        // Handle file upload
        $result = $this->process_file_upload($tree_id);
      } elseif ($import_type === 'server' && isset($_POST['server_file'])) {
        // Handle server file
        $result = $this->process_server_file($tree_id, $_POST['server_file']);
      } else {
        throw new Exception(__('No valid file selected for import.', 'heritagepress'));
      }

      if ($result) {
        $this->redirect_with_success(__('GEDCOM import completed successfully.', 'heritagepress'));
      } else {
        throw new Exception(__('Import failed. Please check the file and try again.', 'heritagepress'));
      }
    } catch (Exception $e) {
      error_log('HeritagePress GEDCOM Import Error: ' . $e->getMessage());
      $this->redirect_with_error($e->getMessage());
    }
  }

  /**
   * Process uploaded file
   */
  private function process_file_upload($tree_id)
  {
    if (!isset($_FILES['gedcom_file']) || $_FILES['gedcom_file']['error'] !== UPLOAD_ERR_OK) {
      throw new Exception(__('File upload failed.', 'heritagepress'));
    }

    $file = $_FILES['gedcom_file'];

    // Validate file type
    $allowed_types = array('ged', 'gedcom');
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($file_ext, $allowed_types)) {
      throw new Exception(__('Invalid file type. Please upload a .ged or .gedcom file.', 'heritagepress'));
    }

    // Get import controller to handle the actual import
    $import_controller = new HP_Import_Controller();
    return $import_controller->import_gedcom_file($file['tmp_name'], $tree_id);
  }

  /**
   * Process server file
   */
  private function process_server_file($tree_id, $server_file)
  {
    $server_file = sanitize_file_name($server_file);

    // Validate server file exists and is in the proper directory
    $import_controller = new HP_Import_Controller();
    $file_path = $import_controller->get_server_file_path($server_file);

    if (!file_exists($file_path)) {
      throw new Exception(__('Selected server file not found.', 'heritagepress'));
    }

    return $import_controller->import_gedcom_file($file_path, $tree_id);
  }

  /**
   * Handle unauthorized access
   */
  public function handle_unauthorized_access()
  {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }

  /**
   * AJAX handler for GEDCOM validation
   */
  public function ajax_validate_gedcom()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'hp_ajax_nonce')) {
      wp_send_json_error(__('Security check failed.', 'heritagepress'));
    }

    // Check capabilities
    if (!current_user_can('manage_options')) {
      wp_send_json_error(__('Insufficient permissions.', 'heritagepress'));
    }

    // Validate GEDCOM file (implementation depends on your validation logic)
    $file_path = sanitize_text_field($_POST['file_path']);

    // Basic validation - you can expand this
    if (file_exists($file_path)) {
      wp_send_json_success(array(
        'valid' => true,
        'message' => __('GEDCOM file is valid.', 'heritagepress')
      ));
    } else {
      wp_send_json_error(__('File not found.', 'heritagepress'));
    }
  }

  /**
   * AJAX handler for starting import
   */
  public function ajax_start_import()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'hp_ajax_nonce')) {
      wp_send_json_error(__('Security check failed.', 'heritagepress'));
    }

    // Check capabilities
    if (!current_user_can('manage_options')) {
      wp_send_json_error(__('Insufficient permissions.', 'heritagepress'));
    }

    // Start background import process
    $tree_id = sanitize_text_field($_POST['tree_id']);
    $file_path = sanitize_text_field($_POST['file_path']);

    // You can implement background processing here
    wp_send_json_success(array(
      'job_id' => uniqid('import_'),
      'message' => __('Import started.', 'heritagepress')
    ));
  }

  /**
   * AJAX handler for checking import status
   */
  public function ajax_check_import_status()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'hp_ajax_nonce')) {
      wp_send_json_error(__('Security check failed.', 'heritagepress'));
    }

    $job_id = sanitize_text_field($_POST['job_id']);

    // Check status (implement your status checking logic)
    wp_send_json_success(array(
      'status' => 'completed',
      'progress' => 100,
      'message' => __('Import completed.', 'heritagepress')
    ));
  }

  /**
   * Redirect with error message
   */
  private function redirect_with_error($message)
  {
    $redirect_url = add_query_arg(array(
      'page' => 'heritagepress-import',
      'hp_error' => urlencode($message)
    ), admin_url('admin.php'));

    wp_redirect($redirect_url);
    exit;
  }

  /**
   * Redirect with success message
   */
  private function redirect_with_success($message)
  {
    $redirect_url = add_query_arg(array(
      'page' => 'heritagepress-import',
      'hp_success' => urlencode($message)
    ), admin_url('admin.php'));

    wp_redirect($redirect_url);
    exit;
  }
}
