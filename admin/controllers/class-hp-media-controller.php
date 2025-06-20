<?php

/**
 * Media Controller for HeritagePress
 *
 * Handles individual media item management functionality
 * including adding, updating, deleting, and retrieving media collections.
 * This class extends the base controller and provides
 * specific methods for media operations.
 * It integrates with the WordPress media library
 * and supports various media types
 * commonly used in genealogy.
 * It also manages media links to individuals
 * and provides AJAX endpoints
 * for dynamic media operations.
 * This controller is part of the HeritagePress plugin
 * for WordPress,
 * which is designed to enhance genealogy management
 * and display capabilities.
 *
 * @package HeritagePress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

require_once plugin_dir_path(__FILE__) . '../../includes/controllers/class-hp-base-controller.php';

/**
 * Media Controller Class
 */
class HP_Media_Controller extends HP_Base_Controller
{
  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct('media');
    $this->capabilities = array(
      'manage_media' => 'manage_genealogy',
      'edit_media' => 'edit_genealogy',
      'delete_media' => 'delete_genealogy'
    );
    $this->init_hooks();
    // Register new AJAX endpoints
    add_action('wp_ajax_hp_get_media_events_xml', array($this, 'ajax_get_media_events_xml'));
    add_action('wp_ajax_hp_get_media_link_targets', array($this, 'ajax_get_media_link_targets'));
    add_action('wp_ajax_hp_get_media_caption', array($this, 'ajax_get_media_caption'));
  }

  /**
   * Initialize hooks
   */
  private function init_hooks()
  {
    // AJAX handlers
    add_action('wp_ajax_hp_add_media', array($this, 'ajax_add_media'));
    add_action('wp_ajax_hp_update_media', array($this, 'ajax_update_media'));
    add_action('wp_ajax_hp_delete_media', array($this, 'ajax_delete_media'));
    add_action('wp_ajax_hp_get_media', array($this, 'ajax_get_media'));
    add_action('wp_ajax_hp_get_media_list', array($this, 'ajax_get_media_list'));
    add_action('wp_ajax_hp_upload_media_file', array($this, 'ajax_upload_media_file'));
    add_action('wp_ajax_hp_create_thumbnail', array($this, 'ajax_create_thumbnail'));
    add_action('wp_ajax_hp_link_media_to_person', array($this, 'ajax_link_media_to_person'));
    add_action('wp_ajax_hp_get_linked_media_for_person', array($this, 'ajax_get_linked_media_for_person'));
    add_action('wp_ajax_hp_get_photo_details', array($this, 'ajax_get_photo_details'));
  }

  /**
   * Register hooks
   */
  public function register_hooks()
  {
    parent::register_hooks();

    // Initialize WordPress media library integration
    add_action('init', array($this, 'init_media_support'));
  }

  /**
   * Initialize WordPress media support
   */
  public function init_media_support()
  {
    // Add GEDCOM mime types
    add_filter('upload_mimes', array($this, 'add_media_mime_types'));

    // Create upload directories
    $this->ensure_upload_directories();
  }

  /**
   * Add media MIME types
   */
  public function add_media_mime_types($mimes)
  {
    // Allow various media types commonly used in genealogy
    $mimes['pdf'] = 'application/pdf';
    $mimes['doc'] = 'application/msword';
    $mimes['docx'] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    $mimes['mp3'] = 'audio/mpeg';
    $mimes['wav'] = 'audio/wav';
    $mimes['mp4'] = 'video/mp4';
    $mimes['avi'] = 'video/avi';

    return $mimes;
  }

  /**
   * Ensure upload directories exist
   */
  private function ensure_upload_directories()
  {
    $upload_dir = wp_upload_dir();
    $heritage_dir = $upload_dir['basedir'] . '/heritagepress/media/';
    $thumb_dir = $heritage_dir . 'thumbnails/';

    if (!file_exists($heritage_dir)) {
      wp_mkdir_p($heritage_dir);
    }

    if (!file_exists($thumb_dir)) {
      wp_mkdir_p($thumb_dir);
    }

    // Create .htaccess for security
    $htaccess_content = "Options -Indexes\n";
    $htaccess_content .= "Order allow,deny\n";
    $htaccess_content .= "Allow from all\n";

    if (!file_exists($heritage_dir . '.htaccess')) {
      file_put_contents($heritage_dir . '.htaccess', $htaccess_content);
    }
  }

  /**
   * Display main page
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
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'browse';

    // Include the media management view
    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/media-management.php';
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
      case 'hp_add_media':
        $this->handle_add_media();
        break;
      case 'hp_update_media':
        $this->handle_update_media();
        break;
      case 'hp_delete_media':
        $this->handle_delete_media();
        break;
      case 'bulk_action':
        $this->handle_bulk_media_actions();
        break;
    }
  }

  /**
   * Handle adding a new media item
   */
  private function handle_add_media()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!current_user_can('manage_options')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    // Get form data
    $media_data = $this->sanitize_media_data($_POST);

    // Validate required fields
    if (empty($media_data['mediatypeID']) || empty($media_data['gedcom'])) {
      $this->add_notice(__('Media type and tree are required.', 'heritagepress'), 'error');
      return;
    }

    // Handle file upload if provided
    if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
      $upload_result = $this->handle_file_upload($_FILES['media_file']);
      if (is_wp_error($upload_result)) {
        $this->add_notice($upload_result->get_error_message(), 'error');
        return;
      }
      $media_data['path'] = $upload_result['path'];
      $media_data['form'] = strtoupper(pathinfo($upload_result['path'], PATHINFO_EXTENSION));
    }

    // Generate media key
    $media_data['mediakey'] = $this->generate_media_key($media_data);

    // Set metadata
    $media_data['changedate'] = current_time('mysql');
    $media_data['changedby'] = wp_get_current_user()->user_login;

    // Insert into database
    global $wpdb;
    $result = $wpdb->insert(
      $wpdb->prefix . 'hp_media',
      $media_data,
      $this->get_media_format_array()
    );

    if ($result) {
      $media_id = $wpdb->insert_id;

      // Handle thumbnail creation
      if (!empty($media_data['path']) && $this->is_image($media_data['path'])) {
        $this->create_thumbnail($media_id, $media_data['path']);
      }

      // Handle person linking if provided
      if (!empty($_POST['link_personID'])) {
        $this->link_media_to_person($media_id, $_POST['link_personID'], $_POST['link_tree'], $_POST['link_linktype']);
      }

      $this->add_notice(__('Media item added successfully!', 'heritagepress'), 'success');

      // Redirect to edit page for new media
      wp_redirect(admin_url('admin.php?page=heritagepress-media&tab=edit&media_id=' . $media_id . '&newmedia=1&added=1'));
      exit;
    } else {
      $this->add_notice(__('Failed to add media item.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle updating media item
   */
  private function handle_update_media()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    $media_id = intval($_POST['media_id']);
    if (!$media_id) {
      $this->add_notice(__('Invalid media ID.', 'heritagepress'), 'error');
      return;
    }

    // Get form data
    $media_data = $this->sanitize_media_data($_POST);
    $media_data['changedate'] = current_time('mysql');
    $media_data['changedby'] = wp_get_current_user()->user_login;

    // Update database
    global $wpdb;
    $result = $wpdb->update(
      $wpdb->prefix . 'hp_media',
      $media_data,
      array('mediaID' => $media_id),
      $this->get_media_format_array(),
      array('%d')
    );

    if ($result !== false) {
      $this->add_notice(__('Media item updated successfully!', 'heritagepress'), 'success');
    } else {
      $this->add_notice(__('Failed to update media item.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle deleting media item
   */
  private function handle_delete_media()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    $media_id = intval($_POST['media_id']);
    if (!$media_id) {
      $this->add_notice(__('Invalid media ID.', 'heritagepress'), 'error');
      return;
    }

    global $wpdb;

    // Get media info before deletion
    $media = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM {$wpdb->prefix}hp_media WHERE mediaID = %d",
      $media_id
    ));

    if (!$media) {
      $this->add_notice(__('Media item not found.', 'heritagepress'), 'error');
      return;
    }

    // Delete media links first
    $wpdb->delete(
      $wpdb->prefix . 'hp_medialinks',
      array('mediaID' => $media_id),
      array('%d')
    );

    // Delete image tags
    $wpdb->delete(
      $wpdb->prefix . 'hp_image_tags',
      array('mediaID' => $media_id),
      array('%d')
    );

    // Delete the media record
    $result = $wpdb->delete(
      $wpdb->prefix . 'hp_media',
      array('mediaID' => $media_id),
      array('%d')
    );

    if ($result) {
      // Delete physical files if they exist
      $this->delete_media_files($media);

      $this->add_notice(__('Media item deleted successfully!', 'heritagepress'), 'success');
    } else {
      $this->add_notice(__('Failed to delete media item.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle bulk actions
   */
  private function handle_bulk_media_actions()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    $action = sanitize_text_field($_POST['bulk_action']);
    $media_ids = array_map('intval', $_POST['media_ids'] ?? array());

    if (empty($media_ids)) {
      $this->add_notice(__('No media items selected.', 'heritagepress'), 'error');
      return;
    }

    switch ($action) {
      case 'delete':
        $this->bulk_delete_media($media_ids);
        break;
      case 'enable':
        $this->bulk_update_media_status($media_ids, 0);
        break;
      case 'disable':
        $this->bulk_update_media_status($media_ids, 1);
        break;
    }
  }

  /**
   * Bulk delete media items
   */
  private function bulk_delete_media($media_ids)
  {
    global $wpdb;
    $deleted_count = 0;

    foreach ($media_ids as $media_id) {
      // Get media info
      $media = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}hp_media WHERE mediaID = %d",
        $media_id
      ));

      if ($media) {
        // Delete related records
        $wpdb->delete($wpdb->prefix . 'hp_medialinks', array('mediaID' => $media_id), array('%d'));
        $wpdb->delete($wpdb->prefix . 'hp_image_tags', array('mediaID' => $media_id), array('%d'));

        // Delete media record
        if ($wpdb->delete($wpdb->prefix . 'hp_media', array('mediaID' => $media_id), array('%d'))) {
          $this->delete_media_files($media);
          $deleted_count++;
        }
      }
    }

    $this->add_notice(sprintf(__('%d media items deleted successfully.', 'heritagepress'), $deleted_count), 'success');
  }

  /**
   * Bulk update media status
   */
  private function bulk_update_media_status($media_ids, $private_status)
  {
    global $wpdb;
    $updated_count = 0;

    foreach ($media_ids as $media_id) {
      if ($wpdb->update(
        $wpdb->prefix . 'hp_media',
        array('private' => $private_status),
        array('mediaID' => $media_id),
        array('%d'),
        array('%d')
      )) {
        $updated_count++;
      }
    }

    $status_text = $private_status ? 'disabled' : 'enabled';
    $this->add_notice(sprintf(__('%d media items %s successfully.', 'heritagepress'), $updated_count, $status_text), 'success');
  }

  /**
   * Sanitize media data from form
   */
  private function sanitize_media_data($data)
  {
    return array(
      'mediatypeID' => sanitize_text_field($data['mediatypeID'] ?? ''),
      'gedcom' => sanitize_text_field($data['gedcom'] ?? ''),
      'description' => sanitize_textarea_field($data['description'] ?? ''),
      'notes' => sanitize_textarea_field($data['notes'] ?? ''),
      'datetaken' => sanitize_text_field($data['datetaken'] ?? ''),
      'placetaken' => sanitize_textarea_field($data['placetaken'] ?? ''),
      'owner' => sanitize_text_field($data['owner'] ?? ''),
      'status' => sanitize_text_field($data['status'] ?? ''),
      'width' => intval($data['width'] ?? 0),
      'height' => intval($data['height'] ?? 0),
      'latitude' => sanitize_text_field($data['latitude'] ?? ''),
      'longitude' => sanitize_text_field($data['longitude'] ?? ''),
      'zoom' => intval($data['zoom'] ?? 0),
      'showmap' => isset($data['showmap']) ? 1 : 0,
      'alwayson' => isset($data['alwayson']) ? 1 : 0,
      'newwindow' => isset($data['newwindow']) ? 1 : 0,
      'private' => isset($data['private']) ? 1 : 0,
      'usenl' => isset($data['usenl']) ? 1 : 0,
      'cemeteryID' => intval($data['cemeteryID'] ?? 0),
      'plot' => sanitize_text_field($data['plot'] ?? ''),
      'linktocem' => isset($data['linktocem']) ? 1 : 0,
      'bodytext' => wp_kses_post($data['bodytext'] ?? ''),
      'left_value' => intval($data['left_value'] ?? 0),
      'top_value' => intval($data['top_value'] ?? 0)
    );
  }

  /**
   * Get format array for database operations
   */
  private function get_media_format_array()
  {
    return array(
      '%s',
      '%s',
      '%s',
      '%s',
      '%s',
      '%s',
      '%s',
      '%s',
      '%d',
      '%d',
      '%s',
      '%s',
      '%d',
      '%d',
      '%d',
      '%d',
      '%d',
      '%d',
      '%d',
      '%s',
      '%d',
      '%s',
      '%d',
      '%d',
      '%s',
      '%s'
    );
  }

  /**
   * Generate media key
   */
  private function generate_media_key($media_data)
  {
    if (!empty($media_data['path'])) {
      return $media_data['mediatypeID'] . '/' . $media_data['path'];
    }

    return $media_data['mediatypeID'] . '/' . time();
  }

  /**
   * Handle file upload
   */
  private function handle_file_upload($file)
  {
    if (!function_exists('wp_handle_upload')) {
      require_once(ABSPATH . 'wp-admin/includes/file.php');
    }

    $upload_overrides = array(
      'test_form' => false,
      'upload_error_handler' => array($this, 'upload_error_handler')
    );

    $uploaded_file = wp_handle_upload($file, $upload_overrides);

    if (isset($uploaded_file['error'])) {
      return new WP_Error('upload_error', $uploaded_file['error']);
    }

    // Move to HeritagePress media directory
    $upload_dir = wp_upload_dir();
    $heritage_dir = $upload_dir['basedir'] . '/heritagepress/media/';
    $filename = sanitize_file_name(basename($uploaded_file['file']));
    $new_path = $heritage_dir . $filename;

    if (rename($uploaded_file['file'], $new_path)) {
      return array(
        'path' => 'heritagepress/media/' . $filename,
        'full_path' => $new_path,
        'url' => $upload_dir['baseurl'] . '/heritagepress/media/' . $filename
      );
    }

    return new WP_Error('move_error', __('Failed to move uploaded file.', 'heritagepress'));
  }

  /**
   * Upload error handler
   */
  public function upload_error_handler($file, $message)
  {
    return array('error' => $message);
  }

  /**
   * Check if file is an image
   */
  private function is_image($path)
  {
    $image_extensions = array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp');
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return in_array($extension, $image_extensions);
  }

  /**
   * Create thumbnail for image
   */
  public function create_thumbnail($media_id, $path)
  {
    if (!function_exists('wp_get_image_editor')) {
      require_once(ABSPATH . 'wp-admin/includes/image.php');
    }

    $upload_dir = wp_upload_dir();
    $full_path = $upload_dir['basedir'] . '/' . $path;

    if (!file_exists($full_path)) {
      return false;
    }

    $editor = wp_get_image_editor($full_path);

    if (is_wp_error($editor)) {
      return false;
    }

    // Resize to thumbnail size (150x150 max)
    $editor->resize(150, 150, false);

    $thumb_dir = $upload_dir['basedir'] . '/heritagepress/media/thumbnails/';
    $thumb_filename = 'thumb_' . $media_id . '_' . basename($path);
    $thumb_path = $thumb_dir . $thumb_filename;

    $saved = $editor->save($thumb_path);

    if (!is_wp_error($saved)) {
      // Update media record with thumbnail path
      global $wpdb;
      $wpdb->update(
        $wpdb->prefix . 'hp_media',
        array('thumbpath' => 'heritagepress/media/thumbnails/' . $thumb_filename),
        array('mediaID' => $media_id),
        array('%s'),
        array('%d')
      );

      return true;
    }

    return false;
  }

  /**
   * Link media to person
   */
  private function link_media_to_person($media_id, $person_id, $tree, $linktype)
  {
    global $wpdb;

    // Get next order number
    $order_num = $wpdb->get_var($wpdb->prepare(
      "SELECT COALESCE(MAX(ordernum), 0) + 1 FROM {$wpdb->prefix}hp_medialinks WHERE personID = %s AND gedcom = %s",
      $person_id,
      $tree
    ));

    // Insert media link
    $wpdb->insert(
      $wpdb->prefix . 'hp_medialinks',
      array(
        'personID' => $person_id,
        'mediaID' => $media_id,
        'ordernum' => $order_num,
        'gedcom' => $tree,
        'linktype' => $linktype,
        'eventID' => '',
        'defphoto' => ''
      ),
      array('%s', '%d', '%f', '%s', '%s', '%s', '%s')
    );
  }

  /**
   * Delete media files from filesystem
   */
  private function delete_media_files($media)
  {
    $upload_dir = wp_upload_dir();

    // Delete main file
    if (!empty($media->path)) {
      $full_path = $upload_dir['basedir'] . '/' . $media->path;
      if (file_exists($full_path)) {
        unlink($full_path);
      }
    }

    // Delete thumbnail
    if (!empty($media->thumbpath)) {
      $thumb_path = $upload_dir['basedir'] . '/' . $media->thumbpath;
      if (file_exists($thumb_path)) {
        unlink($thumb_path);
      }
    }
  }

  /**
   * AJAX handler to get media list
   */
  public function ajax_get_media_list()
  {
    if (!wp_verify_nonce($_POST['nonce'], 'hp_get_media_list')) {
      wp_send_json_error(__('Security check failed.', 'heritagepress'));
      return;
    }

    if (!current_user_can('manage_options')) {
      wp_send_json_error(__('Insufficient permissions.', 'heritagepress'));
      return;
    }

    $page = intval($_POST['page'] ?? 1);
    $per_page = intval($_POST['per_page'] ?? 20);
    $search = sanitize_text_field($_POST['search'] ?? '');
    $media_type = sanitize_text_field($_POST['media_type'] ?? '');
    $tree = sanitize_text_field($_POST['tree'] ?? '');

    global $wpdb;

    $where_conditions = array();
    $where_values = array();

    if ($search) {
      $where_conditions[] = "(description LIKE %s OR notes LIKE %s OR owner LIKE %s)";
      $search_term = '%' . $search . '%';
      $where_values[] = $search_term;
      $where_values[] = $search_term;
      $where_values[] = $search_term;
    }

    if ($media_type) {
      $where_conditions[] = "mediatypeID = %s";
      $where_values[] = $media_type;
    }

    if ($tree) {
      $where_conditions[] = "gedcom = %s";
      $where_values[] = $tree;
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    // Get total count
    $count_sql = "SELECT COUNT(*) FROM {$wpdb->prefix}hp_media $where_clause";
    $total = $wpdb->get_var($wpdb->prepare($count_sql, $where_values));

    // Get paginated results
    $offset = ($page - 1) * $per_page;
    $sql = "SELECT m.*, mt.display as media_type_name
            FROM {$wpdb->prefix}hp_media m
            LEFT JOIN {$wpdb->prefix}hp_mediatypes mt ON m.mediatypeID = mt.mediatypeID
            $where_clause
            ORDER BY m.changedate DESC
            LIMIT %d, %d";

    $query_values = array_merge($where_values, array($offset, $per_page));
    $media_items = $wpdb->get_results($wpdb->prepare($sql, $query_values));

    wp_send_json_success(array(
      'items' => $media_items,
      'total' => $total,
      'page' => $page,
      'per_page' => $per_page,
      'total_pages' => ceil($total / $per_page)
    ));
  }

  /**
   * AJAX handler to get single media item
   */
  public function ajax_get_media()
  {
    if (!wp_verify_nonce($_POST['nonce'], 'hp_get_media')) {
      wp_send_json_error(__('Security check failed.', 'heritagepress'));
      return;
    }

    $media_id = intval($_POST['media_id']);
    if (!$media_id) {
      wp_send_json_error(__('Invalid media ID.', 'heritagepress'));
      return;
    }

    global $wpdb;
    $media = $wpdb->get_row($wpdb->prepare(
      "SELECT m.*, mt.display as media_type_name
       FROM {$wpdb->prefix}hp_media m
       LEFT JOIN {$wpdb->prefix}hp_mediatypes mt ON m.mediatypeID = mt.mediatypeID
       WHERE m.mediaID = %d",
      $media_id
    ));

    if ($media) {
      wp_send_json_success($media);
    } else {
      wp_send_json_error(__('Media item not found.', 'heritagepress'));
    }
  }

  /**
   * AJAX handler for file upload
   */
  public function ajax_upload_media_file()
  {
    if (!wp_verify_nonce($_POST['nonce'], 'hp_upload_media_file')) {
      wp_send_json_error(__('Security check failed.', 'heritagepress'));
      return;
    }

    if (!current_user_can('upload_files')) {
      wp_send_json_error(__('Insufficient permissions.', 'heritagepress'));
      return;
    }

    if (empty($_FILES['file'])) {
      wp_send_json_error(__('No file uploaded.', 'heritagepress'));
      return;
    }

    $upload_result = $this->handle_file_upload($_FILES['file']);

    if (is_wp_error($upload_result)) {
      wp_send_json_error($upload_result->get_error_message());
      return;
    }

    wp_send_json_success($upload_result);
  }

  /**
   * AJAX handler for thumbnail creation
   */
  public function ajax_create_thumbnail()
  {
    if (!wp_verify_nonce($_POST['nonce'], 'hp_create_thumbnail')) {
      wp_send_json_error(__('Security check failed.', 'heritagepress'));
      return;
    }

    $media_id = intval($_POST['media_id']);
    $path = sanitize_text_field($_POST['path']);

    if (!$media_id || !$path) {
      wp_send_json_error(__('Invalid parameters.', 'heritagepress'));
      return;
    }

    if ($this->create_thumbnail($media_id, $path)) {
      wp_send_json_success(__('Thumbnail created successfully.', 'heritagepress'));
    } else {
      wp_send_json_error(__('Failed to create thumbnail.', 'heritagepress'));
    }
  }

  /**
   * AJAX handler to link media to a person
   */
  public function ajax_link_media_to_person()
  {
    check_ajax_referer('hp_link_media_to_person');
    if (!current_user_can('edit_genealogy')) {
      wp_send_json_error(__('Insufficient permissions.', 'heritagepress'));
    }
    $media_id = intval($_POST['media_id'] ?? 0);
    $person_id = sanitize_text_field($_POST['person_id'] ?? '');
    $tree = sanitize_text_field($_POST['tree'] ?? '');
    $linktype = sanitize_text_field($_POST['linktype'] ?? 'I'); // 'I' for individual
    if (!$media_id || !$person_id || !$tree) {
      wp_send_json_error(__('Missing required parameters.', 'heritagepress'));
    }
    $this->link_media_to_person($media_id, $person_id, $tree, $linktype);
    wp_send_json_success();
  }

  /**
   * AJAX handler to get linked media for a person
   */
  public function ajax_get_linked_media_for_person()
  {
    check_ajax_referer('hp_get_linked_media_for_person');
    if (!current_user_can('edit_genealogy')) {
      wp_send_json_error(__('Insufficient permissions.', 'heritagepress'));
    }
    $person_id = sanitize_text_field($_POST['person_id'] ?? '');
    $tree = sanitize_text_field($_POST['tree'] ?? '');
    if (!$person_id || !$tree) {
      wp_send_json_error(__('Missing required parameters.', 'heritagepress'));
    }
    global $wpdb;
    $results = $wpdb->get_results($wpdb->prepare(
      "SELECT m.*, ml.medialinkID, ml.ordernum FROM {$wpdb->prefix}hp_media m
        INNER JOIN {$wpdb->prefix}hp_medialinks ml ON m.mediaID = ml.mediaID
        WHERE ml.personID = %s AND ml.gedcom = %s ORDER BY ml.ordernum",
      $person_id,
      $tree
    ));
    wp_send_json_success(['items' => $results]);
  }

  /**
   * AJAX: Get photo details (for modal/popup)
   */
  public function ajax_get_photo_details()
  {
    if (!current_user_can('manage_options')) {
      wp_send_json_error(__('Insufficient permissions.', 'heritagepress'));
    }
    $media_id = intval($_POST['media_id'] ?? 0);
    if (!$media_id) {
      wp_send_json_error(__('Invalid media ID.', 'heritagepress'));
    }
    global $wpdb;
    $media = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hp_media WHERE mediaID = %d", $media_id));
    if (!$media) {
      wp_send_json_error(__('Media not found.', 'heritagepress'));
    }
    $thumb_url = '';
    if (!empty($media->thumbpath) && file_exists(wp_upload_dir()['basedir'] . '/' . $media->thumbpath)) {
      $thumb_url = wp_upload_dir()['baseurl'] . '/' . $media->thumbpath;
    }
    $description = esc_html($media->description);
    $notes = esc_html($media->notes);
    $html = '<table width="95%" cellpadding="5" cellspacing="1" style="padding-top:6px">';
    $html .= '<tr>';
    $html .= '<td class="lightback" style="width:56px;height:56px;text-align:center;">';
    if ($thumb_url) {
      $html .= '<img src="' . esc_attr($thumb_url) . '" style="max-width:50px;max-height:50px;" alt="' . $description . '" />';
    } else {
      $html .= '&nbsp;';
    }
    $html .= '</td>';
    $html .= '<td class="lightback normal">';
    $html .= '<u>' . $description . '</u><br />' . esc_html(mb_strimwidth($notes, 0, 90, '…'));
    $html .= '&nbsp;</td>';
    $html .= '</tr></table>';
    wp_send_json_success(['html' => $html]);
  }

  /**
   * AJAX handler to get events for a person/family in XML (for media linking)
   */
  public function ajax_get_media_events_xml()
  {
    // Security: check nonce and capability
    if (!current_user_can('edit_genealogy')) {
      wp_die(__('You do not have permission to access this resource.', 'heritagepress'));
    }
    // Accept params
    $persfamID = isset($_POST['persfamID']) ? sanitize_text_field($_POST['persfamID']) : '';
    $tree = isset($_POST['tree']) ? sanitize_text_field($_POST['tree']) : '';
    $linktype = isset($_POST['linktype']) ? sanitize_text_field($_POST['linktype']) : '';
    $count = isset($_POST['count']) ? intval($_POST['count']) : 0;

    header('Content-Type: application/xml; charset=' . get_option('blog_charset'));
    echo '<?xml version="1.0" encoding="' . get_option('blog_charset') . '"?>' . "\n";
    echo "<eventlist>\n";
    echo "<targetlist>\n";
    echo "<target>{$count}</target>\n";
    echo "</targetlist>\n";

    // Standard events
    if ($linktype === 'I') {
      $standard = array('NAME', 'BIRT', 'CHR', 'DEAT', 'BURI');
      foreach ($standard as $eventtype) {
        echo "<event>\n<eventID>{$eventtype}</eventID>\n<display>" . esc_html($this->get_event_label($eventtype)) . "</display>\n<info>-1</info>\n</event>\n";
      }
      // LDS events (optional, add logic if needed)
    } elseif ($linktype === 'F') {
      $standard = array('MARR', 'DIV');
      foreach ($standard as $eventtype) {
        echo "<event>\n<eventID>{$eventtype}</eventID>\n<display>" . esc_html($this->get_event_label($eventtype)) . "</display>\n<info>-1</info>\n</event>\n";
      }
    }

    // Custom events (example, adjust table/logic as needed)
    global $wpdb;
    $events = $wpdb->get_results($wpdb->prepare(
      "SELECT eventID, display, eventdate, eventplace, info FROM {$wpdb->prefix}hp_events WHERE persfamID = %s AND gedcom = %s ORDER BY eventdate ASC, eventID ASC",
      $persfamID,
      $tree
    ));
    foreach ($events as $event) {
      $displayval = esc_html($event->display);
      $info = '-1';
      if (!empty($event->eventdate)) {
        $info = esc_html($event->eventdate);
      } elseif (!empty($event->eventplace)) {
        $info = esc_html($event->eventplace);
      } elseif (!empty($event->info)) {
        $info = esc_html(mb_substr($event->info, 0, 20) . '...');
      }
      echo "<event>\n<eventID>{$event->eventID}</eventID>\n<display>{$displayval}</display>\n<info>{$info}</info>\n</event>\n";
    }
    echo "</eventlist>";
    wp_die();
  }

  /**
   * Helper to get event label (replace with actual localization if needed)
   */
  private function get_event_label($eventtype)
  {
    $labels = array(
      'NAME' => __('Name', 'heritagepress'),
      'BIRT' => __('Birth', 'heritagepress'),
      'CHR'  => __('Christening', 'heritagepress'),
      'DEAT' => __('Death', 'heritagepress'),
      'BURI' => __('Burial', 'heritagepress'),
      'MARR' => __('Marriage', 'heritagepress'),
      'DIV'  => __('Divorce', 'heritagepress'),
      // Add more as needed
    );
    return isset($labels[$eventtype]) ? $labels[$eventtype] : $eventtype;
  }

  /**
   * AJAX handler to get linkable entities for media (HTML table, for media linking UI)
   */
  public function ajax_get_media_link_targets()
  {
    if (!current_user_can('edit_genealogy')) {
      wp_die(__('You do not have permission to access this resource.', 'heritagepress'));
    }
    $linktype = isset($_POST['linktype']) ? sanitize_text_field($_POST['linktype']) : 'I';
    $tree = isset($_POST['tree']) ? sanitize_text_field($_POST['tree']) : '';
    $search = array_map('sanitize_text_field', $_POST);
    global $wpdb;
    $maxresults = 50;
    $lines = '';
    switch ($linktype) {
      case 'I': // People
        $where = $tree ? $wpdb->prepare('WHERE gedcom = %s', $tree) : 'WHERE 1=1';
        if (!empty($search['firstname'])) {
          $where .= $wpdb->prepare(' AND firstname LIKE %s', '%' . $search['firstname'] . '%');
        }
        if (!empty($search['lastname'])) {
          $where .= $wpdb->prepare(' AND lastname LIKE %s', '%' . $search['lastname'] . '%');
        }
        $results = $wpdb->get_results("SELECT personID, firstname, lastname, birthdate, deathdate FROM {$wpdb->prefix}hp_people $where ORDER BY lastname, firstname LIMIT $maxresults");
        $lines .= '<tr><th>' . esc_html__('Add', 'heritagepress') . '</th><th>' . esc_html__('Person ID', 'heritagepress') . '</th><th>' . esc_html__('Name', 'heritagepress') . '</th><th>' . esc_html__('Birth', 'heritagepress') . '</th><th>' . esc_html__('Death', 'heritagepress') . '</th></tr>';
        foreach ($results as $row) {
          $lines .= '<tr>';
          $lines .= '<td><a href="#" class="hp-add-link" data-entity="' . esc_attr($row->personID) . '">' . esc_html__('Add', 'heritagepress') . '</a></td>';
          $lines .= '<td>' . esc_html($row->personID) . '</td>';
          $lines .= '<td>' . esc_html(trim($row->firstname . ' ' . $row->lastname)) . '</td>';
          $lines .= '<td>' . esc_html($row->birthdate) . '</td>';
          $lines .= '<td>' . esc_html($row->deathdate) . '</td>';
          $lines .= '</tr>';
        }
        break;
      case 'F': // Families
        $where = $tree ? $wpdb->prepare('WHERE gedcom = %s', $tree) : 'WHERE 1=1';
        $results = $wpdb->get_results("SELECT familyID, husband, wife FROM {$wpdb->prefix}hp_families $where ORDER BY familyID LIMIT $maxresults");
        $lines .= '<tr><th>' . esc_html__('Add', 'heritagepress') . '</th><th>' . esc_html__('Family ID', 'heritagepress') . '</th><th>' . esc_html__('Husband', 'heritagepress') . '</th><th>' . esc_html__('Wife', 'heritagepress') . '</th></tr>';
        foreach ($results as $row) {
          $lines .= '<tr>';
          $lines .= '<td><a href="#" class="hp-add-link" data-entity="' . esc_attr($row->familyID) . '">' . esc_html__('Add', 'heritagepress') . '</a></td>';
          $lines .= '<td>' . esc_html($row->familyID) . '</td>';
          $lines .= '<td>' . esc_html($row->husband) . '</td>';
          $lines .= '<td>' . esc_html($row->wife) . '</td>';
          $lines .= '</tr>';
        }
        break;
      case 'S': // Sources
        $where = $tree ? $wpdb->prepare('WHERE gedcom = %s', $tree) : 'WHERE 1=1';
        if (!empty($search['title'])) {
          $where .= $wpdb->prepare(' AND title LIKE %s', '%' . $search['title'] . '%');
        }
        $results = $wpdb->get_results("SELECT sourceID, title FROM {$wpdb->prefix}hp_sources $where ORDER BY title LIMIT $maxresults");
        $lines .= '<tr><th>' . esc_html__('Add', 'heritagepress') . '</th><th>' . esc_html__('Source ID', 'heritagepress') . '</th><th>' . esc_html__('Title', 'heritagepress') . '</th></tr>';
        foreach ($results as $row) {
          $lines .= '<tr>';
          $lines .= '<td><a href="#" class="hp-add-link" data-entity="' . esc_attr($row->sourceID) . '">' . esc_html__('Add', 'heritagepress') . '</a></td>';
          $lines .= '<td>' . esc_html($row->sourceID) . '</td>';
          $lines .= '<td>' . esc_html($row->title) . '</td>';
          $lines .= '</tr>';
        }
        break;
      case 'R': // Repositories
        $where = $tree ? $wpdb->prepare('WHERE gedcom = %s', $tree) : 'WHERE 1=1';
        if (!empty($search['title'])) {
          $where .= $wpdb->prepare(' AND reponame LIKE %s', '%' . $search['title'] . '%');
        }
        $results = $wpdb->get_results("SELECT repoID, reponame FROM {$wpdb->prefix}hp_repositories $where ORDER BY reponame LIMIT $maxresults");
        $lines .= '<tr><th>' . esc_html__('Add', 'heritagepress') . '</th><th>' . esc_html__('Repo ID', 'heritagepress') . '</th><th>' . esc_html__('Name', 'heritagepress') . '</th></tr>';
        foreach ($results as $row) {
          $lines .= '<tr>';
          $lines .= '<td><a href="#" class="hp-add-link" data-entity="' . esc_attr($row->repoID) . '">' . esc_html__('Add', 'heritagepress') . '</a></td>';
          $lines .= '<td>' . esc_html($row->repoID) . '</td>';
          $lines .= '<td>' . esc_html($row->reponame) . '</td>';
          $lines .= '</tr>';
        }
        break;
      case 'L': // Places
        $where = $tree ? $wpdb->prepare('WHERE gedcom = %s', $tree) : 'WHERE 1=1';
        if (!empty($search['place'])) {
          $where .= $wpdb->prepare(' AND place LIKE %s', '%' . $search['place'] . '%');
        }
        $results = $wpdb->get_results("SELECT ID, place FROM {$wpdb->prefix}hp_places $where ORDER BY place LIMIT $maxresults");
        $lines .= '<tr><th>' . esc_html__('Add', 'heritagepress') . '</th><th>' . esc_html__('Place', 'heritagepress') . '</th></tr>';
        foreach ($results as $row) {
          $lines .= '<tr>';
          $lines .= '<td><a href="#" class="hp-add-link" data-entity="' . esc_attr($row->ID) . '">' . esc_html__('Add', 'heritagepress') . '</a></td>';
          $lines .= '<td>' . esc_html($row->place) . '</td>';
          $lines .= '</tr>';
        }
        break;
    }
    header('Content-Type: text/html; charset=' . get_option('blog_charset'));
    echo '<table class="wp-list-table widefat fixed striped">' . $lines . '</table>';
    wp_die();
  }

  /**
   * AJAX: Get media caption (for overlays/popups)
   * Accepts media_id or medialink_id, returns formatted HTML caption
   */
  public function ajax_get_media_caption()
  {
    if (!current_user_can('edit_genealogy')) {
      wp_send_json_error(__('Insufficient permissions.', 'heritagepress'));
    }
    global $wpdb;
    $media_id = isset($_POST['media_id']) ? intval($_POST['media_id']) : 0;
    $medialink_id = isset($_POST['medialink_id']) ? intval($_POST['medialink_id']) : 0;
    $caption = '';
    if ($medialink_id) {
      $row = $wpdb->get_row($wpdb->prepare(
        "SELECT m.description, m.notes, ml.altdescription, ml.altnotes FROM {$wpdb->prefix}hp_media m INNER JOIN {$wpdb->prefix}hp_medialinks ml ON m.mediaID = ml.mediaID WHERE ml.medialinkID = %d",
        $medialink_id
      ), ARRAY_A);
      $title = !empty($row['altdescription']) ? $row['altdescription'] : $row['description'];
      $desc = !empty($row['altnotes']) ? $row['altnotes'] : $row['notes'];
    } elseif ($media_id) {
      $row = $wpdb->get_row($wpdb->prepare(
        "SELECT description, notes FROM {$wpdb->prefix}hp_media WHERE mediaID = %d",
        $media_id
      ), ARRAY_A);
      $title = $row ? $row['description'] : '';
      $desc = $row ? $row['notes'] : '';
    } else {
      wp_send_json_error(__('No media_id or medialink_id provided.', 'heritagepress'));
    }
    $title = $title ? '<strong>' . esc_html($title) . '</strong>' : '';
    $desc = $desc ? esc_html(mb_strimwidth($desc, 0, 200, '…')) : '';
    $caption = $title && $desc ? $title . '<br/>' . $desc : $title . $desc;
    wp_send_json_success(['caption' => $caption]);
  }
}
