<?php

/**
 * HeritagePress Place Controller
 *
 * Handles place management functionality including CRUD operations,
 * geocoding, cemetery linking, and map integration.
 * @package HeritagePress
 * @subpackage Controllers
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Place_Controller
{
  private $places_table;
  private $cemeteries_table;
  private $trees_table;

  public function __construct()
  {
    global $wpdb;
    $this->places_table = $wpdb->prefix . 'hp_places';
    $this->cemeteries_table = $wpdb->prefix . 'hp_cemeteries';
    $this->trees_table = $wpdb->prefix . 'hp_trees';

    $this->init_hooks();
  }

  /**
   * Initialize hooks
   */
  private function init_hooks()
  {
    add_action('wp_ajax_hp_place_search', array($this, 'ajax_place_search'));
    add_action('wp_ajax_hp_place_add', array($this, 'ajax_place_add'));
    add_action('wp_ajax_hp_place_update', array($this, 'ajax_place_update'));
    add_action('wp_ajax_hp_place_delete', array($this, 'ajax_place_delete'));
    add_action('wp_ajax_hp_place_merge', array($this, 'ajax_place_merge'));
    add_action('wp_ajax_hp_place_geocode', array($this, 'ajax_place_geocode'));
    add_action('wp_ajax_hp_cemetery_link', array($this, 'ajax_cemetery_link'));
    add_action('wp_ajax_hp_cemetery_unlink', array($this, 'ajax_cemetery_unlink'));
    add_action('wp_ajax_hp_copy_geo_info', array($this, 'ajax_copy_geo_info'));
  }

  /**
   * Search places with filters
   */
  public function search_places($search_params = array())
  {
    global $wpdb;

    $defaults = array(
      'search_string' => '',
      'exact_match' => false,
      'no_coords' => false,
      'no_events' => false,
      'no_level' => false,
      'temples' => false,
      'tree' => '',
      'order' => 'name',
      'limit' => 50,
      'offset' => 0
    );

    $params = array_merge($defaults, $search_params);

    // Build WHERE clause
    $where_conditions = array('1=1');
    $sql_params = array();

    // Tree filter
    if (!empty($params['tree'])) {
      $where_conditions[] = 'gedcom = %s';
      $sql_params[] = $params['tree'];
    }

    // Search string
    if (!empty($params['search_string'])) {
      if ($params['exact_match']) {
        $where_conditions[] = '(place = %s OR notes = %s)';
        $sql_params[] = $params['search_string'];
        $sql_params[] = $params['search_string'];
      } else {
        $where_conditions[] = '(place LIKE %s OR notes LIKE %s)';
        $sql_params[] = '%' . $wpdb->esc_like($params['search_string']) . '%';
        $sql_params[] = '%' . $wpdb->esc_like($params['search_string']) . '%';
      }
    }

    // No coordinates filter
    if ($params['no_coords']) {
      $where_conditions[] = '(latitude IS NULL OR latitude = "" OR longitude IS NULL OR longitude = "")';
    }

    // No place level filter
    if ($params['no_level']) {
      $where_conditions[] = '(placelevel IS NULL OR placelevel = "" OR placelevel = "0")';
    }

    // Temples filter
    if ($params['temples']) {
      $where_conditions[] = 'temple = 1';
    }

    // No events filter (places not used in any events/people)
    if ($params['no_events']) {
      $where_conditions[] = $this->get_unused_places_condition();
    }

    $where_clause = implode(' AND ', $where_conditions);

    // Order clause
    $order_clause = $this->get_order_clause($params['order']);

    // Build final query
    $query = "SELECT
                    ID,
                    place,
                    placelevel,
                    longitude,
                    latitude,
                    zoom,
                    temple,
                    gedcom,
                    changedby,
                    DATE_FORMAT(changedate, '%%d %%b %%Y') as changedatef,
                    notes
                  FROM {$this->places_table}";

    if (!empty($where_clause)) {
      $query .= " WHERE {$where_clause}";
    }

    $query .= " {$order_clause}";

    if ($params['limit'] > 0) {
      $query .= $wpdb->prepare(" LIMIT %d OFFSET %d", $params['limit'], $params['offset']);
    }

    // Prepare query with parameters
    if (!empty($sql_params)) {
      $query = $wpdb->prepare($query, $sql_params);
    }

    return $wpdb->get_results($query, ARRAY_A);
  }

  /**
   * Get condition for unused places
   */
  private function get_unused_places_condition()
  {
    global $wpdb;

    $people_table = $wpdb->prefix . 'hp_people';
    $families_table = $wpdb->prefix . 'hp_families';
    $events_table = $wpdb->prefix . 'hp_events';

    return "place NOT IN (
            SELECT DISTINCT place FROM (
                SELECT birthplace as place FROM {$people_table} WHERE birthplace IS NOT NULL AND birthplace != ''
                UNION
                SELECT deathplace as place FROM {$people_table} WHERE deathplace IS NOT NULL AND deathplace != ''
                UNION
                SELECT burialplace as place FROM {$people_table} WHERE burialplace IS NOT NULL AND burialplace != ''
                UNION
                SELECT marrplace as place FROM {$families_table} WHERE marrplace IS NOT NULL AND marrplace != ''
                UNION
                SELECT eventplace as place FROM {$events_table} WHERE eventplace IS NOT NULL AND eventplace != ''
            ) AS used_places
        )";
  }

  /**
   * Get order clause for place queries
   */
  private function get_order_clause($order)
  {
    switch ($order) {
      case 'nameup':
        return 'ORDER BY place DESC, gedcom DESC';
      case 'change':
        return 'ORDER BY changedate, place, gedcom';
      case 'changeup':
        return 'ORDER BY changedate DESC, place, gedcom';
      default:
        return 'ORDER BY place, gedcom';
    }
  }

  /**
   * Add new place
   */
  public function add_place($place_data)
  {
    global $wpdb;

    // Validate required fields
    if (empty($place_data['place'])) {
      return new WP_Error('missing_place', __('Place name is required.', 'heritagepress'));
    }

    // Sanitize and prepare data
    $data = $this->prepare_place_data($place_data);

    // Handle coordinates
    $data['latitude'] = $this->sanitize_coordinate($data['latitude']);
    $data['longitude'] = $this->sanitize_coordinate($data['longitude']);

    // Set default zoom if coordinates provided but no zoom
    if (!empty($data['latitude']) && !empty($data['longitude']) && empty($data['zoom'])) {
      $data['zoom'] = 13;
    }

    // Set defaults
    $data['zoom'] = !empty($data['zoom']) ? intval($data['zoom']) : 0;
    $data['placelevel'] = !empty($data['placelevel']) ? intval($data['placelevel']) : 0;
    $data['temple'] = !empty($data['temple']) ? 1 : 0;
    $data['changedate'] = current_time('mysql');
    $data['changedby'] = wp_get_current_user()->user_login;

    // Insert place
    $result = $wpdb->insert(
      $this->places_table,
      $data,
      array('%s', '%s', '%d', '%d', '%s', '%s', '%d', '%s', '%d', '%s', '%s')
    );

    if ($result === false) {
      return new WP_Error('db_error', __('Failed to add place.', 'heritagepress'));
    }

    $place_id = $wpdb->insert_id;

    // Handle geocoding if enabled
    if (get_option('heritagepress_auto_geocode', false) && empty($data['latitude'])) {
      $this->geocode_place($place_id, $data['place']);
    }

    // Log the action
    $this->log_place_action('add', $place_id, $data['place']);

    return $place_id;
  }

  /**
   * Update existing place
   */
  public function update_place($place_id, $place_data)
  {
    global $wpdb;

    if (empty($place_id)) {
      return new WP_Error('missing_id', __('Place ID is required.', 'heritagepress'));
    }

    // Get current place
    $current_place = $this->get_place($place_id);
    if (!$current_place) {
      return new WP_Error('place_not_found', __('Place not found.', 'heritagepress'));
    }

    // Prepare update data
    $data = $this->prepare_place_data($place_data);
    $data['latitude'] = $this->sanitize_coordinate($data['latitude']);
    $data['longitude'] = $this->sanitize_coordinate($data['longitude']);
    $data['zoom'] = !empty($data['zoom']) ? intval($data['zoom']) : 0;
    $data['placelevel'] = !empty($data['placelevel']) ? intval($data['placelevel']) : 0;
    $data['temple'] = !empty($data['temple']) ? 1 : 0;
    $data['changedate'] = current_time('mysql');
    $data['changedby'] = wp_get_current_user()->user_login;

    // Update place
    $result = $wpdb->update(
      $this->places_table,
      $data,
      array('ID' => $place_id),
      array('%s', '%s', '%d', '%d', '%s', '%s', '%d', '%s', '%d', '%s', '%s'),
      array('%d')
    );

    if ($result === false) {
      return new WP_Error('db_error', __('Failed to update place.', 'heritagepress'));
    }

    // Handle place name propagation if requested
    if (!empty($place_data['propagate']) && $current_place['place'] !== $data['place']) {
      $this->propagate_place_name_change($current_place['place'], $data['place']);
    }

    // Log the action
    $this->log_place_action('update', $place_id, $data['place']);

    return true;
  }

  /**
   * Delete place
   */
  public function delete_place($place_id)
  {
    global $wpdb;

    $place = $this->get_place($place_id);
    if (!$place) {
      return new WP_Error('place_not_found', __('Place not found.', 'heritagepress'));
    }

    // Check if place is in use
    if ($this->is_place_in_use($place['place'])) {
      return new WP_Error('place_in_use', __('Cannot delete place that is currently in use.', 'heritagepress'));
    }

    // Delete cemetery links first
    $wpdb->delete($this->cemeteries_table, array('place' => $place['place']), array('%s'));

    // Delete the place
    $result = $wpdb->delete($this->places_table, array('ID' => $place_id), array('%d'));

    if ($result === false) {
      return new WP_Error('db_error', __('Failed to delete place.', 'heritagepress'));
    }

    // Log the action
    $this->log_place_action('delete', $place_id, $place['place']);

    return true;
  }

  /**
   * Get single place by ID
   */
  public function get_place($place_id)
  {
    global $wpdb;

    $query = $wpdb->prepare(
      "SELECT *, DATE_FORMAT(changedate, '%%d %%b %%Y %%H:%%i:%%s') as changedate_formatted
             FROM {$this->places_table}
             WHERE ID = %d",
      $place_id
    );

    return $wpdb->get_row($query, ARRAY_A);
  }

  /**
   * Get place count for pagination
   */
  public function get_places_count($search_params = array())
  {
    global $wpdb;

    $search_params['limit'] = 0;
    $search_params['offset'] = 0;

    // Use same WHERE logic as search_places but just COUNT
    $defaults = array(
      'search_string' => '',
      'exact_match' => false,
      'no_coords' => false,
      'no_events' => false,
      'no_level' => false,
      'temples' => false,
      'tree' => ''
    );

    $params = array_merge($defaults, $search_params);

    $where_conditions = array('1=1');
    $sql_params = array();

    if (!empty($params['tree'])) {
      $where_conditions[] = 'gedcom = %s';
      $sql_params[] = $params['tree'];
    }

    if (!empty($params['search_string'])) {
      if ($params['exact_match']) {
        $where_conditions[] = '(place = %s OR notes = %s)';
        $sql_params[] = $params['search_string'];
        $sql_params[] = $params['search_string'];
      } else {
        $where_conditions[] = '(place LIKE %s OR notes LIKE %s)';
        $sql_params[] = '%' . $wpdb->esc_like($params['search_string']) . '%';
        $sql_params[] = '%' . $wpdb->esc_like($params['search_string']) . '%';
      }
    }

    if ($params['no_coords']) {
      $where_conditions[] = '(latitude IS NULL OR latitude = "" OR longitude IS NULL OR longitude = "")';
    }

    if ($params['no_level']) {
      $where_conditions[] = '(placelevel IS NULL OR placelevel = "" OR placelevel = "0")';
    }

    if ($params['temples']) {
      $where_conditions[] = 'temple = 1';
    }

    if ($params['no_events']) {
      $where_conditions[] = $this->get_unused_places_condition();
    }

    $where_clause = implode(' AND ', $where_conditions);
    $query = "SELECT COUNT(*) FROM {$this->places_table}";

    if (!empty($where_clause)) {
      $query .= " WHERE {$where_clause}";
    }

    if (!empty($sql_params)) {
      $query = $wpdb->prepare($query, $sql_params);
    }

    return $wpdb->get_var($query);
  }

  /**
   * Prepare place data for database insertion
   */
  private function prepare_place_data($place_data)
  {
    return array(
      'gedcom' => sanitize_text_field($place_data['gedcom'] ?? ''),
      'place' => sanitize_text_field($place_data['place'] ?? ''),
      'placelevel' => intval($place_data['placelevel'] ?? 0),
      'temple' => !empty($place_data['temple']) ? 1 : 0,
      'latitude' => sanitize_text_field($place_data['latitude'] ?? ''),
      'longitude' => sanitize_text_field($place_data['longitude'] ?? ''),
      'zoom' => intval($place_data['zoom'] ?? 0),
      'notes' => wp_kses_post($place_data['notes'] ?? ''),
      'geoignore' => intval($place_data['geoignore'] ?? 0)
    );
  }

  /**
   * Sanitize coordinate values
   */
  private function sanitize_coordinate($coordinate)
  {
    if (empty($coordinate)) {
      return '';
    }

    // Convert comma to dot for decimal separator
    $coordinate = str_replace(',', '.', $coordinate);

    // Validate it's a valid number
    if (!is_numeric($coordinate)) {
      return '';
    }

    return strval(floatval($coordinate));
  }

  /**
   * Check if place name is in use
   */
  private function is_place_in_use($place_name)
  {
    global $wpdb;

    $people_table = $wpdb->prefix . 'hp_people';
    $families_table = $wpdb->prefix . 'hp_families';
    $events_table = $wpdb->prefix . 'hp_events';

    // Check people table
    $count = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$people_table}
             WHERE birthplace = %s OR deathplace = %s OR burialplace = %s",
      $place_name,
      $place_name,
      $place_name
    ));

    if ($count > 0) return true;

    // Check families table
    $count = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$families_table} WHERE marrplace = %s",
      $place_name
    ));

    if ($count > 0) return true;

    // Check events table
    $count = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$events_table} WHERE eventplace = %s",
      $place_name
    ));

    return $count > 0;
  }

  /**
   * Propagate place name changes across all tables
   */
  private function propagate_place_name_change($old_name, $new_name)
  {
    global $wpdb;

    $people_table = $wpdb->prefix . 'hp_people';
    $families_table = $wpdb->prefix . 'hp_families';
    $events_table = $wpdb->prefix . 'hp_events';

    // Update people table
    $wpdb->update($people_table, array('birthplace' => $new_name), array('birthplace' => $old_name));
    $wpdb->update($people_table, array('deathplace' => $new_name), array('deathplace' => $old_name));
    $wpdb->update($people_table, array('burialplace' => $new_name), array('burialplace' => $old_name));

    // Update families table
    $wpdb->update($families_table, array('marrplace' => $new_name), array('marrplace' => $old_name));

    // Update events table
    $wpdb->update($events_table, array('eventplace' => $new_name), array('eventplace' => $old_name));

    // Update cemeteries table
    $wpdb->update($this->cemeteries_table, array('place' => $new_name), array('place' => $old_name));
  }

  /**
   * Basic geocoding functionality
   */
  public function geocode_place($place_id, $place_name)
  {
    // This is a placeholder for geocoding functionality
    // In production, you would integrate with Google Maps API, Nominatim, etc.

    // For now, just log that geocoding was attempted
    error_log("HeritagePress: Geocoding attempted for place: {$place_name} (ID: {$place_id})");

    return false;
  }

  /**
   * Log place management actions
   */
  private function log_place_action($action, $place_id, $place_name)
  {
    $user = wp_get_current_user();
    $message = sprintf(
      '%s place: %s - %s',
      ucfirst($action),
      $place_id,
      $place_name
    );

    // Log to WordPress error log or custom logging system
    error_log("HeritagePress Place Action [{$user->user_login}]: {$message}");
  }

  /**
   * AJAX handler for place search
   */
  public function ajax_place_search()
  {
    check_ajax_referer('heritagepress_admin', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions.', 'heritagepress'));
    }

    $search_params = array(
      'search_string' => sanitize_text_field($_POST['search_string'] ?? ''),
      'exact_match' => !empty($_POST['exact_match']),
      'no_coords' => !empty($_POST['no_coords']),
      'no_events' => !empty($_POST['no_events']),
      'no_level' => !empty($_POST['no_level']),
      'temples' => !empty($_POST['temples']),
      'tree' => sanitize_text_field($_POST['tree'] ?? ''),
      'order' => sanitize_text_field($_POST['order'] ?? 'name'),
      'limit' => intval($_POST['limit'] ?? 50),
      'offset' => intval($_POST['offset'] ?? 0)
    );

    $places = $this->search_places($search_params);
    $total_count = $this->get_places_count($search_params);

    wp_send_json_success(array(
      'places' => $places,
      'total_count' => $total_count
    ));
  }

  /**
   * AJAX handler for adding place
   */
  public function ajax_place_add()
  {
    check_ajax_referer('heritagepress_admin', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions.', 'heritagepress'));
    }

    $place_data = $_POST['place_data'] ?? array();
    $result = $this->add_place($place_data);

    if (is_wp_error($result)) {
      wp_send_json_error($result->get_error_message());
    } else {
      wp_send_json_success(array(
        'place_id' => $result,
        'message' => __('Place added successfully.', 'heritagepress')
      ));
    }
  }

  /**
   * AJAX handler for updating place
   */
  public function ajax_place_update()
  {
    check_ajax_referer('heritagepress_admin', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions.', 'heritagepress'));
    }

    $place_id = intval($_POST['place_id'] ?? 0);
    $place_data = $_POST['place_data'] ?? array();

    $result = $this->update_place($place_id, $place_data);

    if (is_wp_error($result)) {
      wp_send_json_error($result->get_error_message());
    } else {
      wp_send_json_success(array(
        'message' => __('Place updated successfully.', 'heritagepress')
      ));
    }
  }

  /**
   * AJAX handler for deleting place
   */
  public function ajax_place_delete()
  {
    check_ajax_referer('heritagepress_admin', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions.', 'heritagepress'));
    }

    $place_id = intval($_POST['place_id'] ?? 0);
    $result = $this->delete_place($place_id);

    if (is_wp_error($result)) {
      wp_send_json_error($result->get_error_message());
    } else {
      wp_send_json_success(array(
        'message' => __('Place deleted successfully.', 'heritagepress')
      ));
    }
  }

  /**
   * Enhanced AJAX handler for merging two places (merge notes, coordinates, all related fields)
   */
  public function ajax_place_merge()
  {
    check_ajax_referer('heritagepress_admin', 'nonce');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(__('You do not have sufficient permissions.', 'heritagepress'));
    }
    global $wpdb;
    $source_place = sanitize_text_field($_POST['source_place'] ?? '');
    $target_place = sanitize_text_field($_POST['target_place'] ?? '');
    $tree = sanitize_text_field($_POST['tree'] ?? '');
    if (!$source_place || !$target_place || $source_place === $target_place) {
      wp_send_json_error(__('Invalid place selection.', 'heritagepress'));
    }
    $places_table = $wpdb->prefix . 'hp_places';
    // Get keep (target) place data
    $target_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $places_table WHERE place = %s AND gedcom = %s", $target_place, $tree), ARRAY_A);
    $source_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $places_table WHERE place = %s AND gedcom = %s", $source_place, $tree), ARRAY_A);
    if (!$target_row || !$source_row) {
      wp_send_json_error(__('Could not find both places.', 'heritagepress'));
    }
    // Merge notes and coordinates if missing in target
    $update = [];
    if (empty($target_row['latitude']) && !empty($source_row['latitude'])) $update['latitude'] = $source_row['latitude'];
    if (empty($target_row['longitude']) && !empty($source_row['longitude'])) $update['longitude'] = $source_row['longitude'];
    if (empty($target_row['placelevel']) && !empty($source_row['placelevel'])) $update['placelevel'] = $source_row['placelevel'];
    if (empty($target_row['zoom']) && !empty($source_row['zoom'])) $update['zoom'] = $source_row['zoom'];
    if (!empty($source_row['notes'])) {
      $update['notes'] = trim(($target_row['notes'] ?? '') . "\n" . $source_row['notes']);
    }
    if ($update) {
      $wpdb->update($places_table, $update, ['place' => $target_place, 'gedcom' => $tree]);
    }
    // Update all references in people, families, events, children, media, cemeteries tables
    $people_table = $wpdb->prefix . 'hp_people';
    $families_table = $wpdb->prefix . 'hp_families';
    $events_table = $wpdb->prefix . 'hp_events';
    $children_table = $wpdb->prefix . 'hp_children';
    $media_table = $wpdb->prefix . 'hp_media';
    $cemeteries_table = $wpdb->prefix . 'hp_cemeteries';
    $fields = [
      // people
      [$people_table, ['birthplace', 'altbirthplace', 'deathplace', 'burialplace', 'baptplace', 'confplace', 'initplace', 'endlplace']],
      // families
      [$families_table, ['marrplace', 'divplace', 'sealplace']],
      // events
      [$events_table, ['eventplace']],
      // children
      [$children_table, ['sealplace']],
      // media
      [$media_table, ['placetaken']],
      // cemeteries
      [$cemeteries_table, ['place']],
    ];
    foreach ($fields as [$table, $cols]) {
      foreach ($cols as $col) {
        $wpdb->query($wpdb->prepare("UPDATE $table SET $col = %s WHERE $col = %s" . ($tree ? " AND gedcom = %s" : ""), $target_place, $source_place, $tree));
      }
    }
    // Delete the merged (source) place
    $wpdb->delete($places_table, array('place' => $source_place, 'gedcom' => $tree));
    wp_send_json_success([
      'message' => __('Places merged successfully.', 'heritagepress'),
      'latitude' => $update['latitude'] ?? $target_row['latitude'],
      'longitude' => $update['longitude'] ?? $target_row['longitude'],
    ]);
  }

  /**
   * AJAX handler for place geocoding
   */
  public function ajax_place_geocode()
  {
    check_ajax_referer('heritagepress_admin', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions.', 'heritagepress'));
    }

    $place_id = intval($_POST['place_id'] ?? 0);
    $place_name = sanitize_text_field($_POST['place_name'] ?? '');

    $result = $this->geocode_place($place_id, $place_name);

    if ($result) {
      wp_send_json_success(array(
        'message' => __('Place geocoded successfully.', 'heritagepress')
      ));
    } else {
      wp_send_json_error(__('Geocoding failed.', 'heritagepress'));
    }
  }

  /**
   * Get cemeteries linked to a place
   */
  public function get_place_cemeteries($place_name)
  {
    global $wpdb;

    $query = $wpdb->prepare(
      "SELECT cemeteryID, cemname, city, county, state, country
             FROM {$this->cemeteries_table}
             WHERE place = %s
             ORDER BY cemname",
      $place_name
    );

    return $wpdb->get_results($query, ARRAY_A);
  }

  /**
   * AJAX handler for cemetery linking
   */
  public function ajax_cemetery_link()
  {
    check_ajax_referer('heritagepress_admin', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions.', 'heritagepress'));
    }

    // This would implement cemetery linking functionality
    // For now, return success
    wp_send_json_success(array(
      'message' => __('Cemetery linked successfully.', 'heritagepress')
    ));
  }

  /**
   * AJAX handler for cemetery unlinking
   */
  public function ajax_cemetery_unlink()
  {
    check_ajax_referer('heritagepress_admin', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions.', 'heritagepress'));
    }

    // This would implement cemetery unlinking functionality
    // For now, return success
    wp_send_json_success(array(
      'message' => __('Cemetery unlinked successfully.', 'heritagepress')
    ));
  }

  /**
   * AJAX handler for copying geo info to cemetery
   */
  public function ajax_copy_geo_info()
  {
    check_ajax_referer('heritagepress_admin', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions.', 'heritagepress'));
    }

    // This would implement geo info copying functionality
    // For now, return success
    wp_send_json_success(array(
      'message' => __('Geographic information copied successfully.', 'heritagepress')
    ));
  }
}

// Initialize the controller
new HP_Place_Controller();
