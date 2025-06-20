<?php

/**
 * People Controller
 *
 * Handles all people management functionality including CRUD operations,
 * person validation, and people-related AJAX requests
 */

if (!defined('ABSPATH')) {
  exit;
}

require_once plugin_dir_path(__FILE__) . 'class-hp-base-controller.php';

class HP_People_Controller extends HP_Base_Controller
{
  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct('people');
    $this->capabilities = array(
      'manage_people' => 'manage_genealogy',
      'edit_people' => 'edit_genealogy',
      'delete_people' => 'delete_genealogy'
    );
  }

  /**
   * Initialize the people controller
   */
  public function init()
  {
    parent::init();
    // Load date system for people management
    $this->load_date_helpers();
  }

  /**
   * Load date parsing helpers
   */
  private function load_date_helpers()
  {
    require_once plugin_dir_path(__FILE__) . '../../includes/helpers/class-hp-date-parser.php';
    require_once plugin_dir_path(__FILE__) . '../../includes/helpers/class-hp-date-validator.php';
  }

  /**
   * Register hooks for people management
   */
  public function register_hooks()
  {
    parent::register_hooks();

    // AJAX handlers for people
    add_action('wp_ajax_hp_add_person', array($this, 'ajax_add_person'));
    add_action('wp_ajax_hp_add_person_modal', array($this, 'ajax_add_person_modal'));
    add_action('wp_ajax_hp_update_person', array($this, 'ajax_update_person'));
    add_action('wp_ajax_hp_delete_person', array($this, 'ajax_delete_person'));
    add_action('wp_ajax_hp_generate_person_id', array($this, 'ajax_generate_person_id'));
    add_action('wp_ajax_hp_check_person_id', array($this, 'ajax_check_person_id'));
    add_action('wp_ajax_hp_search_people', array($this, 'ajax_search_people'));

    // AJAX handlers for associations
    add_action('wp_ajax_hp_add_association', array($this, 'ajax_add_association'));
    add_action('wp_ajax_hp_delete_association', array($this, 'ajax_delete_association'));
    add_action('wp_ajax_hp_get_person_associations', array($this, 'ajax_get_person_associations'));

    // AJAX handler for people utilities
    add_action('wp_ajax_hp_run_people_utility', array($this, 'ajax_run_people_utility'));

    // Enqueue scripts for the person edit screen
    add_action('admin_enqueue_scripts', array($this, 'enqueue_edit_person_scripts'));
  }

  /**
   * Handle people page actions
   */
  public function handle_people_actions($tab)
  {
    if (!$this->check_capability('edit_genealogy')) {
      return;
    }

    // Handle form submissions
    if (isset($_POST['action'])) {
      switch ($_POST['action']) {
        case 'add_person':
          $this->handle_add_person();
          break;
        case 'update_person':
          $this->handle_update_person();
          break;
        case 'delete_person':
          $this->handle_delete_person();
          break;
        case 'bulk_action':
          $this->handle_bulk_people_actions();
          break;
      }
    }
  }

  /**
   * Insert a pending person change for review
   */
  private function insert_pending_person($person_data, $action = 'add')
  {
    global $wpdb;
    $temp_table = $wpdb->prefix . 'hp_temp_events';
    $current_user = wp_get_current_user();
    $pending_data = array(
      'type' => 'I',
      'personID' => $person_data['personID'],
      'gedcom' => $person_data['gedcom'],
      'eventID' => '',
      'eventstr' => maybe_serialize($person_data),
      'postdate' => current_time('mysql'),
      'user' => $current_user ? $current_user->user_login : '',
      'branch' => $person_data['branch'] ?? '',
      'action' => $action,
    );
    $wpdb->insert($temp_table, $pending_data);
    return $wpdb->insert_id;
  }

  private function is_review_required()
  {
    // You can enhance this logic to check plugin settings or user role
    return !current_user_can('manage_options');
  }

  /**
   * Handle adding a new person
   */
  private function handle_add_person()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    // Sanitize form data
    $person_data = $this->sanitize_person_data($_POST);

    // Validate required fields
    if (empty($person_data['personID']) || empty($person_data['gedcom'])) {
      $this->add_notice(__('Person ID and Tree are required.', 'heritagepress'), 'error');
      return;
    }

    // Validate person ID format
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $person_data['personID'])) {
      $this->add_notice(__('Person ID must be alphanumeric with underscores and hyphens only.', 'heritagepress'), 'error');
      return;
    }

    // Check if person ID already exists in this tree
    if ($this->person_id_exists($person_data['personID'], $person_data['gedcom'])) {
      $this->add_notice(__('Person ID already exists in this tree. Please choose a different ID.', 'heritagepress'), 'error');
      return;
    }

    // Parse and validate dates
    $person_data = $this->parse_person_dates($person_data);

    if ($this->is_review_required()) {
      $this->insert_pending_person($person_data, 'add');
      $this->add_notice(__('Person submitted for review. An admin must approve this change.', 'heritagepress'), 'success');
    } else {
      $result = $this->create_person($person_data);
      if ($result) {
        $this->add_notice(__('Person created successfully!', 'heritagepress'), 'success');
      } else {
        $this->add_notice(__('Failed to create person. Please try again.', 'heritagepress'), 'error');
      }
    }
  }

  /**
   * Handle updating an existing person
   */
  private function handle_update_person()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    $person_id = sanitize_text_field($_POST['personID']);
    $gedcom = sanitize_text_field($_POST['gedcom']);

    // Sanitize form data
    $person_data = $this->sanitize_person_data($_POST);

    // Parse and validate dates
    $person_data = $this->parse_person_dates($person_data);

    if ($this->is_review_required()) {
      $person_data['personID'] = $person_id;
      $person_data['gedcom'] = $gedcom;
      $this->insert_pending_person($person_data, 'update');
      $this->add_notice(__('Person update submitted for review. An admin must approve this change.', 'heritagepress'), 'success');
    } else {
      $result = $this->update_person($person_id, $gedcom, $person_data);
      if ($result) {
        $this->add_notice(__('Person updated successfully!', 'heritagepress'), 'success');
      } else {
        $this->add_notice(__('Failed to update person. Please try again.', 'heritagepress'), 'error');
      }
    }
  }

  /**
   * Handle deleting a person
   */
  private function handle_delete_person()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!$this->check_capability('delete_genealogy')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    $person_id = sanitize_text_field($_POST['personID']);
    $gedcom = sanitize_text_field($_POST['gedcom']);

    $result = $this->delete_person($person_id, $gedcom);

    if ($result) {
      $this->add_notice(__('Person deleted successfully!', 'heritagepress'), 'success');
    } else {
      $this->add_notice(__('Failed to delete person. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle bulk people actions
   */
  private function handle_bulk_people_actions()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    $action = sanitize_text_field($_POST['bulk_action']);
    $person_ids = array_map('sanitize_text_field', $_POST['person_ids']);
    $gedcom = sanitize_text_field($_POST['gedcom']);

    if (empty($person_ids)) {
      $this->add_notice(__('No people selected.', 'heritagepress'), 'error');
      return;
    }

    switch ($action) {
      case 'delete':
        $callback = function ($person_id) use ($gedcom) {
          return $this->delete_person($person_id, $gedcom);
        };
        $this->handle_bulk_action($action, $person_ids, $callback);
        break;
      default:
        $this->add_notice(__('Invalid bulk action.', 'heritagepress'), 'error');
    }
  }
  /**
   * Sanitize person form data
   */
  private function sanitize_person_data($data)
  {
    return $this->sanitize_form_data($data, array(
      'personID' => 'text',
      'gedcom' => 'text',
      'firstname' => 'text',
      'lastname' => 'text',
      'lnprefix' => 'text',
      'middlename' => 'text',
      'prefix' => 'text',
      'suffix' => 'text',
      'title' => 'text',
      'nickname' => 'text',
      'nameorder' => 'text',
      'gender' => 'text',
      'sex' => 'text', // Uses 'sex' field name
      'birthdate' => 'text',
      'birthplace' => 'text',
      'altbirthtype' => 'text',
      'altbirthdate' => 'text',
      'altbirthplace' => 'text',
      'deathdate' => 'text',
      'deathplace' => 'text',
      'burialdate' => 'text',
      'burialplace' => 'text',
      'burialtype' => 'int',
      'baptdate' => 'text',
      'baptplace' => 'text',
      'confdate' => 'text',
      'confplace' => 'text',
      'initdate' => 'text',
      'initplace' => 'text',
      'endldate' => 'text',
      'endlplace' => 'text',
      'famc' => 'text',
      'branch' => 'text',
      'notes' => 'textarea',
      'private' => 'int',
      'living' => 'int'
    ));
  }
  /**
   * Parse and validate person dates
   */
  private function parse_person_dates($person_data)
  {
    $date_fields = array('birthdate', 'altbirthdate', 'deathdate', 'burialdate', 'baptdate', 'confdate', 'initdate', 'endldate');

    foreach ($date_fields as $field) {
      if (!empty($person_data[$field])) {
        // Use HP_Date_Parser to parse the date
        $parsed_date = HP_Date_Parser::parse($person_data[$field]);
        if ($parsed_date) {
          $person_data[$field] = $parsed_date;
        }
      }
    }

    return $person_data;
  }

  /**
   * Check if person ID exists in tree
   */
  private function person_id_exists($person_id, $gedcom)
  {
    global $wpdb;
    $people_table = $wpdb->prefix . 'hp_people';

    $count = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $people_table WHERE personID = %s AND gedcom = %s",
      $person_id,
      $gedcom
    ));

    return $count > 0;
  }
  /**
   * Create a new person
   */
  private function create_person($person_data)
  {
    global $wpdb;
    $people_table = $wpdb->prefix . 'hp_people';

    // Handle sex/gender field mapping
    if (isset($person_data['gender']) && !isset($person_data['sex'])) {
      $person_data['sex'] = $person_data['gender'];
    }

    // Generate metaphone for lastname with prefix
    $lastname_with_prefix = trim(($person_data['lnprefix'] ?? '') . ' ' . ($person_data['lastname'] ?? ''));
    $metaphone = metaphone($lastname_with_prefix);

    // Handle branches
    $branches = '';
    if (isset($person_data['branch'])) {
      if (is_array($person_data['branch'])) {
        $branches = implode(',', array_filter($person_data['branch']));
      } else {
        $branches = $person_data['branch'];
      }
    }

    // Prepare data for insertion - include all genealogy fields
    $insert_data = array(
      'personID' => $person_data['personID'],
      'gedcom' => $person_data['gedcom'],
      'firstname' => $person_data['firstname'] ?? '',
      'lastname' => $person_data['lastname'] ?? '',
      'lnprefix' => $person_data['lnprefix'] ?? '',
      'middlename' => $person_data['middlename'] ?? '',
      'prefix' => $person_data['prefix'] ?? '',
      'suffix' => $person_data['suffix'] ?? '',
      'title' => $person_data['title'] ?? '',
      'nickname' => $person_data['nickname'] ?? '',
      'nameorder' => $person_data['nameorder'] ?? '',
      'sex' => $person_data['sex'] ?? '',
      'birthdate' => $person_data['birthdate'] ?? '',
      'birthdatetr' => '0000-00-00', // Will be calculated from birthdate
      'birthplace' => $person_data['birthplace'] ?? '',
      'altbirthtype' => $person_data['altbirthtype'] ?? '',
      'altbirthdate' => $person_data['altbirthdate'] ?? '',
      'altbirthdatetr' => '0000-00-00', // Will be calculated from altbirthdate
      'altbirthplace' => $person_data['altbirthplace'] ?? '',
      'deathdate' => $person_data['deathdate'] ?? '',
      'deathdatetr' => '0000-00-00', // Will be calculated from deathdate
      'deathplace' => $person_data['deathplace'] ?? '',
      'burialdate' => $person_data['burialdate'] ?? '',
      'burialdatetr' => '0000-00-00', // Will be calculated from burialdate
      'burialplace' => $person_data['burialplace'] ?? '',
      'burialtype' => $person_data['burialtype'] ?? 0,
      'baptdate' => $person_data['baptdate'] ?? '',
      'baptdatetr' => '0000-00-00', // Will be calculated from baptdate
      'baptplace' => $person_data['baptplace'] ?? '',
      'confdate' => $person_data['confdate'] ?? '',
      'confdatetr' => '0000-00-00', // Will be calculated from confdate
      'confplace' => $person_data['confplace'] ?? '',
      'initdate' => $person_data['initdate'] ?? '',
      'initdatetr' => '0000-00-00', // Will be calculated from initdate
      'initplace' => $person_data['initplace'] ?? '',
      'endldate' => $person_data['endldate'] ?? '',
      'endldatetr' => '0000-00-00', // Will be calculated from endldate
      'endlplace' => $person_data['endlplace'] ?? '',
      'famc' => $person_data['famc'] ?? '',
      'metaphone' => $metaphone,
      'branch' => $branches,
      'notes' => $person_data['notes'] ?? '',
      'private' => $person_data['private'] ?? 0,
      'living' => $person_data['living'] ?? 0,
      'changedate' => current_time('mysql'),
      'changedby' => get_current_user_id(),
      'edituser' => '',
      'edittime' => 0
    );

    $result = $wpdb->insert($people_table, $insert_data);
    if ($result !== false) {
      // Log admin action
      $person_id = $person_data['personID'];
      $gedcom = $person_data['gedcom'];
      $this->log_admin_action("Add person: $gedcom/$person_id");
    }

    return $result !== false;
  }
  /**
   * Update an existing person
   */
  private function update_person($person_id, $gedcom, $person_data)
  {
    global $wpdb;
    $people_table = $wpdb->prefix . 'hp_people';

    // Handle sex/gender field mapping
    if (isset($person_data['gender']) && !isset($person_data['sex'])) {
      $person_data['sex'] = $person_data['gender'];
    }

    // Generate metaphone for lastname with prefix
    $lastname_with_prefix = trim(($person_data['lnprefix'] ?? '') . ' ' . ($person_data['lastname'] ?? ''));
    $metaphone = metaphone($lastname_with_prefix);

    // Handle branches
    $branches = '';
    if (isset($person_data['branch'])) {
      if (is_array($person_data['branch'])) {
        $branches = implode(',', array_filter($person_data['branch']));
      } else {
        $branches = $person_data['branch'];
      }
    }

    // Prepare data for update - include all genealogy fields
    $update_data = array(
      'firstname' => $person_data['firstname'] ?? '',
      'lastname' => $person_data['lastname'] ?? '',
      'lnprefix' => $person_data['lnprefix'] ?? '',
      'middlename' => $person_data['middlename'] ?? '',
      'prefix' => $person_data['prefix'] ?? '',
      'suffix' => $person_data['suffix'] ?? '',
      'title' => $person_data['title'] ?? '',
      'nickname' => $person_data['nickname'] ?? '',
      'nameorder' => $person_data['nameorder'] ?? '',
      'sex' => $person_data['sex'] ?? '',
      'birthdate' => $person_data['birthdate'] ?? '',
      'birthdatetr' => '0000-00-00', // Will be calculated from birthdate
      'birthplace' => $person_data['birthplace'] ?? '',
      'altbirthtype' => $person_data['altbirthtype'] ?? '',
      'altbirthdate' => $person_data['altbirthdate'] ?? '',
      'altbirthdatetr' => '0000-00-00', // Will be calculated from altbirthdate
      'altbirthplace' => $person_data['altbirthplace'] ?? '',
      'deathdate' => $person_data['deathdate'] ?? '',
      'deathdatetr' => '0000-00-00', // Will be calculated from deathdate
      'deathplace' => $person_data['deathplace'] ?? '',
      'burialdate' => $person_data['burialdate'] ?? '',
      'burialdatetr' => '0000-00-00', // Will be calculated from burialdate
      'burialplace' => $person_data['burialplace'] ?? '',
      'burialtype' => $person_data['burialtype'] ?? 0,
      'baptdate' => $person_data['baptdate'] ?? '',
      'baptdatetr' => '0000-00-00', // Will be calculated from baptdate
      'baptplace' => $person_data['baptplace'] ?? '',
      'confdate' => $person_data['confdate'] ?? '',
      'confdatetr' => '0000-00-00', // Will be calculated from confdate
      'confplace' => $person_data['confplace'] ?? '',
      'initdate' => $person_data['initdate'] ?? '',
      'initdatetr' => '0000-00-00', // Will be calculated from initdate
      'initplace' => $person_data['initplace'] ?? '',
      'endldate' => $person_data['endldate'] ?? '',
      'endldatetr' => '0000-00-00', // Will be calculated from endldate
      'endlplace' => $person_data['endlplace'] ?? '',
      'famc' => $person_data['famc'] ?? '',
      'metaphone' => $metaphone,
      'branch' => $branches,
      'notes' => $person_data['notes'] ?? '',
      'private' => $person_data['private'] ?? 0,
      'living' => $person_data['living'] ?? 0,
      'changedate' => current_time('mysql'),
      'changedby' => get_current_user_id()
    );

    $result = $wpdb->update(
      $people_table,
      $update_data,
      array('personID' => $person_id, 'gedcom' => $gedcom)
    );
    if ($result !== false) {
      // Log admin action
      $this->log_admin_action("Update person: $gedcom/$person_id");
    }

    return $result !== false;
  }

  /**
   * Delete a person
   */
  private function delete_person($person_id, $gedcom)
  {
    global $wpdb;
    $people_table = $wpdb->prefix . 'hp_people';

    $result = $wpdb->delete(
      $people_table,
      array('personID' => $person_id, 'gedcom' => $gedcom),
      array('%s', '%s')
    );

    return $result !== false;
  }

  /**
   * Generate a unique person ID
   */
  public function generate_person_id($gedcom, $base_name = '')
  {
    global $wpdb;
    $people_table = $wpdb->prefix . 'hp_people';

    if (empty($base_name)) {
      $base_name = 'I';
    }

    // Get the highest existing ID number for this pattern
    $pattern = $base_name . '%';
    $existing_ids = $wpdb->get_col($wpdb->prepare(
      "SELECT personID FROM $people_table WHERE gedcom = %s AND personID LIKE %s ORDER BY personID",
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
   * AJAX: Generate person ID
   */
  public function ajax_generate_person_id()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    $gedcom = sanitize_text_field($_POST['gedcom']);
    $base_name = sanitize_text_field($_POST['base_name'] ?? '');

    $person_id = $this->generate_person_id($gedcom, $base_name);

    wp_send_json_success(array('person_id' => $person_id));
  }

  /**
   * AJAX: Check person ID availability
   */
  public function ajax_check_person_id()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    $person_id = sanitize_text_field($_POST['person_id']);
    $gedcom = sanitize_text_field($_POST['gedcom']);

    $exists = $this->person_id_exists($person_id, $gedcom);

    wp_send_json_success(array('exists' => $exists));
  }

  /**
   * AJAX: Add person
   */
  public function ajax_add_person()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('edit_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    // Sanitize and validate data
    $person_data = $this->sanitize_person_data($_POST);

    if (empty($person_data['personID']) || empty($person_data['gedcom'])) {
      wp_send_json_error('Person ID and Tree are required.');
    }

    if ($this->person_id_exists($person_data['personID'], $person_data['gedcom'])) {
      wp_send_json_error('Person ID already exists in this tree.');
    }

    $person_data = $this->parse_person_dates($person_data);
    $result = $this->create_person($person_data);

    if ($result) {
      wp_send_json_success('Person created successfully');
    } else {
      wp_send_json_error('Failed to create person');
    }
  }

  /**
   * AJAX: Add person from modal (for family integration)
   */
  public function ajax_add_person_modal()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('edit_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    // Sanitize and validate data
    $person_data = $this->sanitize_person_data($_POST);
    $context_type = sanitize_text_field($_POST['type'] ?? '');
    $family_id = sanitize_text_field($_POST['familyID'] ?? '');
    $father_id = sanitize_text_field($_POST['father'] ?? '');
    $mother_id = sanitize_text_field($_POST['mother'] ?? '');

    if (empty($person_data['personID']) || empty($person_data['gedcom'])) {
      wp_send_json_error('Person ID and Tree are required.');
    }

    if ($this->person_id_exists($person_data['personID'], $person_data['gedcom'])) {
      wp_send_json_error('Person ID already exists in this tree.');
    }

    $person_data = $this->parse_person_dates($person_data);
    $result = $this->create_person($person_data);

    if (!$result) {
      wp_send_json_error('Failed to create person');
    }

    // Handle family integration for children
    if ($context_type === 'child' && $family_id) {
      $this->link_child_to_family($person_data['personID'], $person_data['gedcom'], $family_id, $_POST);
    }

    // Prepare response data based on context
    $response_data = array(
      'personID' => $person_data['personID'],
      'name' => trim($person_data['firstname'] . ' ' . $person_data['lastname']),
      'context_type' => $context_type
    );

    // Add context-specific response data
    if ($context_type === 'child') {
      $response_data['html'] = $this->generate_child_html($person_data);
    } elseif ($context_type === 'spouse') {
      $response_data['display_name'] = $response_data['name'] . ' - ' . $person_data['personID'];
    }

    wp_send_json_success($response_data);
  }

  /**
   * Link a child to a family
   */
  private function link_child_to_family($person_id, $gedcom, $family_id, $form_data)
  {
    global $wpdb;

    $children_table = $wpdb->prefix . 'hp_children';
    $families_table = $wpdb->prefix . 'hp_families';

    // Get current number of children for ordering
    $order_query = $wpdb->prepare(
      "SELECT COUNT(*) FROM $children_table WHERE familyID = %s AND gedcom = %s",
      $family_id,
      $gedcom
    );
    $order = $wpdb->get_var($order_query) + 1;

    // Insert child relationship
    $child_data = array(
      'familyID' => $family_id,
      'personID' => $person_id,
      'ordernum' => $order,
      'gedcom' => $gedcom,
      'frel' => sanitize_text_field($form_data['frel'] ?? ''),
      'mrel' => sanitize_text_field($form_data['mrel'] ?? ''),
      'haskids' => 0,
      'parentorder' => 0,
      'sealdate' => '',
      'sealdatetr' => '0000-00-00',
      'sealplace' => ''
    );

    $wpdb->insert($children_table, $child_data);

    // Update parent records to show they have children
    $family_query = $wpdb->prepare(
      "SELECT husband, wife FROM $families_table WHERE familyID = %s AND gedcom = %s",
      $family_id,
      $gedcom
    );
    $family = $wpdb->get_row($family_query, ARRAY_A);

    if ($family) {
      $people_table = $wpdb->prefix . 'hp_people';

      if ($family['husband']) {
        $wpdb->update(
          $people_table,
          array('haskids' => 1),
          array('personID' => $family['husband'], 'gedcom' => $gedcom)
        );
      }

      if ($family['wife']) {
        $wpdb->update(
          $people_table,
          array('haskids' => 1),
          array('personID' => $family['wife'], 'gedcom' => $gedcom)
        );
      }
    }
  }

  /**
   * Generate HTML for child display in family context
   */
  private function generate_child_html($person_data)
  {
    $person_id = $person_data['personID'];
    $name = trim($person_data['firstname'] . ' ' . $person_data['lastname']);

    // Format birth information
    $birth_info = '';
    if (!empty($person_data['birthdate'])) {
      $birth_info = __('b.', 'heritagepress') . ' ' . $person_data['birthdate'];
    }

    $html = '<div class="child-item" id="child_' . esc_attr($person_id) . '">';
    $html .= '<div class="child-info">';
    $html .= '<span class="child-name">' . esc_html($name) . '</span>';
    $html .= '<span class="child-id"> - ' . esc_html($person_id) . '</span>';
    if ($birth_info) {
      $html .= '<br><span class="child-birth">' . esc_html($birth_info) . '</span>';
    }
    $html .= '</div>';
    $html .= '<div class="child-actions">';
    $html .= '<a href="#" onclick="editPerson(\'' . esc_js($person_id) . '\');">' . __('Edit', 'heritagepress') . '</a>';
    $html .= ' | <a href="#" onclick="removeChild(\'' . esc_js($person_id) . '\');">' . __('Remove', 'heritagepress') . '</a>';
    $html .= '</div>';
    $html .= '</div>';

    return $html;
  }

  /**
   * AJAX: Update person
   */
  public function ajax_update_person()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('edit_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    $person_id = sanitize_text_field($_POST['personID']);
    $gedcom = sanitize_text_field($_POST['gedcom']);
    $person_data = $this->sanitize_person_data($_POST);
    $person_data = $this->parse_person_dates($person_data);

    $result = $this->update_person($person_id, $gedcom, $person_data);

    if ($result) {
      wp_send_json_success('Person updated successfully');
    } else {
      wp_send_json_error('Failed to update person');
    }
  }

  /**
   * AJAX: Delete person
   */
  public function ajax_delete_person()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('delete_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    $person_id = sanitize_text_field($_POST['personID']);
    $gedcom = sanitize_text_field($_POST['gedcom']);

    $result = $this->delete_person($person_id, $gedcom);

    if ($result) {
      wp_send_json_success('Person deleted successfully');
    } else {
      wp_send_json_error('Failed to delete person');
    }
  }

  /**
   * AJAX: Search people
   */
  public function ajax_search_people()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    $search_term = sanitize_text_field($_POST['search_term']);
    $gedcom = sanitize_text_field($_POST['gedcom']);
    $limit = intval($_POST['limit'] ?? 50);

    global $wpdb;
    $people_table = $wpdb->prefix . 'hp_people';

    $results = $wpdb->get_results($wpdb->prepare(
      "SELECT personID, firstname, lastname, birthdate, deathdate
             FROM $people_table
             WHERE gedcom = %s
             AND (firstname LIKE %s OR lastname LIKE %s OR personID LIKE %s)
             ORDER BY lastname, firstname
             LIMIT %d",
      $gedcom,
      '%' . $search_term . '%',
      '%' . $search_term . '%',
      '%' . $search_term . '%',
      $limit
    ));

    wp_send_json_success(array('people' => $results));
  }
  /**
   * Display the people management page
   */
  public function display_page()
  {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Get current tab for form handling
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'browse';

    // Handle form submissions for the current tab
    $this->handle_people_actions($current_tab);

    // Display any notices
    $this->display_notices();

    // Include the main people management interface
    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/people/people-main.php';
  }

  /**
   * AJAX: Add association between people/families
   */
  public function ajax_add_association()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('edit_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    try {
      $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');
      $person_id = sanitize_text_field($_POST['person_id'] ?? '');
      $associated_id = sanitize_text_field($_POST['associated_id'] ?? '');
      $relationship = sanitize_text_field($_POST['relationship'] ?? '');
      $rel_type = sanitize_text_field($_POST['rel_type'] ?? 'I');
      $create_reverse = !empty($_POST['create_reverse']);

      // Validate required fields
      if (empty($gedcom) || empty($person_id) || empty($associated_id) || empty($relationship)) {
        wp_send_json_error('All fields are required');
      }

      // Load association manager
      require_once plugin_dir_path(__FILE__) . '../../includes/core/class-hp-association-manager.php';
      $association_manager = new HP_Association_Manager();

      // Validate association data
      $validation = $association_manager->validate_association_data([
        'gedcom' => $gedcom,
        'person_id' => $person_id,
        'associated_id' => $associated_id,
        'relationship' => $relationship,
        'rel_type' => $rel_type
      ]);

      if (!$validation['valid']) {
        wp_send_json_error([
          'message' => 'Validation failed',
          'errors' => $validation['errors']
        ]);
      }

      // Add association
      $association_id = $association_manager->add_association(
        $gedcom,
        $person_id,
        $associated_id,
        $relationship,
        $rel_type,
        $create_reverse
      );

      if ($association_id === false) {
        wp_send_json_error('Failed to create association');
      }

      // Get display name for response
      $display_name = $association_manager->get_associated_display_name(
        $gedcom,
        $associated_id,
        $rel_type
      );

      // Format display with relationship
      $display_string = $this->truncate_string(
        $display_name . ': ' . stripslashes($relationship),
        75
      );

      wp_send_json_success([
        'id' => $association_id,
        'person_id' => $person_id,
        'tree' => $gedcom,
        'display' => $display_string,
        'allow_edit' => $this->check_capability('edit_genealogy'),
        'allow_delete' => $this->check_capability('delete_genealogy')
      ]);
    } catch (Exception $e) {
      error_log('HeritagePress Association Error: ' . $e->getMessage());
      wp_send_json_error('An error occurred while adding the association');
    }
  }

  /**
   * AJAX: Delete association
   */
  public function ajax_delete_association()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('delete_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    try {
      $association_id = intval($_POST['association_id'] ?? 0);

      if ($association_id <= 0) {
        wp_send_json_error('Invalid association ID');
      }

      // Load association manager
      require_once plugin_dir_path(__FILE__) . '../../includes/core/class-hp-association-manager.php';
      $association_manager = new HP_Association_Manager();

      $result = $association_manager->delete_association($association_id);

      if ($result) {
        wp_send_json_success('Association deleted successfully');
      } else {
        wp_send_json_error('Failed to delete association');
      }
    } catch (Exception $e) {
      error_log('HeritagePress Association Error: ' . $e->getMessage());
      wp_send_json_error('An error occurred while deleting the association');
    }
  }

  /**
   * AJAX: Get person associations
   */
  public function ajax_get_person_associations()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('read')) {
      wp_send_json_error('Insufficient permissions');
    }

    try {
      $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');
      $person_id = sanitize_text_field($_POST['person_id'] ?? '');

      if (empty($gedcom) || empty($person_id)) {
        wp_send_json_error('Tree and person ID are required');
      }

      // Load association manager
      require_once plugin_dir_path(__FILE__) . '../../includes/core/class-hp-association-manager.php';
      $association_manager = new HP_Association_Manager();

      $associations = $association_manager->get_person_associations($gedcom, $person_id);

      // Format associations for display
      $formatted_associations = [];
      foreach ($associations as $assoc) {
        $display_name = $association_manager->get_associated_display_name(
          $assoc['gedcom'],
          $assoc['passocID'],
          $assoc['reltype']
        );

        $formatted_associations[] = [
          'id' => $assoc['assocID'],
          'person_id' => $assoc['personID'],
          'associated_id' => $assoc['passocID'],
          'relationship' => $assoc['relationship'],
          'rel_type' => $assoc['reltype'],
          'display_name' => $display_name,
          'tree' => $assoc['gedcom']
        ];
      }

      wp_send_json_success(['associations' => $formatted_associations]);
    } catch (Exception $e) {
      error_log('HeritagePress Association Error: ' . $e->getMessage());
      wp_send_json_error('An error occurred while retrieving associations');
    }
  }

  /**
   * Truncate string to specified length
   *
   * @param string $string String to truncate
   * @param int $length Maximum length
   * @return string Truncated string
   */
  private function truncate_string($string, $length)
  {
    if (strlen($string) <= $length) {
      return $string;
    }

    return substr($string, 0, $length - 3) . '...';
  }

  /**
   * Log admin action for genealogy compatibility
   */
  private function log_admin_action($message)
  {
    // For now, we'll use WordPress's built-in activity log or custom logging
    // This can be enhanced later to match genealogy admin logging exactly
    if (function_exists('error_log')) {
      error_log("HeritagePress Admin: $message");
    }

    // Could also add to a custom admin log table in the future
    // or integrate with WordPress action logging plugins
  }

  /**
   * AJAX handler for running people utilities
   */
  public function ajax_run_people_utility()
  {
    check_ajax_referer('hp_run_utility');
    if (!current_user_can('manage_genealogy')) {
      wp_send_json_error(['message' => __('Insufficient permissions.', 'heritagepress')]);
    }
    $utility = sanitize_text_field($_POST['utility'] ?? '');
    $tree = sanitize_text_field($_POST['tree'] ?? '');
    if ($utility === 'assign_default_photos') {
      $result = $this->assign_default_photos($tree);
      wp_send_json_success(['report' => $result]);
    }
    wp_send_json_error(['message' => __('Unknown utility.', 'heritagepress')]);
  }

  /**
   * Assign default photos to people (bulk)
   *
   * @param string $tree Optional tree ID
   * @return string HTML report
   */
  public function assign_default_photos($tree = '')
  {
    global $wpdb;
    $people_table = $wpdb->prefix . 'hp_people';
    $medialinks_table = $wpdb->prefix . 'hp_medialinks';
    $media_table = $wpdb->prefix . 'hp_media';
    $where = '';
    if ($tree) {
      $where = $wpdb->prepare('WHERE gedcom = %s', $tree);
    }
    $people = $wpdb->get_results("SELECT personID, gedcom FROM $people_table $where");
    $defsdone = 0;
    foreach ($people as $person) {
      // Check if a default photo already exists
      $def_exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $medialinks_table WHERE gedcom = %s AND personID = %s AND defphoto = '1'",
        $person->gedcom,
        $person->personID
      ));
      if ($def_exists) continue;
      // Find first media link with a thumbnail
      $media = $wpdb->get_results($wpdb->prepare(
        "SELECT medialinkID FROM $medialinks_table ml JOIN $media_table m ON ml.mediaID = m.mediaID WHERE ml.gedcom = %s AND ml.personID = %s AND m.thumbpath != '' ORDER BY ml.ordernum",
        $person->gedcom,
        $person->personID
      ));
      $count = 0;
      foreach ($media as $ml) {
        if ($count == 0) {
          $wpdb->update($medialinks_table, ['defphoto' => '1'], ['medialinkID' => $ml->medialinkID]);
        } else {
          $wpdb->update($medialinks_table, ['defphoto' => '0'], ['medialinkID' => $ml->medialinkID]);
        }
        $count++;
        $defsdone++;
      }
    }
    return sprintf(__('Default photos assigned: %d', 'heritagepress'), $defsdone);
  }

  /**
   * Enqueue scripts for the person edit screen
   */
  public function enqueue_edit_person_scripts($hook)
  {
    // Only load on the person edit screen
    if (strpos($hook, 'heritagepress-people') === false) {
      return;
    }
    wp_enqueue_script(
      'hp-find-link-media',
      plugins_url('../../admin/js/find-link-media.js', __FILE__),
      array('jquery', 'underscore'),
      '1.0',
      true
    );
    wp_localize_script('hp-find-link-media', 'hpFindLinkMedia', array(
      'nonce' => wp_create_nonce('hp_get_media_list'),
      'linkNonce' => wp_create_nonce('hp_link_media_to_person'),
      'getLinkedNonce' => wp_create_nonce('hp_get_linked_media_for_person')
    ));
  }
}
