<?php

/**
 * Album Controller for HeritagePress
 *
 * Handles album management functionality
 * including adding, updating, deleting albums,
 * and managing media within albums.
 * @package HeritagePress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

require_once plugin_dir_path(__FILE__) . '../../includes/controllers/class-hp-base-controller.php';
require_once plugin_dir_path(__FILE__) . '../traits/trait-hp-string-handler.php';

/**
 * Album Controller Class
 */
class HP_Album_Controller extends HP_Base_Controller
{
  use HP_String_Handler;

  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct('album');
    $this->init_hooks();
  }

  /**
   * Initialize hooks
   */
  private function init_hooks()
  {
    // AJAX handlers
    add_action('wp_ajax_hp_add_album', array($this, 'ajax_add_album'));
    add_action('wp_ajax_hp_update_album', array($this, 'ajax_update_album'));
    add_action('wp_ajax_hp_delete_album', array($this, 'ajax_delete_album'));
    add_action('wp_ajax_hp_get_album', array($this, 'ajax_get_album'));
    add_action('wp_ajax_hp_get_albums', array($this, 'ajax_get_albums'));
    add_action('wp_ajax_hp_search_media_for_album', array($this, 'ajax_search_media_for_album'));
    add_action('wp_ajax_hp_add_media_to_album', array($this, 'ajax_add_media_to_album'));
    add_action('wp_ajax_hp_remove_media_from_album', array($this, 'ajax_remove_media_from_album'));
  }

  /**
   * Display main page
   */
  public function display_page($current_tab = 'list')
  {
    switch ($current_tab) {
      case 'add':
        include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/albums-add.php';
        break;
      case 'edit':
        include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/albums-edit.php';
        break;
      case 'manage':
        include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/albums-manage.php';
        break;
      default:
        include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/albums-main.php';
        break;
    }
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
      case 'hp_add_album':
        $this->process_add_album();
        break;
      case 'hp_update_album':
        $this->process_update_album();
        break;
      case 'hp_delete_album':
        $this->process_delete_album();
        break;
    }
  }

  /**
   * Process add album form
   */
  private function process_add_album()
  {
    // Security checks
    if (!wp_verify_nonce($_POST['hp_album_nonce'], 'hp_add_album')) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!current_user_can('manage_options')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    // Get form data
    $albumname = sanitize_text_field($_POST['albumname'] ?? '');
    $description = sanitize_textarea_field($_POST['description'] ?? '');
    $keywords = sanitize_text_field($_POST['keywords'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;
    $alwayson = isset($_POST['alwayson']) ? 1 : 0;

    // Validate required fields
    if (empty($albumname)) {
      $this->add_notice(__('Album name is required.', 'heritagepress'), 'error');
      return;
    }

    // Check if album name already exists
    global $wpdb;
    $table_name = $this->safe_table($wpdb->prefix . 'hp_albums');

    [$where_sql, $where_params] = $this->safe_like('albumname', $albumname);
    $existing = $wpdb->get_var(
      $this->safe_prepare(
        "SELECT albumID FROM $table_name WHERE $where_sql",
        $where_params
      )
    );

    if ($existing) {
      $this->add_notice(__('Album name already exists.', 'heritagepress'), 'error');
      return;
    }

    // Insert new album
    $result = $wpdb->insert(
      $table_name,
      array(
        'albumname' => $this->ensure_string($albumname),
        'description' => $this->ensure_string($description),
        'keywords' => $this->ensure_string($keywords),
        'active' => (int)$active,
        'alwayson' => (int)$alwayson
      ),
      array('%s', '%s', '%s', '%d', '%d')
    );

    if ($result) {
      $album_id = $wpdb->insert_id;
      $this->add_notice(__('Album created successfully!', 'heritagepress'), 'success');

      // Redirect to edit page
      $redirect_url = admin_url('admin.php?page=heritagepress&section=albums&tab=edit&albumID=' . $album_id . '&added=1');
      wp_redirect($redirect_url);
      exit;
    } else {
      $this->add_notice(__('Failed to create album.', 'heritagepress'), 'error');
    }
  }

  /**
   * Process update album form
   */
  private function process_update_album()
  {
    // Security checks
    if (!wp_verify_nonce($_POST['hp_album_nonce'], 'hp_update_album')) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!current_user_can('manage_options')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    // Get form data
    $album_id = intval($_POST['albumID'] ?? 0);
    $albumname = sanitize_text_field($_POST['albumname'] ?? '');
    $description = sanitize_textarea_field($_POST['description'] ?? '');
    $keywords = sanitize_text_field($_POST['keywords'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;
    $alwayson = isset($_POST['alwayson']) ? 1 : 0;

    // Validate required fields
    if (empty($album_id) || empty($albumname)) {
      $this->add_notice(__('Album ID and name are required.', 'heritagepress'), 'error');
      return;
    }

    // Check if album name already exists (excluding current album)
    global $wpdb;
    $existing = $wpdb->get_var($wpdb->prepare(
      "SELECT albumID FROM {$wpdb->prefix}hp_albums WHERE albumname = %s AND albumID != %d",
      $albumname,
      $album_id
    ));

    if ($existing) {
      $this->add_notice(__('Album name already exists.', 'heritagepress'), 'error');
      return;
    }

    // Update album
    $result = $wpdb->update(
      $wpdb->prefix . 'hp_albums',
      array(
        'albumname' => $albumname,
        'description' => $description,
        'keywords' => $keywords,
        'active' => $active,
        'alwayson' => $alwayson
      ),
      array('albumID' => $album_id),
      array('%s', '%s', '%s', '%d', '%d'),
      array('%d')
    );

    if ($result !== false) {
      $this->add_notice(__('Album updated successfully!', 'heritagepress'), 'success');
    } else {
      $this->add_notice(__('Failed to update album.', 'heritagepress'), 'error');
    }
  }

  /**
   * Process delete album
   */
  private function process_delete_album()
  {
    // Security checks
    if (!wp_verify_nonce($_POST['hp_album_nonce'], 'hp_delete_album')) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!current_user_can('manage_options')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    $album_id = intval($_POST['albumID'] ?? 0);

    if (empty($album_id)) {
      $this->add_notice(__('Album ID is required.', 'heritagepress'), 'error');
      return;
    }

    global $wpdb;

    // Delete album links first
    $wpdb->delete(
      $wpdb->prefix . 'hp_albumlinks',
      array('albumID' => $album_id),
      array('%d')
    );

    // Delete album person links
    $wpdb->delete(
      $wpdb->prefix . 'hp_albumplinks',
      array('albumID' => $album_id),
      array('%d')
    );

    // Delete album
    $result = $wpdb->delete(
      $wpdb->prefix . 'hp_albums',
      array('albumID' => $album_id),
      array('%d')
    );

    if ($result) {
      $this->add_notice(__('Album deleted successfully!', 'heritagepress'), 'success');
    } else {
      $this->add_notice(__('Failed to delete album.', 'heritagepress'), 'error');
    }
  }

  /**
   * AJAX handler to add album
   */
  public function ajax_add_album()
  {
    if (!wp_verify_nonce($_POST['nonce'], 'hp_add_album')) {
      wp_send_json_error(__('Security check failed.', 'heritagepress'));
      return;
    }

    if (!current_user_can('manage_options')) {
      wp_send_json_error(__('Insufficient permissions.', 'heritagepress'));
      return;
    }

    // Get form data with guaranteed string values
    $albumname = $this->safe_text($_POST['albumname'] ?? '');
    $description = $this->safe_textarea($_POST['description'] ?? '');
    $keywords = $this->safe_text($_POST['keywords'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;
    $alwayson = isset($_POST['alwayson']) ? 1 : 0;

    // Validate required fields
    if (empty($albumname)) {
      $this->add_notice($this->ensure_string(__('Album name is required.', 'heritagepress')), 'error');
      return;
    }

    // Check if album name already exists
    global $wpdb;
    $table_name = $this->safe_table($wpdb->prefix . 'hp_albums');

    [$where_sql, $where_params] = $this->safe_like('albumname', $albumname);
    $existing = $wpdb->get_var(
      $this->safe_prepare(
        "SELECT albumID FROM $table_name WHERE $where_sql",
        $where_params
      )
    );

    if ($existing) {
      $this->add_notice($this->ensure_string(__('Album name already exists.', 'heritagepress')), 'error');
      return;
    }

    // Insert new album
    $result = $wpdb->insert(
      $table_name,
      array(
        'albumname' => $this->ensure_string($albumname),
        'description' => $this->ensure_string($description),
        'keywords' => $this->ensure_string($keywords),
        'active' => (int)$active,
        'alwayson' => (int)$alwayson
      ),
      array('%s', '%s', '%s', '%d', '%d')
    );

    if ($result) {
      wp_send_json_success(array(
        'albumID' => $wpdb->insert_id,
        'message' => __('Album created successfully.', 'heritagepress')
      ));
    } else {
      wp_send_json_error(__('Failed to create album.', 'heritagepress'));
    }
  }

  /**
   * AJAX handler to update album
   */
  public function ajax_update_album()
  {
    if (!wp_verify_nonce($_POST['nonce'], 'hp_update_album')) {
      wp_send_json_error(__('Security check failed.', 'heritagepress'));
      return;
    }

    if (!current_user_can('manage_options')) {
      wp_send_json_error(__('Insufficient permissions.', 'heritagepress'));
      return;
    }

    $album_id = intval($_POST['albumID'] ?? 0);
    $albumname = sanitize_text_field($_POST['albumname'] ?? '');
    $description = sanitize_textarea_field($_POST['description'] ?? '');
    $keywords = sanitize_text_field($_POST['keywords'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;
    $alwayson = isset($_POST['alwayson']) ? 1 : 0;

    if (empty($album_id) || empty($albumname)) {
      wp_send_json_error(__('Album ID and name are required.', 'heritagepress'));
      return;
    }

    global $wpdb;

    // Update
    $result = $wpdb->update(
      $wpdb->prefix . 'hp_albums',
      array(
        'albumname' => $albumname,
        'description' => $description,
        'keywords' => $keywords,
        'active' => $active,
        'alwayson' => $alwayson
      ),
      array('albumID' => $album_id),
      array('%s', '%s', '%s', '%d', '%d'),
      array('%d')
    );

    if ($result !== false) {
      wp_send_json_success(array('message' => __('Album updated successfully.', 'heritagepress')));
    } else {
      wp_send_json_error(__('Failed to update album.', 'heritagepress'));
    }
  }

  /**
   * AJAX handler to delete album
   */
  public function ajax_delete_album()
  {
    if (!wp_verify_nonce($_POST['nonce'], 'hp_delete_album')) {
      wp_send_json_error(__('Security check failed.', 'heritagepress'));
      return;
    }

    if (!current_user_can('manage_options')) {
      wp_send_json_error(__('Insufficient permissions.', 'heritagepress'));
      return;
    }

    $album_id = intval($_POST['albumID'] ?? 0);
    if (empty($album_id)) {
      wp_send_json_error(__('Album ID required.', 'heritagepress'));
      return;
    }

    global $wpdb;

    // Delete links first
    $wpdb->delete($wpdb->prefix . 'hp_albumlinks', array('albumID' => $album_id), array('%d'));
    $wpdb->delete($wpdb->prefix . 'hp_albumplinks', array('albumID' => $album_id), array('%d'));

    // Delete album
    $result = $wpdb->delete($wpdb->prefix . 'hp_albums', array('albumID' => $album_id), array('%d'));

    if ($result) {
      wp_send_json_success(array('message' => __('Album deleted successfully.', 'heritagepress')));
    } else {
      wp_send_json_error(__('Failed to delete album.', 'heritagepress'));
    }
  }

  /**
   * AJAX handler to get single album
   */
  public function ajax_get_album()
  {
    if (!wp_verify_nonce($_POST['nonce'], 'hp_get_album')) {
      wp_send_json_error(__('Security check failed.', 'heritagepress'));
      return;
    }

    if (!current_user_can('manage_options')) {
      wp_send_json_error(__('Insufficient permissions.', 'heritagepress'));
      return;
    }

    $album_id = intval($_POST['albumID'] ?? 0);
    if (empty($album_id)) {
      wp_send_json_error(__('Album ID required.', 'heritagepress'));
      return;
    }

    global $wpdb;
    $album = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM {$wpdb->prefix}hp_albums WHERE albumID = %d",
      $album_id
    ));

    if ($album) {
      wp_send_json_success($album);
    } else {
      wp_send_json_error(__('Album not found.', 'heritagepress'));
    }
  }

  /**
   * AJAX handler to get albums list
   */
  public function ajax_get_albums()
  {
    if (!wp_verify_nonce($_POST['nonce'], 'hp_get_albums')) {
      wp_send_json_error(__('Security check failed.', 'heritagepress'));
      return;
    }

    if (!current_user_can('manage_options')) {
      wp_send_json_error(__('Insufficient permissions.', 'heritagepress'));
      return;
    }

    global $wpdb;
    $albums = $wpdb->get_results(
      "SELECT * FROM {$wpdb->prefix}hp_albums ORDER BY albumname ASC"
    );

    wp_send_json_success($albums);
  }

  /**
   * AJAX handler to search media for album (equivalent to admin_add2albumxml.php)
   */
  public function ajax_search_media_for_album()
  {
    if (!wp_verify_nonce($_POST['nonce'], 'hp_search_media')) {
      wp_send_json_error(__('Security check failed.', 'heritagepress'));
      return;
    }

    if (!current_user_can('manage_options')) {
      wp_send_json_error(__('Insufficient permissions.', 'heritagepress'));
      return;
    }

    $album_id = intval($_POST['albumID'] ?? 0);
    $search_string = sanitize_text_field($_POST['searchstring'] ?? '');
    $mediatype_id = sanitize_text_field($_POST['mediatypeID'] ?? '');
    $tree = sanitize_text_field($_POST['tree'] ?? '');
    $offset = intval($_POST['offset'] ?? 0);
    $per_page = intval($_POST['perpage'] ?? 50);

    global $wpdb;

    // Build WHERE clause
    $where_conditions = array();
    $where_params = array();

    if (!empty($search_string)) {
      $like_search = '%' . $wpdb->esc_like($search_string) . '%';
      $where_conditions[] = "(m.mediaID LIKE %s OR m.description LIKE %s OR m.path LIKE %s OR m.notes LIKE %s OR m.owner LIKE %s OR m.bodytext LIKE %s)";
      $where_params = array_merge($where_params, array($like_search, $like_search, $like_search, $like_search, $like_search, $like_search));
    }

    if (!empty($tree)) {
      $where_conditions[] = "(m.gedcom = '' OR m.gedcom = %s)";
      $where_params[] = $tree;
    }

    if (!empty($mediatype_id)) {
      $where_conditions[] = "m.mediatypeID = %s";
      $where_params[] = $mediatype_id;
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    // Get total count
    $count_query = "SELECT COUNT(m.mediaID) as total
                        FROM {$wpdb->prefix}hp_media m
                        $where_clause";

    if (!empty($where_params)) {
      $total_rows = $wpdb->get_var($wpdb->prepare($count_query, $where_params));
    } else {
      $total_rows = $wpdb->get_var($count_query);
    }

    // Get paginated results
    $limit_clause = "LIMIT $offset, $per_page";
    $query = "SELECT m.mediaID, m.description, m.notes, m.thumbpath, m.mediatypeID,
                         m.usecollfolder, m.datetaken, m.gedcom, m.path
                  FROM {$wpdb->prefix}hp_media m
                  $where_clause
                  ORDER BY m.description
                  $limit_clause";

    if (!empty($where_params)) {
      $results = $wpdb->get_results($wpdb->prepare($query, $where_params));
    } else {
      $results = $wpdb->get_results($query);
    }

    // Get media already in album
    $existing_media = array();
    if ($album_id) {
      $existing_results = $wpdb->get_results($wpdb->prepare(
        "SELECT mediaID FROM {$wpdb->prefix}hp_albumlinks WHERE albumID = %d",
        $album_id
      ));
      foreach ($existing_results as $row) {
        $existing_media[] = $row->mediaID;
      }
    }

    // Format results
    $formatted_results = array();
    foreach ($results as $row) {
      $formatted_results[] = array(
        'mediaID' => $row->mediaID,
        'description' => $row->description,
        'notes' => $row->notes,
        'thumbpath' => $row->thumbpath,
        'mediatypeID' => $row->mediatypeID,
        'datetaken' => $row->datetaken,
        'gedcom' => $row->gedcom,
        'path' => $row->path,
        'already_added' => in_array($row->mediaID, $existing_media)
      );
    }

    wp_send_json_success(array(
      'results' => $formatted_results,
      'total' => $total_rows,
      'offset' => $offset,
      'per_page' => $per_page
    ));
  }

  /**
   * AJAX handler to add media to album
   */
  public function ajax_add_media_to_album()
  {
    if (!wp_verify_nonce($_POST['nonce'], 'hp_manage_album_media')) {
      wp_send_json_error(__('Security check failed.', 'heritagepress'));
      return;
    }

    if (!current_user_can('manage_options')) {
      wp_send_json_error(__('Insufficient permissions.', 'heritagepress'));
      return;
    }

    $album_id = intval($_POST['albumID'] ?? 0);
    $media_id = intval($_POST['mediaID'] ?? 0);

    if (empty($album_id) || empty($media_id)) {
      wp_send_json_error(__('Album ID and Media ID are required.', 'heritagepress'));
      return;
    }

    global $wpdb;

    // Check if already exists
    $existing = $wpdb->get_var($wpdb->prepare(
      "SELECT albumlinkID FROM {$wpdb->prefix}hp_albumlinks WHERE albumID = %d AND mediaID = %d",
      $album_id,
      $media_id
    ));

    if ($existing) {
      wp_send_json_error(__('Media item is already in this album.', 'heritagepress'));
      return;
    }

    // Get next order number
    $max_order = $wpdb->get_var($wpdb->prepare(
      "SELECT MAX(ordernum) FROM {$wpdb->prefix}hp_albumlinks WHERE albumID = %d",
      $album_id
    ));
    $order_num = $max_order ? $max_order + 1 : 1;

    // Insert link
    $result = $wpdb->insert(
      $wpdb->prefix . 'hp_albumlinks',
      array(
        'albumID' => $album_id,
        'mediaID' => $media_id,
        'ordernum' => $order_num,
        'defphoto' => ''
      ),
      array('%d', '%d', '%d', '%s')
    );

    if ($result) {
      wp_send_json_success(array('message' => __('Media added to album successfully.', 'heritagepress')));
    } else {
      wp_send_json_error(__('Failed to add media to album.', 'heritagepress'));
    }
  }

  /**
   * AJAX handler to remove media from album
   */
  public function ajax_remove_media_from_album()
  {
    if (!wp_verify_nonce($_POST['nonce'], 'hp_manage_album_media')) {
      wp_send_json_error(__('Security check failed.', 'heritagepress'));
      return;
    }

    if (!current_user_can('manage_options')) {
      wp_send_json_error(__('Insufficient permissions.', 'heritagepress'));
      return;
    }

    $album_id = intval($_POST['albumID'] ?? 0);
    $media_id = intval($_POST['mediaID'] ?? 0);

    if (empty($album_id) || empty($media_id)) {
      wp_send_json_error(__('Album ID and Media ID are required.', 'heritagepress'));
      return;
    }

    global $wpdb;

    $result = $wpdb->delete(
      $wpdb->prefix . 'hp_albumlinks',
      array(
        'albumID' => $album_id,
        'mediaID' => $media_id
      ),
      array('%d', '%d')
    );

    if ($result) {
      wp_send_json_success(array('message' => __('Media removed from album successfully.', 'heritagepress')));
    } else {
      wp_send_json_error(__('Failed to remove media from album.', 'heritagepress'));
    }
  }

  /**
   * Get linked entities for an album
   *
   * @param int $album_id Album ID
   * @param string $tree_id Optional tree ID to filter by
   * @param int $limit Max number of entities to return
   * @return array Array of linked entities
   */
  public function get_linked_entities($album_id, $tree_id = '', $limit = 10)
  {
    global $wpdb;

    $where_clause = '';
    $where_params = array($album_id);

    if (!empty($tree_id)) {
      $where_clause = ' AND e.tree_id = %s';
      $where_params[] = $tree_id;
    }

    $query = "SELECT e.entityID, e.entityType, p.personID, p.lastname, p.firstname, p.suffix, p.prefix,
                         f.familyID, s.sourceID, s.title as stitle, r.repoID, r.reponame
                  FROM {$wpdb->prefix}hp_album2entities e
                  LEFT JOIN {$wpdb->prefix}hp_people p ON e.entityID = p.personID AND e.entityType = 'person'
                  LEFT JOIN {$wpdb->prefix}hp_families f ON e.entityID = f.familyID AND e.entityType = 'family'
                  LEFT JOIN {$wpdb->prefix}hp_sources s ON e.entityID = s.sourceID AND e.entityType = 'source'
                  LEFT JOIN {$wpdb->prefix}hp_repositories r ON e.entityID = r.repoID AND e.entityType = 'repository'
                  WHERE e.albumID = %d{$where_clause}
                  ORDER BY
                    CASE
                      WHEN p.lastname IS NOT NULL THEN p.lastname
                      WHEN s.title IS NOT NULL THEN s.title
                      WHEN r.reponame IS NOT NULL THEN r.reponame
                      ELSE e.entityID
                    END
                  LIMIT %d";

    $where_params[] = $limit;

    return $wpdb->get_results($wpdb->prepare($query, $where_params));
  }
}
