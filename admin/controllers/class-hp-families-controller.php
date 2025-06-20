<?php

/**
 * Families Controller
 *
 * Handles all admin functionality for managing families in HeritagePress.
 * Provides CRUD, search, filter, and paging for families, with full UI/UX parity to HeritagePress.
 */

if (!defined('ABSPATH')) {
  exit;
}

require_once dirname(__FILE__) . '/../../includes/controllers/class-hp-base-controller.php';

if (!function_exists('sanitize_text_field')) {
  function sanitize_text_field($str)
  {
    return is_string($str) ? trim(strip_tags($str)) : '';
  }
}

if (!defined('ARRAY_A')) {
  define('ARRAY_A', 'ARRAY_A');
}

class HP_Families_Controller extends HP_Base_Controller
{
  public function __construct()
  {
    parent::__construct('families');
    $this->capabilities = array(
      'manage_families' => 'manage_genealogy',
      'edit_families' => 'edit_genealogy',
      'delete_families' => 'delete_genealogy'
    );
  }

  public function display_page()
  {
    // Handle actions (add, update, delete, search, filter, paging)
    $this->handle_families_actions();
    // Show add or edit form if requested
    if (isset($_GET['action']) && $_GET['action'] === 'add') {
      include dirname(__FILE__) . '/../views/families/add-family.php';
      return;
    }
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && !empty($_GET['family_id']) && !empty($_GET['gedcom'])) {
      $family = $this->get_family(sanitize_text_field($_GET['family_id']), sanitize_text_field($_GET['gedcom']));
      if ($family) {
        // Make $family available to the view
        global $family;
      }
      include dirname(__FILE__) . '/../views/families/edit-family.php';
      return;
    }
    include dirname(__FILE__) . '/../views/families/browse-families.php';
  }

  private function handle_families_actions()
  {
    if (!empty($_POST['action'])) {
      switch ($_POST['action']) {
        case 'add_family':
          $this->handle_add_family();
          break;
        case 'update_family':
          $this->handle_update_family();
          break;
        case 'delete_family':
          $this->handle_delete_family();
          break;
        case 'bulk_action':
          $this->handle_bulk_families_actions();
          break;
        case 'add_child':
          $this->handle_add_child();
          break;
        case 'remove_child':
          $this->handle_remove_child();
          break;
        case 'add_citation':
          $this->handle_add_citation();
          break;
        case 'remove_citation':
          $this->handle_remove_citation();
          break;
        case 'add_event':
          $this->handle_add_event();
          break;
        case 'remove_event':
          $this->handle_remove_event();
          break;
      }
    }
  }

  /**
   * Insert a pending family change for review
   */
  private function insert_pending_family($family_data, $action = 'add')
  {
    global $wpdb;
    $temp_table = $wpdb->prefix . 'hp_temp_events';
    $current_user = function_exists('wp_get_current_user') ? wp_get_current_user() : null;
    $pending_data = array(
      'type' => 'F',
      'familyID' => $family_data['familyID'],
      'gedcom' => $family_data['gedcom'],
      'eventID' => '',
      'eventstr' => maybe_serialize($family_data),
      'postdate' => current_time('mysql'),
      'user' => $current_user ? $current_user->user_login : '',
      'branch' => $family_data['branch'] ?? '',
      'action' => $action,
    );
    $wpdb->insert($temp_table, $pending_data);
    return $wpdb->insert_id;
  }

  private function is_review_required()
  {
    // You can enhance this logic to check plugin settings or user role
    return !function_exists('current_user_can') || !current_user_can('manage_options');
  }

  private function handle_add_family()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }
    $family_data = $this->sanitize_family_data($_POST);
    if (empty($family_data['familyID']) || empty($family_data['gedcom'])) {
      $this->add_notice(__('Family ID and Tree are required.', 'heritagepress'), 'error');
      return;
    }
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $family_data['familyID'])) {
      $this->add_notice(__('Family ID must be alphanumeric with underscores and hyphens only.', 'heritagepress'), 'error');
      return;
    }
    if ($this->family_id_exists($family_data['familyID'], $family_data['gedcom'])) {
      $this->add_notice(__('Family ID already exists in this tree. Please choose a different ID.', 'heritagepress'), 'error');
      return;
    }
    if ($this->is_review_required()) {
      $this->insert_pending_family($family_data, 'add');
      $this->add_notice(__('Family submitted for review. An admin must approve this change.', 'heritagepress'), 'success');
    } else {
      $result = $this->create_family($family_data);
      if ($result) {
        $this->add_notice(__('Family created successfully!', 'heritagepress'), 'success');
      } else {
        $this->add_notice(__('Failed to create family. Please try again.', 'heritagepress'), 'error');
      }
    }
  }

  private function handle_update_family()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }
    $family_id = sanitize_text_field($_POST['familyID']);
    $gedcom = sanitize_text_field($_POST['gedcom']);
    $family_data = $this->sanitize_family_data($_POST);
    if ($this->is_review_required()) {
      $family_data['familyID'] = $family_id;
      $family_data['gedcom'] = $gedcom;
      $this->insert_pending_family($family_data, 'update');
      $this->add_notice(__('Family update submitted for review. An admin must approve this change.', 'heritagepress'), 'success');
    } else {
      $result = $this->update_family($family_id, $gedcom, $family_data);
      if ($result) {
        $this->add_notice(__('Family updated successfully!', 'heritagepress'), 'success');
      } else {
        $this->add_notice(__('Failed to update family. Please try again.', 'heritagepress'), 'error');
      }
    }
  }

  private function handle_delete_family()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }
    if (!$this->check_capability('delete_genealogy')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }
    $family_id = sanitize_text_field($_POST['familyID']);
    $gedcom = sanitize_text_field($_POST['gedcom']);
    $result = $this->delete_family($family_id, $gedcom);
    if ($result) {
      $this->add_notice(__('Family deleted successfully!', 'heritagepress'), 'success');
    } else {
      $this->add_notice(__('Failed to delete family. Please try again.', 'heritagepress'), 'error');
    }
  }

  private function handle_bulk_families_actions()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }
    $action = sanitize_text_field($_POST['bulk_action']);
    $family_ids = array_map('sanitize_text_field', $_POST['family_ids']);
    $gedcom = sanitize_text_field($_POST['gedcom']);
    if (empty($family_ids)) {
      $this->add_notice(__('No families selected.', 'heritagepress'), 'error');
      return;
    }
    switch ($action) {
      case 'delete':
        $callback = function ($family_id) use ($gedcom) {
          return $this->delete_family($family_id, $gedcom);
        };
        $this->handle_bulk_action($action, $family_ids, $callback);
        break;
      default:
        $this->add_notice(__('Invalid bulk action.', 'heritagepress'), 'error');
    }
  }

  private function handle_add_child()
  {
    global $wpdb;
    $family_id = sanitize_text_field($_POST['family_id']);
    $gedcom = sanitize_text_field($_POST['gedcom']);
    $person_id = sanitize_text_field($_POST['person_id']);
    $frel = sanitize_text_field($_POST['frel'] ?? '');
    $mrel = sanitize_text_field($_POST['mrel'] ?? '');
    $children_table = $wpdb->prefix . 'hp_children';
    // Get current number of children for ordering
    $order = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $children_table WHERE familyID = %s AND gedcom = %s", $family_id, $gedcom)) + 1;
    $child_data = array(
      'familyID' => $family_id,
      'personID' => $person_id,
      'ordernum' => $order,
      'gedcom' => $gedcom,
      'frel' => $frel,
      'mrel' => $mrel,
      'haskids' => 0,
      'parentorder' => 0,
      'sealdate' => '',
      'sealdatetr' => '0000-00-00',
      'sealplace' => ''
    );
    $wpdb->insert($children_table, $child_data);
    $this->add_notice(__('Child added to family.', 'heritagepress'), 'success');
  }

  private function handle_remove_child()
  {
    global $wpdb;
    $family_id = sanitize_text_field($_POST['family_id']);
    $gedcom = sanitize_text_field($_POST['gedcom']);
    $person_id = sanitize_text_field($_POST['person_id']);
    $children_table = $wpdb->prefix . 'hp_children';
    $wpdb->delete($children_table, array('familyID' => $family_id, 'gedcom' => $gedcom, 'personID' => $person_id));
    $this->add_notice(__('Child removed from family.', 'heritagepress'), 'success');
  }

  private function handle_add_citation()
  {
    if (!class_exists('HP_Citation_Controller')) {
      require_once dirname(__FILE__) . '/class-hp-citation-controller.php';
    }
    $citation_controller = new HP_Citation_Controller();
    // Prepare POST data for citation controller
    $_POST['persfamID'] = sanitize_text_field($_POST['family_id']);
    $_POST['sourceID'] = sanitize_text_field($_POST['source_id']);
    $_POST['description'] = sanitize_text_field($_POST['description']);
    $_POST['citepage'] = sanitize_text_field($_POST['page']);
    // Skipping nonce for internal controller call; user-facing forms should use real WordPress nonces
    $citation_controller->handle_add_citation();
    $this->add_notice(__('Citation added to family.', 'heritagepress'), 'success');
  }

  private function handle_remove_citation()
  {
    if (!class_exists('HP_Citation_Controller')) {
      require_once dirname(__FILE__) . '/class-hp-citation-controller.php';
    }
    $citation_controller = new HP_Citation_Controller();
    $_POST['citationID'] = intval($_POST['citation_id']);
    // Skipping nonce for internal controller call; user-facing forms should use real WordPress nonces
    $citation_controller->handle_delete_citation();
    $this->add_notice(__('Citation removed from family.', 'heritagepress'), 'success');
  }

  private function handle_add_event()
  {
    if (!class_exists('HP_Event_Controller')) {
      require_once dirname(__FILE__) . '/class-hp-event-controller.php';
    }
    $event_controller = new HP_Event_Controller();
    global $wpdb;
    $family_id = sanitize_text_field($_POST['family_id']);
    $gedcom = sanitize_text_field($_POST['gedcom']);
    $event_type = sanitize_text_field($_POST['event_type']);
    $event_date = sanitize_text_field($_POST['event_date']);
    $event_place = sanitize_text_field($_POST['event_place']);
    $event_info = sanitize_text_field($_POST['event_info']);
    // Find eventtypeID by tag or display
    $eventtypes_table = $wpdb->prefix . 'hp_eventtypes';
    $eventtype = $wpdb->get_row($wpdb->prepare("SELECT eventtypeID FROM $eventtypes_table WHERE tag = %s OR display = %s LIMIT 1", $event_type, $event_type), ARRAY_A);
    $eventtypeID = $eventtype['eventtypeID'] ?? null;
    if (!$eventtypeID) {
      $this->add_notice(__('Event type not found.', 'heritagepress'), 'error');
      return;
    }
    $events_table = $wpdb->prefix . 'hp_events';
    $wpdb->insert($events_table, array(
      'gedcom' => $gedcom,
      'persfamID' => $family_id,
      'eventtypeID' => $eventtypeID,
      'eventdate' => $event_date,
      'eventplace' => $event_place,
      'info' => $event_info
    ));
    $this->add_notice(__('Event added to family.', 'heritagepress'), 'success');
  }

  private function handle_remove_event()
  {
    if (!class_exists('HP_Event_Controller')) {
      require_once dirname(__FILE__) . '/class-hp-event-controller.php';
    }
    $event_controller = new HP_Event_Controller();
    global $wpdb;
    $event_id = intval($_POST['event_id']);
    $events_table = $wpdb->prefix . 'hp_events';
    $wpdb->delete($events_table, array('eventID' => $event_id));
    $this->add_notice(__('Event removed from family.', 'heritagepress'), 'success');
  }

  private function sanitize_family_data($data)
  {
    return $this->sanitize_form_data($data, array(
      'familyID' => 'text',
      'gedcom' => 'text',
      'husband' => 'text',
      'wife' => 'text',
      'marrdate' => 'text',
      'marrplace' => 'text',
      'divdate' => 'text',
      'divplace' => 'text',
      'living' => 'int',
      'private' => 'int',
      'branch' => 'text',
      'notes' => 'textarea'
    ));
  }

  private function family_id_exists($family_id, $gedcom)
  {
    global $wpdb;
    $families_table = $wpdb->prefix . 'hp_families';
    return (bool) $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $families_table WHERE familyID = %s AND gedcom = %s",
      $family_id,
      $gedcom
    ));
  }

  private function create_family($family_data)
  {
    global $wpdb;
    $families_table = $wpdb->prefix . 'hp_families';
    $result = $wpdb->insert($families_table, $family_data);
    return $result !== false;
  }

  private function update_family($family_id, $gedcom, $family_data)
  {
    global $wpdb;
    $families_table = $wpdb->prefix . 'hp_families';
    $result = $wpdb->update($families_table, $family_data, array('familyID' => $family_id, 'gedcom' => $gedcom));
    return $result !== false;
  }

  private function delete_family($family_id, $gedcom)
  {
    global $wpdb;
    $families_table = $wpdb->prefix . 'hp_families';
    $result = $wpdb->delete($families_table, array('familyID' => $family_id, 'gedcom' => $gedcom));
    return $result !== false;
  }

  public function get_families($args = array())
  {
    global $wpdb;
    $families_table = $wpdb->prefix . 'hp_families';
    $people_table = $wpdb->prefix . 'hp_people';
    $defaults = array(
      'search' => '',
      'tree' => '',
      'living' => '',
      'exactmatch' => false,
      'spousename' => '',
      'order' => 'familyID',
      'orderby' => 'ASC',
      'paged' => 1,
      'per_page' => 20
    );
    $args = array_merge($defaults, $args);
    $where = array();
    $params = array();
    if ($args['tree']) {
      $where[] = "$families_table.gedcom = %s";
      $params[] = $args['tree'];
    }
    if ($args['search']) {
      $search = $args['search'];
      if ($args['exactmatch']) {
        $where[] = "(familyID = %s OR husband = %s OR wife = %s)";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
      } else {
        $like = '%' . $wpdb->esc_like($search) . '%';
        $where[] = "(familyID LIKE %s OR husband LIKE %s OR wife LIKE %s)";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
      }
    }
    if ($args['living'] !== '') {
      $where[] = "$families_table.living = %d";
      $params[] = (int)$args['living'];
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $order_sql = in_array($args['order'], ['familyID', 'marrdate', 'husband', 'wife']) ? $args['order'] : 'familyID';
    $orderby_sql = strtoupper($args['orderby']) === 'DESC' ? 'DESC' : 'ASC';
    $offset = ($args['paged'] - 1) * $args['per_page'];
    $sql = "SELECT SQL_CALC_FOUND_ROWS $families_table.*,
                h.firstname AS husband_firstname, h.lastname AS husband_lastname,
                w.firstname AS wife_firstname, w.lastname AS wife_lastname
            FROM $families_table
            LEFT JOIN $people_table h ON $families_table.husband = h.personID AND $families_table.gedcom = h.gedcom
            LEFT JOIN $people_table w ON $families_table.wife = w.personID AND $families_table.gedcom = w.gedcom
            $where_sql
            ORDER BY $order_sql $orderby_sql
            LIMIT %d OFFSET %d";
    $params[] = (int)$args['per_page'];
    $params[] = (int)$offset;
    $families = $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);
    $total = (int)$wpdb->get_var('SELECT FOUND_ROWS()');
    return array('families' => $families, 'total' => $total);
  }

  /**
   * Get a single family by ID and gedcom (tree)
   */
  public function get_family($family_id, $gedcom)
  {
    global $wpdb;
    $table = $this->get_table_name();
    $sql = $wpdb->prepare("SELECT * FROM $table WHERE familyID = %s AND gedcom = %s LIMIT 1", $family_id, $gedcom);
    $family = $wpdb->get_row($sql, ARRAY_A);
    return $family;
  }

  /**
   * Get the database table name for families
   */
  public function get_table_name()
  {
    global $wpdb;
    return $wpdb->prefix . 'hp_families';
  }

  /**
   * Get children for a family (by familyID and gedcom)
   */
  public function get_family_children($family_id, $gedcom)
  {
    global $wpdb;
    $children_table = $wpdb->prefix . 'hp_children';
    $people_table = $wpdb->prefix . 'hp_people';
    $sql = $wpdb->prepare("SELECT c.*, p.firstname, p.lastname, p.birthdate FROM $children_table c LEFT JOIN $people_table p ON c.personID = p.personID AND c.gedcom = p.gedcom WHERE c.familyID = %s AND c.gedcom = %s ORDER BY c.ordernum ASC", $family_id, $gedcom);
    return $wpdb->get_results($sql, ARRAY_A);
  }

  /**
   * AJAX handler to update child order for a family
   */
  public function ajax_update_child_order()
  {
    global $wpdb;
    // Polyfill for nonce check
    if (!function_exists('wp_verify_nonce')) {
      function wp_verify_nonce($nonce, $action = '')
      {
        // Simple check: match the polyfill in wp_create_nonce
        return $nonce === md5($action . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'guest') . date('Ymd'));
      }
    }
    $nonce = $_POST['nonce'] ?? '';
    $family_id = sanitize_text_field($_POST['family_id'] ?? '');
    $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');
    $order = isset($_POST['order']) && is_array($_POST['order']) ? $_POST['order'] : array();
    if (!$family_id || !$gedcom || empty($order)) {
      $this->send_json(array('success' => false, 'data' => 'Missing data.'));
    }
    if (!wp_verify_nonce($nonce, 'hp_update_child_order')) {
      $this->send_json(array('success' => false, 'data' => 'Security check failed.'));
    }
    $children_table = $wpdb->prefix . 'hp_children';
    foreach ($order as $i => $person_id) {
      $wpdb->update($children_table, array('ordernum' => $i + 1), array('familyID' => $family_id, 'gedcom' => $gedcom, 'personID' => $person_id));
    }
    $this->send_json(array('success' => true));
  }

  /**
   * AJAX handler to update 'Living' or 'Private' field for a family
   */
  public function ajax_update_family_privacy()
  {
    global $wpdb;
    if (!function_exists('wp_verify_nonce')) {
      function wp_verify_nonce($nonce, $action = '')
      {
        return $nonce === md5($action . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'guest') . date('Ymd'));
      }
    }
    $nonce = $_POST['nonce'] ?? '';
    $family_id = sanitize_text_field($_POST['family_id'] ?? '');
    $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');
    $field = $_POST['field'] ?? '';
    $value = isset($_POST['value']) ? (int)$_POST['value'] : 0;
    if (!$family_id || !$gedcom || !in_array($field, ['living', 'private'])) {
      $this->send_json(['success' => false, 'data' => 'Missing or invalid data.']);
    }
    if (!wp_verify_nonce($nonce, 'hp_update_family_privacy')) {
      $this->send_json(['success' => false, 'data' => 'Security check failed.']);
    }
    $families_table = $wpdb->prefix . 'hp_families';
    $result = $wpdb->update($families_table, [$field => $value], ['familyID' => $family_id, 'gedcom' => $gedcom]);
    if ($result !== false) {
      $this->send_json(['success' => true]);
    } else {
      $this->send_json(['success' => false, 'data' => 'Failed to update.']);
    }
  }

  /**
   * AJAX handler to get updated children table HTML
   */
  public function ajax_get_children_table()
  {
    $family_id = sanitize_text_field($_POST['family_id'] ?? '');
    $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');
    if (!$family_id || !$gedcom) {
      $this->send_json(['success' => false, 'data' => 'Missing data.']);
    }
    $children = $this->get_family_children($family_id, $gedcom);
    ob_start();
    include dirname(__FILE__) . '/../views/families/children-table-ajax.php';
    $html = ob_get_clean();
    $this->send_json(['success' => true, 'html' => $html]);
  }

  /**
   * Generate a unique family ID
   */
  public function generate_family_id($gedcom, $base_name = '')
  {
    global $wpdb;
    $families_table = $wpdb->prefix . 'hp_families';

    if (empty($base_name)) {
      $base_name = 'F';
    }

    // Get the highest existing ID number for this pattern
    $pattern = $base_name . '%';
    $existing_ids = $wpdb->get_col($wpdb->prepare(
      "SELECT familyID FROM $families_table WHERE gedcom = %s AND familyID LIKE %s ORDER BY familyID",
      $gedcom,
      $pattern
    ));

    $highest_num = 0;
    foreach ($existing_ids as $id) {
      if (preg_match('/(\d+)$/', $id, $matches)) {
        $num = intval($matches[1]);
        if ($num > $highest_num) {
          $highest_num = $num;
        }
      }
    }

    return $base_name . ($highest_num + 1);
  }

  /**
   * AJAX: Generate family ID
   */
  public function ajax_generate_family_id()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    $gedcom = sanitize_text_field($_POST['gedcom']);
    $base_name = sanitize_text_field($_POST['base_name'] ?? '');

    $family_id = $this->generate_family_id($gedcom, $base_name);

    wp_send_json_success(array('family_id' => $family_id));
  }

  // Register AJAX handler for generating family ID
  // (This should be called in the constructor or on load)
  public static function register_ajax_handlers()
  {
    add_action('wp_ajax_hp_generate_family_id', array('HP_Families_Controller', 'ajax_generate_family_id_static'));
  }

  // Static wrapper for AJAX (for use with add_action)
  public static function ajax_generate_family_id_static()
  {
    $controller = new self();
    $controller->ajax_generate_family_id();
  }
}

// Register AJAX handler for updating child order (drag-and-drop)
if (defined('DOING_AJAX') && DOING_AJAX && isset($_POST['action']) && $_POST['action'] === 'hp_update_child_order') {
  $hp_families_controller = new HP_Families_Controller();
  add_action('wp_ajax_hp_update_child_order', array($hp_families_controller, 'ajax_update_child_order'));
}
// Register AJAX handler for generating family ID
if (defined('DOING_AJAX') && DOING_AJAX && isset($_POST['action']) && $_POST['action'] === 'hp_generate_family_id') {
  HP_Families_Controller::register_ajax_handlers();
}
