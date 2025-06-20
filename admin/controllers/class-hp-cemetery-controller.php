<?php

/**
 * Cemetery Management Controller
 *
 * Handles cemetery CRUD operations, map file uploads, geocoding,
 * place linking, and search functionality.
 *
 * @package    HeritagePress
 * @subpackage Admin/Controllers
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Cemetery_Controller
{
  /**
   * Initialize the cemetery controller
   */
  public function __construct()
  {
    add_action('admin_init', array($this, 'handle_cemetery_actions'));
    add_action('wp_ajax_hp_search_cemeteries', array($this, 'ajax_search_cemeteries'));
    add_action('wp_ajax_hp_delete_cemetery', array($this, 'ajax_delete_cemetery'));
    add_action('wp_ajax_hp_get_cemetery_details', array($this, 'ajax_get_cemetery_details'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
  }

  /**
   * Enqueue scripts and styles
   */
  public function enqueue_scripts($hook)
  {
    if (!is_string($hook) || strpos($hook, 'hp-cemeteries') === false) {
      return;
    }

    wp_enqueue_script(
      'hp-cemetery-admin',
      plugins_url('js/cemetery-admin.js', __FILE__),
      array('jquery', 'wp-util'),
      '1.0.0',
      true
    );

    wp_localize_script('hp-cemetery-admin', 'hp_cemetery_ajax', array(
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('hp_cemetery_nonce'),
      'confirm_delete' => __('Are you sure you want to delete this cemetery?', 'heritagepress'),
      'confirm_delete_selected' => __('Are you sure you want to delete the selected cemeteries?', 'heritagepress')
    ));
  }

  /**
   * Handle cemetery form actions
   */
  public function handle_cemetery_actions()
  {
    if (!isset($_POST['hp_cemetery_action']) || !wp_verify_nonce($_POST['hp_cemetery_nonce'], 'hp_cemetery_action')) {
      return;
    }

    $action = sanitize_text_field($_POST['hp_cemetery_action']);

    switch ($action) {
      case 'add':
        $this->add_cemetery();
        break;
      case 'update':
        $this->update_cemetery();
        break;
      case 'delete':
        $this->delete_cemetery();
        break;
      case 'delete_selected':
        $this->delete_selected_cemeteries();
        break;
    }
  }

  /**
   * Add new cemetery
   */
  private function add_cemetery()
  {
    global $wpdb;

    try {
      $cemname = sanitize_text_field($_POST['cemname']);
      $city = sanitize_text_field($_POST['city'] ?? '');
      $county = sanitize_text_field($_POST['county'] ?? '');
      $state = sanitize_text_field($_POST['state'] ?? '');
      $country = sanitize_text_field($_POST['country'] ?? '');
      $latitude = sanitize_text_field($_POST['latitude'] ?? '');
      $longitude = sanitize_text_field($_POST['longitude'] ?? '');
      $zoom = intval($_POST['zoom'] ?? 0);
      $notes = wp_kses_post($_POST['notes'] ?? '');
      $place = sanitize_text_field($_POST['place'] ?? '');
      $use_coords = isset($_POST['usecoords']);

      // Validate required fields
      if (empty($cemname)) {
        throw new Exception(__('Cemetery name is required.', 'heritagepress'));
      }
      if (empty($country)) {
        throw new Exception(__('Country is required.', 'heritagepress'));
      }

      // Handle map file upload
      $maplink = '';
      if (isset($_FILES['newfile']) && $_FILES['newfile']['error'] === UPLOAD_ERR_OK) {
        $maplink = $this->handle_map_upload($_FILES['newfile']);
      } elseif (!empty($_POST['maplink'])) {
        $maplink = sanitize_text_field($_POST['maplink']);
      }

      // Process coordinates
      $latitude = str_replace(',', '.', $latitude);
      $longitude = str_replace(',', '.', $longitude);

      if ($latitude && $longitude && !$zoom) {
        $zoom = 13;
      }
      if (!$zoom) $zoom = 0;

      // Insert cemetery
      $table_name = $wpdb->prefix . 'hp_cemeteries';
      $result = $wpdb->insert(
        $table_name,
        array(
          'cemname' => $cemname,
          'maplink' => $maplink,
          'city' => $city,
          'county' => $county,
          'state' => $state,
          'country' => $country,
          'latitude' => $latitude,
          'longitude' => $longitude,
          'zoom' => $zoom,
          'notes' => $notes,
          'place' => $place
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
      );

      if ($result === false) {
        throw new Exception(__('Failed to create cemetery.', 'heritagepress'));
      }

      $cemetery_id = $wpdb->insert_id;

      // Handle place linking
      if (!empty($place)) {
        $this->handle_place_linking($place, $latitude, $longitude, $zoom, $notes, $use_coords);
      }

      // Log the action
      $this->log_admin_action("Added cemetery: $cemetery_id - $cemname");

      wp_redirect(add_query_arg(array(
        'page' => 'hp-cemeteries',
        'message' => urlencode(sprintf(__('Cemetery %d successfully added.', 'heritagepress'), $cemetery_id))
      ), admin_url('admin.php')));
      exit;
    } catch (Exception $e) {
      wp_redirect(add_query_arg(array(
        'page' => 'hp-cemeteries',
        'action' => 'add',
        'error' => urlencode($e->getMessage())
      ), admin_url('admin.php')));
      exit;
    }
  }

  /**
   * Update existing cemetery
   */
  private function update_cemetery()
  {
    global $wpdb;

    try {
      $cemetery_id = intval($_POST['cemeteryID']);
      if (!$cemetery_id) {
        throw new Exception(__('Invalid cemetery ID.', 'heritagepress'));
      }

      $cemname = sanitize_text_field($_POST['cemname']);
      $city = sanitize_text_field($_POST['city'] ?? '');
      $county = sanitize_text_field($_POST['county'] ?? '');
      $state = sanitize_text_field($_POST['state'] ?? '');
      $country = sanitize_text_field($_POST['country'] ?? '');
      $latitude = sanitize_text_field($_POST['latitude'] ?? '');
      $longitude = sanitize_text_field($_POST['longitude'] ?? '');
      $zoom = intval($_POST['zoom'] ?? 0);
      $notes = wp_kses_post($_POST['notes'] ?? '');
      $place = sanitize_text_field($_POST['place'] ?? '');
      $use_coords = isset($_POST['usecoords']);

      // Validate required fields
      if (empty($cemname)) {
        throw new Exception(__('Cemetery name is required.', 'heritagepress'));
      }
      if (empty($country)) {
        throw new Exception(__('Country is required.', 'heritagepress'));
      }

      // Get current maplink
      $table_name = $wpdb->prefix . 'hp_cemeteries';
      $current = $wpdb->get_row($wpdb->prepare(
        "SELECT maplink FROM $table_name WHERE cemeteryID = %d",
        $cemetery_id
      ));

      $maplink = $current->maplink ?? '';

      // Handle map file upload
      if (isset($_FILES['newfile']) && $_FILES['newfile']['error'] === UPLOAD_ERR_OK) {
        $maplink = $this->handle_map_upload($_FILES['newfile']);
      } elseif (!empty($_POST['maplink'])) {
        $maplink = sanitize_text_field($_POST['maplink']);
      }

      // Process coordinates
      $latitude = str_replace(',', '.', $latitude);
      $longitude = str_replace(',', '.', $longitude);

      if ($latitude && $longitude && !$zoom) {
        $zoom = 13;
      }
      if (!$zoom) $zoom = 0;

      // Update cemetery
      $result = $wpdb->update(
        $table_name,
        array(
          'cemname' => $cemname,
          'maplink' => $maplink,
          'city' => $city,
          'county' => $county,
          'state' => $state,
          'country' => $country,
          'latitude' => $latitude,
          'longitude' => $longitude,
          'zoom' => $zoom,
          'notes' => $notes,
          'place' => $place
        ),
        array('cemeteryID' => $cemetery_id),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s'),
        array('%d')
      );

      if ($result === false) {
        throw new Exception(__('Failed to update cemetery.', 'heritagepress'));
      }

      // Handle place linking
      if (!empty($place)) {
        $this->handle_place_linking($place, $latitude, $longitude, $zoom, $notes, $use_coords);
      }

      // Log the action
      $this->log_admin_action("Updated cemetery: $cemetery_id - $cemname");

      wp_redirect(add_query_arg(array(
        'page' => 'hp-cemeteries',
        'message' => urlencode(sprintf(__('Cemetery %d successfully updated.', 'heritagepress'), $cemetery_id))
      ), admin_url('admin.php')));
      exit;
    } catch (Exception $e) {
      wp_redirect(add_query_arg(array(
        'page' => 'hp-cemeteries',
        'action' => 'edit',
        'id' => intval($_POST['cemeteryID'] ?? 0),
        'error' => urlencode($e->getMessage())
      ), admin_url('admin.php')));
      exit;
    }
  }

  /**
   * Delete cemetery
   */
  private function delete_cemetery()
  {
    global $wpdb;

    try {
      $cemetery_id = intval($_POST['cemetery_id']);
      if (!$cemetery_id) {
        throw new Exception(__('Invalid cemetery ID.', 'heritagepress'));
      }

      $table_name = $wpdb->prefix . 'hp_cemeteries';

      // Get cemetery name for logging
      $cemetery = $wpdb->get_row($wpdb->prepare(
        "SELECT cemname FROM $table_name WHERE cemeteryID = %d",
        $cemetery_id
      ));

      $result = $wpdb->delete($table_name, array('cemeteryID' => $cemetery_id), array('%d'));

      if ($result === false) {
        throw new Exception(__('Failed to delete cemetery.', 'heritagepress'));
      }

      // Log the action
      $cemname = $cemetery->cemname ?? "ID $cemetery_id";
      $this->log_admin_action("Deleted cemetery: $cemetery_id - $cemname");

      wp_redirect(add_query_arg(array(
        'page' => 'hp-cemeteries',
        'message' => urlencode(__('Cemetery successfully deleted.', 'heritagepress'))
      ), admin_url('admin.php')));
      exit;
    } catch (Exception $e) {
      wp_redirect(add_query_arg(array(
        'page' => 'hp-cemeteries',
        'error' => urlencode($e->getMessage())
      ), admin_url('admin.php')));
      exit;
    }
  }

  /**
   * Delete selected cemeteries
   */
  private function delete_selected_cemeteries()
  {
    global $wpdb;

    try {
      $cemetery_ids = array_map('intval', $_POST['cemetery_ids'] ?? array());

      if (empty($cemetery_ids)) {
        throw new Exception(__('No cemeteries selected for deletion.', 'heritagepress'));
      }

      $table_name = $wpdb->prefix . 'hp_cemeteries';
      $placeholders = implode(',', array_fill(0, count($cemetery_ids), '%d'));

      $deleted = $wpdb->query($wpdb->prepare(
        "DELETE FROM $table_name WHERE cemeteryID IN ($placeholders)",
        $cemetery_ids
      ));

      if ($deleted === false) {
        throw new Exception(__('Failed to delete selected cemeteries.', 'heritagepress'));
      }

      // Log the action
      $this->log_admin_action("Deleted $deleted cemeteries");

      wp_redirect(add_query_arg(array(
        'page' => 'hp-cemeteries',
        'message' => urlencode(sprintf(__('%d cemeteries successfully deleted.', 'heritagepress'), $deleted))
      ), admin_url('admin.php')));
      exit;
    } catch (Exception $e) {
      wp_redirect(add_query_arg(array(
        'page' => 'hp-cemeteries',
        'error' => urlencode($e->getMessage())
      ), admin_url('admin.php')));
      exit;
    }
  }

  /**
   * Handle map file upload
   */
  private function handle_map_upload($file)
  {
    $upload_dir = wp_upload_dir();
    $heritagepress_dir = $upload_dir['basedir'] . '/heritagepress/maps';

    // Create directory if it doesn't exist
    if (!file_exists($heritagepress_dir)) {
      wp_mkdir_p($heritagepress_dir);
    }

    // Sanitize filename
    $filename = sanitize_file_name($file['name']);
    $upload_path = $heritagepress_dir . '/' . $filename;

    // Check file type
    $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'pdf');
    $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (!in_array($file_ext, $allowed_types)) {
      throw new Exception(__('Invalid file type. Allowed: JPG, PNG, GIF, PDF', 'heritagepress'));
    }

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
      throw new Exception(__('Failed to upload map file.', 'heritagepress'));
    }

    // Set proper permissions
    chmod($upload_path, 0644);

    return 'heritagepress/maps/' . $filename;
  }

  /**
   * Handle place linking logic
   */
  private function handle_place_linking($place, $latitude, $longitude, $zoom, $notes, $use_coords)
  {
    global $wpdb;

    $place = trim($place);
    if (empty($place)) {
      return;
    }

    $places_table = $wpdb->prefix . 'hp_places';

    // Check if place exists
    $existing_place = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM $places_table WHERE place = %s",
      $place
    ));

    if (!$existing_place) {
      // Create new place
      if (!$use_coords) {
        $latitude = $longitude = '';
        $zoom = 0;
      }

      $wpdb->insert(
        $places_table,
        array(
          'gedcom' => '', // Empty for cemetery-linked places
          'place' => $place,
          'placelevel' => 0,
          'latitude' => $latitude,
          'longitude' => $longitude,
          'zoom' => $zoom,
          'notes' => $notes
        ),
        array('%s', '%s', '%d', '%s', '%s', '%d', '%s')
      );
    } elseif ($use_coords) {
      // Update existing place with coordinates
      $wpdb->update(
        $places_table,
        array(
          'latitude' => $latitude,
          'longitude' => $longitude,
          'zoom' => $zoom
        ),
        array('place' => $place),
        array('%s', '%s', '%d'),
        array('%s')
      );
    }
  }

  /**
   * Log admin action
   */
  private function log_admin_action($message)
  {
    // Simple logging - could be expanded to use WordPress logging
    error_log('[HeritagePress Cemetery] ' . $message);
  }

  /**
   * AJAX handler for cemetery search
   */
  public function ajax_search_cemeteries()
  {
    check_ajax_referer('hp_cemetery_nonce', 'nonce');

    global $wpdb;

    $search = sanitize_text_field($_POST['search'] ?? '');
    $page = intval($_POST['page'] ?? 1);
    $per_page = 25;
    $offset = ($page - 1) * $per_page;

    $table_name = $wpdb->prefix . 'hp_cemeteries';
    $where_clause = '';
    $params = array();

    if (!empty($search)) {
      $where_clause = "WHERE (cemname LIKE %s OR city LIKE %s OR county LIKE %s OR state LIKE %s OR country LIKE %s OR cemeteryID LIKE %s)";
      $search_term = '%' . $wpdb->esc_like($search) . '%';
      $params = array($search_term, $search_term, $search_term, $search_term, $search_term, $search_term);
    }

    // Get total count
    $total_query = "SELECT COUNT(*) FROM $table_name $where_clause";
    $total = $wpdb->get_var($wpdb->prepare($total_query, $params));

    // Get cemeteries
    $query = "SELECT * FROM $table_name $where_clause ORDER BY cemname, city, county, state, country LIMIT %d, %d";
    $params[] = $offset;
    $params[] = $per_page;

    $cemeteries = $wpdb->get_results($wpdb->prepare($query, $params));

    wp_send_json_success(array(
      'cemeteries' => $cemeteries,
      'total' => $total,
      'page' => $page,
      'per_page' => $per_page
    ));
  }

  /**
   * AJAX handler for cemetery deletion
   */
  public function ajax_delete_cemetery()
  {
    check_ajax_referer('hp_cemetery_nonce', 'nonce');

    global $wpdb;

    $cemetery_id = intval($_POST['cemetery_id']);
    if (!$cemetery_id) {
      wp_send_json_error(__('Invalid cemetery ID.', 'heritagepress'));
    }

    $table_name = $wpdb->prefix . 'hp_cemeteries';

    // Get cemetery name for logging
    $cemetery = $wpdb->get_row($wpdb->prepare(
      "SELECT cemname FROM $table_name WHERE cemeteryID = %d",
      $cemetery_id
    ));

    $result = $wpdb->delete($table_name, array('cemeteryID' => $cemetery_id), array('%d'));

    if ($result === false) {
      wp_send_json_error(__('Failed to delete cemetery.', 'heritagepress'));
    }

    // Log the action
    $cemname = $cemetery->cemname ?? "ID $cemetery_id";
    $this->log_admin_action("Deleted cemetery: $cemetery_id - $cemname");

    wp_send_json_success(__('Cemetery successfully deleted.', 'heritagepress'));
  }

  /**
   * AJAX handler for getting cemetery details
   */
  public function ajax_get_cemetery_details()
  {
    check_ajax_referer('hp_cemetery_nonce', 'nonce');

    global $wpdb;

    $cemetery_id = intval($_POST['cemetery_id']);
    if (!$cemetery_id) {
      wp_send_json_error(__('Invalid cemetery ID.', 'heritagepress'));
    }

    $table_name = $wpdb->prefix . 'hp_cemeteries';
    $cemetery = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM $table_name WHERE cemeteryID = %d",
      $cemetery_id
    ));

    if (!$cemetery) {
      wp_send_json_error(__('Cemetery not found.', 'heritagepress'));
    }

    wp_send_json_success($cemetery);
  }

  /**
   * Main cemetery management page
   */
  public function cemetery_management_page()
  {
    $action = sanitize_text_field($_GET['action'] ?? 'list');
    $cemetery_id = intval($_GET['id'] ?? 0);

    switch ($action) {
      case 'add':
        $this->show_add_cemetery_form();
        break;
      case 'edit':
        $this->show_edit_cemetery_form($cemetery_id);
        break;
      default:
        $this->show_cemetery_list();
        break;
    }
  }

  /**
   * Show cemetery list view
   */
  private function show_cemetery_list()
  {
    include plugin_dir_path(__FILE__) . '../views/cemeteries-main.php';
  }

  /**
   * Show add cemetery form
   */
  private function show_add_cemetery_form()
  {
    include plugin_dir_path(__FILE__) . '../views/cemeteries-add.php';
  }

  /**
   * Show edit cemetery form
   */
  private function show_edit_cemetery_form($cemetery_id)
  {
    global $wpdb;

    $table_name = $wpdb->prefix . 'hp_cemeteries';
    $cemetery = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM $table_name WHERE cemeteryID = %d",
      $cemetery_id
    ));

    if (!$cemetery) {
      wp_die(__('Cemetery not found.', 'heritagepress'));
    }

    include plugin_dir_path(__FILE__) . '../views/cemeteries-edit.php';
  }

  /**
   * Get states for dropdown
   */
  public function get_states()
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hp_entities';
    return $wpdb->get_results("SELECT entity as state FROM $table_name WHERE entity_type = 'state' ORDER BY entity");
  }

  /**
   * Get countries for dropdown
   */
  public function get_countries()
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hp_entities';
    return $wpdb->get_results("SELECT entity as country FROM $table_name WHERE entity_type = 'country' ORDER BY entity");
  }
}

// Initialize the controller
new HP_Cemetery_Controller();
