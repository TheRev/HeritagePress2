<?php

/**
 * Media Collection Controller for HeritagePress
 *
 * Handles media collection/media type management functionality
 * including adding, updating, deleting, and retrieving media collections.
 * This class extends the base controller and provides AJAX endpoints
 * for client-side interactions.
 *
 * @package HeritagePress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

require_once plugin_dir_path(__FILE__) . '../../includes/controllers/class-hp-base-controller.php';

/**
 * Media Collection Controller Class
 */
class HP_Media_Collection_Controller extends HP_Base_Controller
{
  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct('media-collection');
    $this->init_hooks();
  }

  /**
   * Initialize hooks
   */
  private function init_hooks()
  {
    // AJAX handlers
    add_action('wp_ajax_hp_add_media_collection', array($this, 'ajax_add_collection'));
    add_action('wp_ajax_hp_update_media_collection', array($this, 'ajax_update_collection'));
    add_action('wp_ajax_hp_delete_media_collection', array($this, 'ajax_delete_collection'));
    add_action('wp_ajax_hp_get_media_collections', array($this, 'ajax_get_collections'));
    add_action('wp_ajax_hp_get_media_collection', array($this, 'ajax_get_collection'));
  }

  /**
   * Display main page
   */
  public function display_page()
  {
    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/media-collections.php';
  }

  /**
   * Handle form submissions
   */
  public function handle_form_submission()
  {
    if (!isset($_POST['action'])) {
      return;
    }

    switch ($_POST['action']) {
      case 'hp_add_collection':
        $this->process_add_collection();
        break;
      case 'hp_update_collection':
        $this->process_update_collection();
        break;
      case 'hp_delete_collection':
        $this->process_delete_collection();
        break;
    }
  }

  /**
   * Process add collection form
   */
  private function process_add_collection()
  {
    // Security checks
    if (!wp_verify_nonce($_POST['hp_collection_nonce'], 'hp_add_collection')) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!current_user_can('manage_options')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    // Get form data
    $collection_id = sanitize_text_field($_POST['collection_id'] ?? '');
    $display = sanitize_text_field($_POST['display'] ?? '');
    $path = sanitize_text_field($_POST['path'] ?? '');
    $liketype = sanitize_text_field($_POST['liketype'] ?? '');
    $icon = sanitize_text_field($_POST['icon'] ?? '');
    $thumb = sanitize_text_field($_POST['thumb'] ?? '');
    $exportas = sanitize_text_field($_POST['exportas'] ?? '');
    $ordernum = intval($_POST['ordernum'] ?? 0);
    $localpath = sanitize_text_field($_POST['localpath'] ?? '');

    // Validate required fields
    if (empty($collection_id) || empty($display)) {
      $this->add_notice(__('Collection ID and Display Name are required.', 'heritagepress'), 'error');
      return;
    }

    // Clean collection ID (clean ID function)
    $collection_id = $this->clean_collection_id($collection_id);

    // Check if collection ID already exists
    global $wpdb;
    $existing = $wpdb->get_var($wpdb->prepare(
      "SELECT mediatypeID FROM {$wpdb->prefix}hp_mediatypes WHERE mediatypeID = %s",
      $collection_id
    ));

    if ($existing) {
      $this->add_notice(__('Collection ID already exists.', 'heritagepress'), 'error');
      return;
    }

    // Standard collections that can't be added manually
    $standard_collections = array('photos', 'histories', 'headstones', 'documents', 'recordings', 'videos');
    if (in_array($collection_id, $standard_collections)) {
      $this->add_notice(__('Standard collection types cannot be added manually.', 'heritagepress'), 'error');
      return;
    }

    // Insert new collection
    $result = $wpdb->insert(
      $wpdb->prefix . 'hp_mediatypes',
      array(
        'mediatypeID' => $collection_id,
        'display' => $display,
        'path' => $path,
        'liketype' => $liketype,
        'icon' => $icon,
        'thumb' => $thumb,
        'exportas' => $exportas,
        'disabled' => 0,
        'ordernum' => $ordernum,
        'localpath' => $localpath
      ),
      array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s')
    );

    if ($result) {
      $this->add_notice(__('Media collection added successfully!', 'heritagepress'), 'success');
    } else {
      $this->add_notice(__('Failed to add media collection.', 'heritagepress'), 'error');
    }
  }

  /**
   * Process update collection form
   */
  private function process_update_collection()
  {
    // Security checks
    if (!wp_verify_nonce($_POST['hp_collection_nonce'], 'hp_update_collection')) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!current_user_can('manage_options')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    // Get form data
    $collection_id = sanitize_text_field($_POST['collection_id'] ?? '');
    $display = sanitize_text_field($_POST['display'] ?? '');
    $path = sanitize_text_field($_POST['path'] ?? '');
    $liketype = sanitize_text_field($_POST['liketype'] ?? '');
    $icon = sanitize_text_field($_POST['icon'] ?? '');
    $thumb = sanitize_text_field($_POST['thumb'] ?? '');
    $exportas = sanitize_text_field($_POST['exportas'] ?? '');
    $ordernum = intval($_POST['ordernum'] ?? 0);
    $localpath = sanitize_text_field($_POST['localpath'] ?? '');
    $disabled = isset($_POST['disabled']) ? 1 : 0;

    // Validate required fields
    if (empty($collection_id) || empty($display)) {
      $this->add_notice(__('Collection ID and Display Name are required.', 'heritagepress'), 'error');
      return;
    }

    // Update collection
    global $wpdb;
    $result = $wpdb->update(
      $wpdb->prefix . 'hp_mediatypes',
      array(
        'display' => $display,
        'path' => $path,
        'liketype' => $liketype,
        'icon' => $icon,
        'thumb' => $thumb,
        'exportas' => $exportas,
        'disabled' => $disabled,
        'ordernum' => $ordernum,
        'localpath' => $localpath
      ),
      array('mediatypeID' => $collection_id),
      array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s'),
      array('%s')
    );

    if ($result !== false) {
      $this->add_notice(__('Media collection updated successfully!', 'heritagepress'), 'success');
    } else {
      $this->add_notice(__('Failed to update media collection.', 'heritagepress'), 'error');
    }
  }

  /**
   * Process delete collection
   */
  private function process_delete_collection()
  {
    // Security checks
    if (!wp_verify_nonce($_POST['hp_collection_nonce'], 'hp_delete_collection')) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!current_user_can('manage_options')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    $collection_id = sanitize_text_field($_POST['collection_id'] ?? '');

    if (empty($collection_id)) {
      $this->add_notice(__('Collection ID is required.', 'heritagepress'), 'error');
      return;
    }

    // Prevent deletion of standard collections
    $standard_collections = array('photos', 'histories', 'headstones', 'documents', 'recordings', 'videos');
    if (in_array($collection_id, $standard_collections)) {
      $this->add_notice(__('Standard collection types cannot be deleted.', 'heritagepress'), 'error');
      return;
    }

    // Check if collection is in use
    global $wpdb;
    $in_use = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$wpdb->prefix}hp_media WHERE mediatypeID = %s",
      $collection_id
    ));

    if ($in_use > 0) {
      $this->add_notice(sprintf(__('Cannot delete collection. %d media items are using this collection type.', 'heritagepress'), $in_use), 'error');
      return;
    }

    // Delete collection
    $result = $wpdb->delete(
      $wpdb->prefix . 'hp_mediatypes',
      array('mediatypeID' => $collection_id),
      array('%s')
    );

    if ($result) {
      $this->add_notice(__('Media collection deleted successfully!', 'heritagepress'), 'success');
    } else {
      $this->add_notice(__('Failed to delete media collection.', 'heritagepress'), 'error');
    }
  }

  /**
   * AJAX handler to add collection
   */
  public function ajax_add_collection()
  {
    if (!wp_verify_nonce($_POST['nonce'], 'hp_add_collection')) {
      wp_send_json_error(__('Security check failed.', 'heritagepress'));
      return;
    }

    if (!current_user_can('manage_options')) {
      wp_send_json_error(__('Insufficient permissions.', 'heritagepress'));
      return;
    }

    // Get data - standard collection addition behavior
    $collection_id = sanitize_text_field($_POST['collid'] ?? '');
    $display = sanitize_text_field($_POST['display'] ?? '');
    $path = sanitize_text_field($_POST['path'] ?? '');
    $liketype = sanitize_text_field($_POST['liketype'] ?? '');
    $icon = sanitize_text_field($_POST['icon'] ?? '');
    $thumb = sanitize_text_field($_POST['thumb'] ?? '');
    $exportas = sanitize_text_field($_POST['exportas'] ?? '');
    $ordernum = intval($_POST['ordernum'] ?? 0);
    $localpath = sanitize_text_field($_POST['localpath'] ?? '');

    // Clean collection ID
    $collection_id = $this->clean_collection_id($collection_id);

    // Standard collections check (standard check)
    $standard_collections = array('photos', 'histories', 'headstones', 'documents', 'recordings', 'videos');
    $new_collection_id = 0;

    if (!in_array($collection_id, $standard_collections)) {
      global $wpdb;

      // Check if already exists
      $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT mediatypeID FROM {$wpdb->prefix}hp_mediatypes WHERE mediatypeID = %s",
        $collection_id
      ));

      if (!$existing) {
        $result = $wpdb->insert(
          $wpdb->prefix . 'hp_mediatypes',
          array(
            'mediatypeID' => $collection_id,
            'display' => $display,
            'path' => $path,
            'liketype' => $liketype,
            'icon' => $icon,
            'thumb' => $thumb,
            'exportas' => $exportas,
            'disabled' => 0,
            'ordernum' => $ordernum,
            'localpath' => $localpath
          ),
          array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s')
        );

        if ($result) {
          $new_collection_id = $collection_id;
        }
      }
    }

    // Return collection ID (standard behavior)
    echo $new_collection_id;
    wp_die();
  }

  /**
   * AJAX handler to get collections
   */
  public function ajax_get_collections()
  {
    if (!wp_verify_nonce($_POST['nonce'], 'hp_get_collections')) {
      wp_send_json_error(__('Security check failed.', 'heritagepress'));
      return;
    }

    if (!current_user_can('manage_options')) {
      wp_send_json_error(__('Insufficient permissions.', 'heritagepress'));
      return;
    }

    global $wpdb;
    $collections = $wpdb->get_results(
      "SELECT * FROM {$wpdb->prefix}hp_mediatypes ORDER BY ordernum ASC, display ASC"
    );

    wp_send_json_success($collections);
  }

  /**
   * AJAX handler to get single collection
   */
  public function ajax_get_collection()
  {
    if (!wp_verify_nonce($_POST['nonce'], 'hp_get_collection')) {
      wp_send_json_error(__('Security check failed.', 'heritagepress'));
      return;
    }

    if (!current_user_can('manage_options')) {
      wp_send_json_error(__('Insufficient permissions.', 'heritagepress'));
      return;
    }

    $collection_id = sanitize_text_field($_POST['collection_id'] ?? '');
    if (empty($collection_id)) {
      wp_send_json_error(__('Collection ID required.', 'heritagepress'));
      return;
    }

    global $wpdb;
    $collection = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM {$wpdb->prefix}hp_mediatypes WHERE mediatypeID = %s",
      $collection_id
    ));

    if ($collection) {
      wp_send_json_success($collection);
    } else {
      wp_send_json_error(__('Collection not found.', 'heritagepress'));
    }
  }

  /**
   * AJAX handler to delete collection
   */
  public function ajax_delete_collection()
  {
    if (!wp_verify_nonce($_POST['nonce'], 'hp_delete_collection')) {
      wp_send_json_error(__('Security check failed.', 'heritagepress'));
      return;
    }

    if (!current_user_can('manage_options')) {
      wp_send_json_error(__('Insufficient permissions.', 'heritagepress'));
      return;
    }

    $collection_id = sanitize_text_field($_POST['collection_id'] ?? '');
    if (empty($collection_id)) {
      wp_send_json_error(__('Collection ID required.', 'heritagepress'));
      return;
    }

    // Prevent deletion of standard collections
    $standard_collections = array('photos', 'histories', 'headstones', 'documents', 'recordings', 'videos');
    if (in_array($collection_id, $standard_collections)) {
      wp_send_json_error(__('Standard collection types cannot be deleted.', 'heritagepress'));
      return;
    }

    // Check if in use
    global $wpdb;
    $in_use = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$wpdb->prefix}hp_media WHERE mediatypeID = %s",
      $collection_id
    ));

    if ($in_use > 0) {
      wp_send_json_error(sprintf(__('Cannot delete collection. %d media items are using this collection type.', 'heritagepress'), $in_use));
      return;
    }

    // Delete
    $result = $wpdb->delete(
      $wpdb->prefix . 'hp_mediatypes',
      array('mediatypeID' => $collection_id),
      array('%s')
    );

    if ($result) {
      wp_send_json_success(array('message' => __('Collection deleted successfully.', 'heritagepress')));
    } else {
      wp_send_json_error(__('Failed to delete collection.', 'heritagepress'));
    }
  }

  /**
   * Clean collection ID (clean ID function)
   */
  private function clean_collection_id($id)
  {
    // Remove special characters and convert to lowercase
    $id = strtolower(trim($id));
    $id = preg_replace('/[^a-z0-9_-]/', '', $id);
    $id = preg_replace('/[-_]+/', '_', $id);
    return $id;
  }
}
