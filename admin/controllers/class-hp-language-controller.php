<?php

/**
 * Language Management Controller
 *
 * Handles CRUD operations for language management
 * This includes adding, updating, deleting, and retrieving languages,
 * as well as searching languages with pagination.
 * This controller also manages the creation of the languages table
 * and ensures that the default English language is present.
 * It provides AJAX endpoints for these operations
 * to be used in the WordPress admin interface.
 * It also includes functionality to log admin actions
 * for auditing purposes.
 * This controller is part of the HeritagePress plugin
 * and is designed to work with the WordPress database.
 * It uses the WordPress database abstraction layer
 * to interact with the database in a secure and efficient manner.
 * It also includes methods to retrieve available language folders
 * from the filesystem, which can be used to populate dropdowns
 * or other UI elements in the admin interface.
 * This controller is intended to be used in conjunction
 * with the HeritagePress plugin's admin interface
 * to provide a comprehensive language management solution
 * for WordPress sites that use the HeritagePress plugin.
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Language_Controller
{

  private $table_name;

  public function __construct()
  {
    global $wpdb;
    $this->table_name = $wpdb->prefix . 'hp_languages';

    add_action('wp_ajax_heritagepress_add_language', array($this, 'ajax_add_language'));
    add_action('wp_ajax_heritagepress_update_language', array($this, 'ajax_update_language'));
    add_action('wp_ajax_heritagepress_delete_language', array($this, 'ajax_delete_language'));
    add_action('wp_ajax_heritagepress_get_language', array($this, 'ajax_get_language'));
    add_action('wp_ajax_heritagepress_search_languages', array($this, 'ajax_search_languages'));
  }

  /**
   * Create languages table if it doesn't exist
   */
  public function create_table()
  {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$this->table_name} (
            languageID int(11) NOT NULL AUTO_INCREMENT,
            display varchar(50) NOT NULL,
            folder varchar(50) NOT NULL,
            charset varchar(20) NOT NULL DEFAULT 'UTF-8',
            norels tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (languageID),
            UNIQUE KEY folder (folder),
            KEY display (display)
        ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Insert default English language if table is empty
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
    if ($count == 0) {
      $wpdb->insert(
        $this->table_name,
        array(
          'display' => 'English',
          'folder' => 'english',
          'charset' => 'UTF-8',
          'norels' => 0
        ),
        array('%s', '%s', '%s', '%d')
      );
    }
  }

  /**
   * Add new language
   */
  public function ajax_add_language()
  {
    check_ajax_referer('heritagepress_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('Insufficient permissions', 'heritagepress'));
    }

    $display = sanitize_text_field($_POST['display']);
    $folder = sanitize_text_field($_POST['folder']);
    $charset = sanitize_text_field($_POST['langcharset']);
    $norels = isset($_POST['langnorels']) ? 1 : 0;

    // Validation
    $errors = array();

    if (empty($display)) {
      $errors[] = __('Display name is required', 'heritagepress');
    }

    if (empty($folder)) {
      $errors[] = __('Folder name is required', 'heritagepress');
    }

    if (empty($charset)) {
      $errors[] = __('Character set is required', 'heritagepress');
    }

    // Check for duplicate folder
    global $wpdb;
    $existing = $wpdb->get_var($wpdb->prepare(
      "SELECT languageID FROM {$this->table_name} WHERE folder = %s",
      $folder
    ));

    if ($existing) {
      $errors[] = __('Language folder already exists', 'heritagepress');
    }

    if (!empty($errors)) {
      wp_send_json_error(array('message' => implode('<br>', $errors)));
      return;
    }

    // Insert new language
    $result = $wpdb->insert(
      $this->table_name,
      array(
        'display' => $display,
        'folder' => $folder,
        'charset' => $charset,
        'norels' => $norels
      ),
      array('%s', '%s', '%s', '%d')
    );

    if ($result === false) {
      wp_send_json_error(array('message' => __('Failed to add language', 'heritagepress')));
      return;
    }

    $language_id = $wpdb->insert_id;

    // Log the action
    $this->log_action('add', $language_id, "Added language: {$display}/{$folder}");

    wp_send_json_success(array(
      'message' => sprintf(__('Language %s successfully added', 'heritagepress'), $display),
      'language_id' => $language_id
    ));
  }

  /**
   * Update existing language
   */
  public function ajax_update_language()
  {
    check_ajax_referer('heritagepress_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('Insufficient permissions', 'heritagepress'));
    }

    $language_id = intval($_POST['language_id']);
    $display = sanitize_text_field($_POST['display']);
    $folder = sanitize_text_field($_POST['folder']);
    $charset = sanitize_text_field($_POST['langcharset']);
    $norels = isset($_POST['langnorels']) ? 1 : 0;

    // Validation
    $errors = array();

    if (empty($language_id)) {
      $errors[] = __('Invalid language ID', 'heritagepress');
    }

    if (empty($display)) {
      $errors[] = __('Display name is required', 'heritagepress');
    }

    if (empty($folder)) {
      $errors[] = __('Folder name is required', 'heritagepress');
    }

    if (empty($charset)) {
      $errors[] = __('Character set is required', 'heritagepress');
    }

    // Check for duplicate folder (excluding current record)
    global $wpdb;
    $existing = $wpdb->get_var($wpdb->prepare(
      "SELECT languageID FROM {$this->table_name} WHERE folder = %s AND languageID != %d",
      $folder,
      $language_id
    ));

    if ($existing) {
      $errors[] = __('Language folder already exists', 'heritagepress');
    }

    if (!empty($errors)) {
      wp_send_json_error(array('message' => implode('<br>', $errors)));
      return;
    }

    // Update language
    $result = $wpdb->update(
      $this->table_name,
      array(
        'display' => $display,
        'folder' => $folder,
        'charset' => $charset,
        'norels' => $norels
      ),
      array('languageID' => $language_id),
      array('%s', '%s', '%s', '%d'),
      array('%d')
    );

    if ($result === false) {
      wp_send_json_error(array('message' => __('Failed to update language', 'heritagepress')));
      return;
    }

    // Log the action
    $this->log_action('edit', $language_id, "Modified language: {$display}/{$folder}");

    wp_send_json_success(array(
      'message' => sprintf(__('Language %s successfully updated', 'heritagepress'), $display)
    ));
  }

  /**
   * Delete language
   */
  public function ajax_delete_language()
  {
    check_ajax_referer('heritagepress_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('Insufficient permissions', 'heritagepress'));
    }

    $language_id = intval($_POST['language_id']);

    if (empty($language_id)) {
      wp_send_json_error(array('message' => __('Invalid language ID', 'heritagepress')));
      return;
    }

    global $wpdb;

    // Get language info for logging
    $language = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM {$this->table_name} WHERE languageID = %d",
      $language_id
    ));

    if (!$language) {
      wp_send_json_error(array('message' => __('Language not found', 'heritagepress')));
      return;
    }

    // Don't allow deletion of the last language
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
    if ($count <= 1) {
      wp_send_json_error(array('message' => __('Cannot delete the last language', 'heritagepress')));
      return;
    }

    // Delete language
    $result = $wpdb->delete(
      $this->table_name,
      array('languageID' => $language_id),
      array('%d')
    );

    if ($result === false) {
      wp_send_json_error(array('message' => __('Failed to delete language', 'heritagepress')));
      return;
    }

    // Log the action
    $this->log_action('delete', $language_id, "Deleted language: {$language->display}/{$language->folder}");

    wp_send_json_success(array(
      'message' => sprintf(__('Language %s successfully deleted', 'heritagepress'), $language->display)
    ));
  }

  /**
   * Get single language data
   */
  public function ajax_get_language()
  {
    check_ajax_referer('heritagepress_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('Insufficient permissions', 'heritagepress'));
    }

    $language_id = intval($_POST['language_id']);

    if (empty($language_id)) {
      wp_send_json_error(array('message' => __('Invalid language ID', 'heritagepress')));
      return;
    }

    global $wpdb;
    $language = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM {$this->table_name} WHERE languageID = %d",
      $language_id
    ));

    if (!$language) {
      wp_send_json_error(array('message' => __('Language not found', 'heritagepress')));
      return;
    }

    wp_send_json_success($language);
  }

  /**
   * Search languages with pagination
   */
  public function ajax_search_languages()
  {
    check_ajax_referer('heritagepress_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('Insufficient permissions', 'heritagepress'));
    }

    $search = sanitize_text_field($_POST['search'] ?? '');
    $page = intval($_POST['page'] ?? 1);
    $per_page = 20; // Match standard maxsearchresults
    $offset = ($page - 1) * $per_page;

    global $wpdb;

    // Build search query
    $where_clause = '';
    $search_params = array();

    if (!empty($search)) {
      $where_clause = "WHERE display LIKE %s OR folder LIKE %s";
      $search_term = '%' . $wpdb->esc_like($search) . '%';
      $search_params = array($search_term, $search_term);
    }

    // Get total count
    $count_query = "SELECT COUNT(*) FROM {$this->table_name} {$where_clause}";
    $total_records = $wpdb->get_var($wpdb->prepare($count_query, $search_params));

    // Get languages
    $query = "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY display LIMIT %d OFFSET %d";
    $params = array_merge($search_params, array($per_page, $offset));
    $languages = $wpdb->get_results($wpdb->prepare($query, $params));

    wp_send_json_success(array(
      'languages' => $languages,
      'total' => $total_records,
      'page' => $page,
      'per_page' => $per_page,
      'total_pages' => ceil($total_records / $per_page)
    ));
  }

  /**
   * Get all languages for dropdown
   */
  public function get_all_languages()
  {
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY display");
  }

  /**
   * Get available language folders from filesystem
   */
  public function get_available_language_folders()
  {
    $language_path = HERITAGEPRESS_PLUGIN_DIR . 'languages/';
    $folders = array();

    if (is_dir($language_path)) {
      $handle = opendir($language_path);
      while (($filename = readdir($handle)) !== false) {
        if (
          is_dir($language_path . $filename) &&
          $filename != '.' &&
          $filename != '..' &&
          $filename != '@eaDir'
        ) {
          $folders[] = $filename;
        }
      }
      closedir($handle);
      natcasesort($folders);
    }

    return $folders;
  }

  /**
   * Log admin action
   */
  private function log_action($action, $language_id, $description)
  {
    // WordPress doesn't have a built-in admin log,
    // so we'll create a simple logging mechanism
    $log_entry = array(
      'timestamp' => current_time('mysql'),
      'user_id' => get_current_user_id(),
      'action' => $action,
      'language_id' => $language_id,
      'description' => $description
    );

    // Store in WordPress options or implement a custom log table
    $log_entries = get_option('heritagepress_admin_log', array());
    array_unshift($log_entries, $log_entry);

    // Keep only last 1000 entries
    $log_entries = array_slice($log_entries, 0, 1000);
    update_option('heritagepress_admin_log', $log_entries);
  }
}

// Initialize the controller
$hp_language_controller = new HP_Language_Controller();
