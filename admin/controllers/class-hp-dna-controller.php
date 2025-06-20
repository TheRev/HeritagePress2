<?php

/**
 * DNA Groups Controller
 *
 * Handles all DNA group management functionality including CRUD operations,
 * DNA group validation, and DNA-related AJAX requests
 */

if (!defined('ABSPATH')) {
  exit;
}

require_once plugin_dir_path(__FILE__) . '../../includes/controllers/class-hp-base-controller.php';

class HP_DNA_Controller extends HP_Base_Controller
{
  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct('dna');
    $this->capabilities = array(
      'manage_dna' => 'manage_genealogy',
      'edit_dna' => 'edit_genealogy',
      'delete_dna' => 'delete_genealogy'
    );
  }

  /**
   * Initialize the DNA controller
   */
  public function init()
  {
    parent::init();
    // DNA-specific initialization
  }

  /**
   * Register hooks for DNA management
   */
  public function register_hooks()
  {
    parent::register_hooks();

    // AJAX handlers for DNA groups
    add_action('wp_ajax_hp_add_dna_group', array($this, 'ajax_add_dna_group'));
    add_action('wp_ajax_hp_update_dna_group', array($this, 'ajax_update_dna_group'));
    add_action('wp_ajax_hp_delete_dna_group', array($this, 'ajax_delete_dna_group'));
    add_action('wp_ajax_hp_search_dna_groups', array($this, 'ajax_search_dna_groups'));

    // AJAX handlers for DNA tests
    add_action('wp_ajax_hp_add_dna_test', array($this, 'ajax_add_dna_test'));
    add_action('wp_ajax_hp_update_dna_test', array($this, 'ajax_update_dna_test'));
    add_action('wp_ajax_hp_delete_dna_test', array($this, 'ajax_delete_dna_test'));
    add_action('wp_ajax_hp_search_dna_tests', array($this, 'ajax_search_dna_tests'));
    add_action('wp_ajax_hp_get_dna_test', array($this, 'ajax_get_dna_test'));

    // Form handlers
    add_action('wp_ajax_hp_dna_form_handler', array($this, 'handle_form_submission'));
    add_action('wp_ajax_nopriv_hp_dna_form_handler', array($this, 'handle_form_submission'));
  }

  /**
   * Handle form submissions
   */
  public function handle_form_submission()
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      return;
    }

    $action = sanitize_text_field($_POST['action'] ?? '');
    switch ($action) {
      case 'add_dna_group':
        $this->handle_add_dna_group();
        break;
      case 'update_dna_group':
        $this->handle_update_dna_group();
        break;
      case 'delete_dna_group':
        $this->handle_delete_dna_group();
        break;
      case 'add_dna_test':
        $this->handle_add_dna_test();
        break;
      case 'update_dna_test':
        $this->handle_update_dna_test();
        break;
      case 'delete_dna_test':
        $this->handle_delete_dna_test();
        break;
      case 'bulk_dna_group_actions':
        $this->handle_bulk_dna_group_actions();
        break;
    }
  }

  /**
   * Handle adding a new DNA group
   */
  private function handle_add_dna_group()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    // Sanitize form data
    $dna_group_data = $this->sanitize_dna_group_data($_POST);

    // Validate required fields
    if (empty($dna_group_data['dna_group']) || empty($dna_group_data['gedcom'])) {
      $this->add_notice(__('Group ID and Tree are required.', 'heritagepress'), 'error');
      return;
    }

    // Validate DNA group ID format
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $dna_group_data['dna_group'])) {
      $this->add_notice(__('Group ID must be alphanumeric with underscores and hyphens only.', 'heritagepress'), 'error');
      return;
    }

    // Check if DNA group ID already exists in this tree
    if ($this->dna_group_id_exists($dna_group_data['dna_group'], $dna_group_data['gedcom'])) {
      $this->add_notice(__('DNA Group ID already exists in this tree. Please choose a different ID.', 'heritagepress'), 'error');
      return;
    }

    // Validate test type selection
    if (empty($dna_group_data['test_type'])) {
      $this->add_notice(__('Please select a test type.', 'heritagepress'), 'error');
      return;
    }

    // Validate description
    if (empty($dna_group_data['description'])) {
      $this->add_notice(__('Please enter a description.', 'heritagepress'), 'error');
      return;
    }

    // Create the DNA group
    $result = $this->create_dna_group($dna_group_data);

    if ($result) {
      $this->add_notice(__('DNA Group created successfully!', 'heritagepress'), 'success');
    } else {
      $this->add_notice(__('Failed to create DNA group. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle updating an existing DNA group
   */
  private function handle_update_dna_group()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    $dna_group_id = sanitize_text_field($_POST['dna_group']);
    $gedcom = sanitize_text_field($_POST['gedcom']);

    // Sanitize form data
    $dna_group_data = $this->sanitize_dna_group_data($_POST);

    $result = $this->update_dna_group($dna_group_id, $gedcom, $dna_group_data);

    if ($result) {
      $this->add_notice(__('DNA Group updated successfully!', 'heritagepress'), 'success');
    } else {
      $this->add_notice(__('Failed to update DNA group. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle deleting a DNA group
   */
  private function handle_delete_dna_group()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!$this->check_capability('delete_genealogy')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    $dna_group_id = sanitize_text_field($_POST['dna_group']);
    $gedcom = sanitize_text_field($_POST['gedcom']);

    $result = $this->delete_dna_group($dna_group_id, $gedcom);

    if ($result) {
      $this->add_notice(__('DNA Group deleted successfully!', 'heritagepress'), 'success');
    } else {
      $this->add_notice(__('Failed to delete DNA group. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle bulk DNA group actions
   */
  private function handle_bulk_dna_group_actions()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    $action = sanitize_text_field($_POST['bulk_action']);
    $dna_group_ids = array_map('sanitize_text_field', $_POST['dna_group_ids']);
    $gedcom = sanitize_text_field($_POST['gedcom']);

    if (empty($dna_group_ids)) {
      $this->add_notice(__('No DNA groups selected.', 'heritagepress'), 'error');
      return;
    }

    switch ($action) {
      case 'delete':
        $callback = function ($dna_group_id) use ($gedcom, $wpdb) {
          // Remove group from related DNA tests and links (HeritagePress logic)
          $dna_tests_table = $wpdb->prefix . 'hp_dna_tests';
          $dna_links_table = $wpdb->prefix . 'hp_dna_links';
          $wpdb->update($dna_tests_table, ['dna_group' => '', 'dna_group_desc' => ''], ['dna_group' => $dna_group_id]);
          $wpdb->update($dna_links_table, ['dna_group' => ''], ['dna_group' => $dna_group_id]);
          // Now delete the group itself
          return $this->delete_dna_group($dna_group_id, $gedcom);
        };
        $this->handle_bulk_action($action, $dna_group_ids, $callback);
        break;
      default:
        $this->add_notice(__('Invalid bulk action.', 'heritagepress'), 'error');
    }
  }

  /**
   * Sanitize DNA group form data
   */
  private function sanitize_dna_group_data($data)
  {
    return $this->sanitize_form_data($data, array(
      'dna_group' => 'text',
      'gedcom' => 'text',
      'description' => 'text',
      'test_type' => 'text'
    ));
  }

  /**
   * Check if DNA group ID already exists
   */
  private function dna_group_id_exists($dna_group_id, $gedcom)
  {
    global $wpdb;
    $dna_groups_table = $wpdb->prefix . 'hp_dna_groups';

    $count = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $dna_groups_table WHERE dna_group = %s AND gedcom = %s",
      $dna_group_id,
      $gedcom
    ));

    return $count > 0;
  }

  /**
   * Create a new DNA group
   */
  private function create_dna_group($dna_group_data)
  {
    global $wpdb;
    $dna_groups_table = $wpdb->prefix . 'hp_dna_groups';

    // Prepare data for insertion
    $insert_data = array(
      'dna_group' => $dna_group_data['dna_group'],
      'gedcom' => $dna_group_data['gedcom'],
      'description' => $dna_group_data['description'] ?? '',
      'test_type' => $dna_group_data['test_type'] ?? '',
      'action' => '2' // Compatibility (2 = active)
    );

    $result = $wpdb->insert($dna_groups_table, $insert_data);

    return $result !== false;
  }

  /**
   * Update an existing DNA group
   */
  private function update_dna_group($dna_group_id, $gedcom, $dna_group_data)
  {
    global $wpdb;
    $dna_groups_table = $wpdb->prefix . 'hp_dna_groups';
    $dna_tests_table = $wpdb->prefix . 'hp_dna_tests';

    // Prepare data for update
    $update_data = array(
      'description' => $dna_group_data['description'] ?? '',
      'test_type' => $dna_group_data['test_type'] ?? ''
    );

    // Update DNA groups table
    $result = $wpdb->update(
      $dna_groups_table,
      $update_data,
      array('dna_group' => $dna_group_id, 'gedcom' => $gedcom)
    );

    // Update related DNA tests table (compatibility)
    if ($result !== false) {
      $wpdb->update(
        $dna_tests_table,
        array('dna_group_desc' => $dna_group_data['description']),
        array('dna_group' => $dna_group_id)
      );
    }

    return $result !== false;
  }

  /**
   * Delete a DNA group
   */
  private function delete_dna_group($dna_group_id, $gedcom)
  {
    global $wpdb;
    $dna_groups_table = $wpdb->prefix . 'hp_dna_groups';

    $result = $wpdb->delete(
      $dna_groups_table,
      array('dna_group' => $dna_group_id, 'gedcom' => $gedcom),
      array('%s', '%s')
    );

    return $result !== false;
  }

  /**
   * Get DNA test count for a group
   */
  private function get_dna_test_count($dna_group_id, $gedcom)
  {
    global $wpdb;
    $dna_tests_table = $wpdb->prefix . 'hp_dna_tests';

    $count = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $dna_tests_table WHERE dna_group = %s AND gedcom = %s",
      $dna_group_id,
      $gedcom
    ));

    return intval($count);
  }

  /**
   * AJAX: Check if DNA group ID exists
   */
  public function ajax_check_dna_group_id()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    $dna_group_id = sanitize_text_field($_POST['dna_group_id']);
    $gedcom = sanitize_text_field($_POST['gedcom']);

    $exists = $this->dna_group_id_exists($dna_group_id, $gedcom);

    wp_send_json_success(array('exists' => $exists));
  }

  /**
   * AJAX: Add DNA group
   */
  public function ajax_add_dna_group()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('edit_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    // Sanitize and validate data
    $dna_group_data = $this->sanitize_dna_group_data($_POST);

    if (empty($dna_group_data['dna_group']) || empty($dna_group_data['gedcom'])) {
      wp_send_json_error('Group ID and Tree are required.');
    }

    if ($this->dna_group_id_exists($dna_group_data['dna_group'], $dna_group_data['gedcom'])) {
      wp_send_json_error('Group ID already exists in this tree.');
    }

    if (empty($dna_group_data['test_type'])) {
      wp_send_json_error('Please select a test type.');
    }

    if (empty($dna_group_data['description'])) {
      wp_send_json_error('Please enter a description.');
    }

    $result = $this->create_dna_group($dna_group_data);

    if ($result) {
      wp_send_json_success('DNA Group created successfully');
    } else {
      wp_send_json_error('Failed to create DNA group');
    }
  }

  /**
   * AJAX: Update DNA group
   */
  public function ajax_update_dna_group()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('edit_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    $dna_group_id = sanitize_text_field($_POST['dna_group']);
    $gedcom = sanitize_text_field($_POST['gedcom']);
    $dna_group_data = $this->sanitize_dna_group_data($_POST);

    $result = $this->update_dna_group($dna_group_id, $gedcom, $dna_group_data);

    if ($result) {
      wp_send_json_success('DNA Group updated successfully');
    } else {
      wp_send_json_error('Failed to update DNA group');
    }
  }

  /**
   * AJAX: Delete DNA group
   */
  public function ajax_delete_dna_group()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('delete_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    $dna_group_id = sanitize_text_field($_POST['dna_group']);
    $gedcom = sanitize_text_field($_POST['gedcom']);

    $result = $this->delete_dna_group($dna_group_id, $gedcom);

    if ($result) {
      wp_send_json_success('DNA Group deleted successfully');
    } else {
      wp_send_json_error('Failed to delete DNA group');
    }
  }

  /**
   * AJAX: Get DNA groups
   */
  public function ajax_get_dna_groups()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    global $wpdb;
    $dna_groups_table = $wpdb->prefix . 'hp_dna_groups';
    $trees_table = $wpdb->prefix . 'hp_trees';

    $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');
    $search = sanitize_text_field($_POST['search'] ?? '');
    $offset = intval($_POST['offset'] ?? 0);
    $limit = intval($_POST['limit'] ?? 25);

    // Build WHERE clause
    $where_conditions = array();
    $where_params = array();

    if (!empty($gedcom)) {
      $where_conditions[] = "g.gedcom = %s";
      $where_params[] = $gedcom;
    }

    if (!empty($search)) {
      $where_conditions[] = "(g.dna_group LIKE %s OR g.description LIKE %s)";
      $where_params[] = '%' . $search . '%';
      $where_params[] = '%' . $search . '%';
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    // Get DNA groups with tree names
    $query = "SELECT g.dna_group, g.gedcom, g.description, g.test_type, t.treename
              FROM $dna_groups_table g
              LEFT JOIN $trees_table t ON t.gedcom = g.gedcom
              $where_clause
              ORDER BY g.description
              LIMIT %d OFFSET %d";

    $where_params[] = $limit;
    $where_params[] = $offset;

    $results = $wpdb->get_results($wpdb->prepare($query, $where_params), ARRAY_A);

    // Add test counts
    foreach ($results as &$group) {
      $group['test_count'] = $this->get_dna_test_count($group['dna_group'], $group['gedcom']);
      $group['allow_edit'] = $this->check_capability('edit_genealogy');
      $group['allow_delete'] = $this->check_capability('delete_genealogy');
    }

    wp_send_json_success(array('dna_groups' => $results));
  }

  /**
   * Display the DNA groups management page
   */
  public function display_page()
  {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Check if we're viewing a specific page
    $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

    // Display any notices
    $this->display_notices();

    // Route to correct template based on page
    if ($page === 'heritagepress-dna-tests') {
      // Include the DNA tests management template
      include plugin_dir_path(__FILE__) . '../views/dna-tests.php';
    } else {
      // Include the DNA groups management template (default)
      include plugin_dir_path(__FILE__) . '../views/dna-groups.php';
    }
  }

  // ===========================================
  // DNA TEST MANAGEMENT METHODS
  // ===========================================

  /**
   * Handle DNA test form submissions
   */
  private function handle_add_dna_test()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    // Sanitize form data
    $test_data = $this->sanitize_dna_test_data($_POST);

    // Validate required fields
    if (empty($test_data['test_type'])) {
      $this->add_notice(__('Test type is required.', 'heritagepress'), 'error');
      return;
    }

    // Create the DNA test
    $result = $this->create_dna_test($test_data);

    if ($result) {
      $this->add_notice(__('DNA Test created successfully!', 'heritagepress'), 'success');
      // Redirect to edit page
      $redirect_url = admin_url('admin.php?page=heritagepress-dna-tests&tab=edit&testID=' . $result . '&added=1');
      wp_redirect($redirect_url);
      exit;
    } else {
      $this->add_notice(__('Failed to create DNA test. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle updating an existing DNA test
   */
  private function handle_update_dna_test()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    $test_id = intval($_POST['testID']);
    $test_data = $this->sanitize_dna_test_data($_POST);

    $result = $this->update_dna_test($test_id, $test_data);

    if ($result) {
      $this->add_notice(__('DNA Test updated successfully!', 'heritagepress'), 'success');
    } else {
      $this->add_notice(__('Failed to update DNA test. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle deleting a DNA test
   */
  private function handle_delete_dna_test()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    $test_id = intval($_POST['testID']);

    $result = $this->delete_dna_test($test_id);

    if ($result) {
      $this->add_notice(__('DNA Test deleted successfully!', 'heritagepress'), 'success');
      // Redirect to tests list
      wp_redirect(admin_url('admin.php?page=heritagepress-dna-tests'));
      exit;
    } else {
      $this->add_notice(__('Failed to delete DNA test. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Create a new DNA test
   */
  private function create_dna_test($test_data)
  {
    global $wpdb;
    $dna_tests_table = $wpdb->prefix . 'hp_dna_tests';
    $dna_links_table = $wpdb->prefix . 'hp_dna_links';

    // Parse dates
    $test_data['test_date'] = $this->convert_date($test_data['test_date'] ?? '');
    $test_data['match_date'] = $this->convert_date($test_data['match_date'] ?? '');

    // Get DNA group description
    $dna_group_desc = '';
    if (!empty($test_data['dna_group'])) {
      $dna_groups_table = $wpdb->prefix . 'hp_dna_groups';
      $group = $wpdb->get_row($wpdb->prepare(
        "SELECT description FROM $dna_groups_table WHERE dna_group = %s",
        $test_data['dna_group']
      ));
      if ($group) {
        $dna_group_desc = $group->description;
      }
    }

    // Prepare insert data (matching standard fields exactly)
    $insert_data = array(
      'test_type' => $test_data['test_type'] ?? '',
      'test_number' => $test_data['test_number'] ?? '',
      'notes' => $test_data['notes'] ?? '',
      'vendor' => $test_data['vendor'] ?? '',
      'test_date' => $test_data['test_date'],
      'match_date' => $test_data['match_date'],
      'personID' => $test_data['personID'] ?? '',
      'gedcom' => $test_data['gedcom'] ?? '',
      'person_name' => $test_data['person_name'] ?? '',
      'urls' => $test_data['urls'] ?? '',
      'mtdna_haplogroup' => $test_data['mtdna_haplogroup'] ?? '',
      'ydna_haplogroup' => $test_data['ydna_haplogroup'] ?? '',
      'significant_snp' => $test_data['significant_snp'] ?? '',
      'terminal_snp' => $test_data['terminal_snp'] ?? '',
      'markers' => $test_data['markers'] ?? '',
      'y_results' => $test_data['y_results'] ?? '',
      'hvr1_results' => $test_data['hvr1_results'] ?? '',
      'hvr2_results' => $test_data['hvr2_results'] ?? '',
      'mtdna_confirmed' => $test_data['mtdna_confirmed'] ?? '',
      'ydna_confirmed' => $test_data['ydna_confirmed'] ?? '',
      'markeropt' => $test_data['markeropt'] ?? '',
      'notesopt' => $test_data['notesopt'] ?? '',
      'linksopt' => $test_data['linksopt'] ?? '',
      'surnamesopt' => intval($test_data['surnamesopt'] ?? 0),
      'private_dna' => $test_data['private_dna'] ?? '0',
      'private_test' => $test_data['private_test'] ?? '0',
      'dna_group' => $test_data['dna_group'] ?? '',
      'dna_group_desc' => $dna_group_desc,
      'surnames' => $test_data['surnames'] ?? '',
      'GEDmatchID' => $test_data['GEDmatchID'] ?? ''
    );

    $result = $wpdb->insert($dna_tests_table, $insert_data);

    if ($result) {
      $test_id = $wpdb->insert_id;

      // Create DNA link if personID is provided
      if (!empty($test_data['personID']) && !empty($test_data['gedcom'])) {
        $link_data = array(
          'testID' => $test_id,
          'personID' => $test_data['personID'],
          'gedcom' => $test_data['gedcom'],
          'dna_group' => $test_data['dna_group'] ?? ''
        );
        $wpdb->insert($dna_links_table, $link_data);
      }

      return $test_id;
    }

    return false;
  }

  /**
   * Update an existing DNA test
   */
  private function update_dna_test($test_id, $test_data)
  {
    global $wpdb;
    $dna_tests_table = $wpdb->prefix . 'hp_dna_tests';

    // Parse dates
    $test_data['test_date'] = $this->convert_date($test_data['test_date'] ?? '');
    $test_data['match_date'] = $this->convert_date($test_data['match_date'] ?? '');

    // Get DNA group description
    $dna_group_desc = '';
    if (!empty($test_data['dna_group'])) {
      $dna_groups_table = $wpdb->prefix . 'hp_dna_groups';
      $group = $wpdb->get_row($wpdb->prepare(
        "SELECT description FROM $dna_groups_table WHERE dna_group = %s",
        $test_data['dna_group']
      ));
      if ($group) {
        $dna_group_desc = $group->description;
      }
    }

    // Prepare update data
    $update_data = array(
      'test_type' => $test_data['test_type'] ?? '',
      'test_number' => $test_data['test_number'] ?? '',
      'notes' => $test_data['notes'] ?? '',
      'vendor' => $test_data['vendor'] ?? '',
      'test_date' => $test_data['test_date'],
      'match_date' => $test_data['match_date'],
      'personID' => $test_data['personID'] ?? '',
      'gedcom' => $test_data['gedcom'] ?? '',
      'person_name' => $test_data['person_name'] ?? '',
      'urls' => $test_data['urls'] ?? '',
      'mtdna_haplogroup' => $test_data['mtdna_haplogroup'] ?? '',
      'ydna_haplogroup' => $test_data['ydna_haplogroup'] ?? '',
      'significant_snp' => $test_data['significant_snp'] ?? '',
      'terminal_snp' => $test_data['terminal_snp'] ?? '',
      'markers' => $test_data['markers'] ?? '',
      'y_results' => $test_data['y_results'] ?? '',
      'hvr1_results' => $test_data['hvr1_results'] ?? '',
      'hvr2_results' => $test_data['hvr2_results'] ?? '',
      'mtdna_confirmed' => $test_data['mtdna_confirmed'] ?? '',
      'ydna_confirmed' => $test_data['ydna_confirmed'] ?? '',
      'markeropt' => $test_data['markeropt'] ?? '',
      'notesopt' => $test_data['notesopt'] ?? '',
      'linksopt' => $test_data['linksopt'] ?? '',
      'surnamesopt' => intval($test_data['surnamesopt'] ?? 0),
      'private_dna' => $test_data['private_dna'] ?? '0',
      'private_test' => $test_data['private_test'] ?? '0',
      'dna_group' => $test_data['dna_group'] ?? '',
      'dna_group_desc' => $dna_group_desc,
      'surnames' => $test_data['surnames'] ?? '',
      'GEDmatchID' => $test_data['GEDmatchID'] ?? ''
    );

    $result = $wpdb->update(
      $dna_tests_table,
      $update_data,
      array('testID' => $test_id),
      null,
      array('%d')
    );

    return $result !== false;
  }

  /**
   * Delete a DNA test
   */
  private function delete_dna_test($test_id)
  {
    global $wpdb;
    $dna_tests_table = $wpdb->prefix . 'hp_dna_tests';
    $dna_links_table = $wpdb->prefix . 'hp_dna_links';

    // Delete from dna_links first
    $wpdb->delete($dna_links_table, array('testID' => $test_id), array('%d'));

    // Delete from dna_tests
    $result = $wpdb->delete($dna_tests_table, array('testID' => $test_id), array('%d'));

    return $result !== false;
  }

  /**
   * Get a DNA test by ID
   */
  private function get_dna_test($test_id)
  {
    global $wpdb;
    $dna_tests_table = $wpdb->prefix . 'hp_dna_tests';

    return $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM $dna_tests_table WHERE testID = %d",
      $test_id
    ), ARRAY_A);
  }

  /**
   * Sanitize DNA test form data
   */
  private function sanitize_dna_test_data($data)
  {
    return $this->sanitize_form_data($data, array(
      'test_type' => 'text',
      'test_number' => 'text',
      'notes' => 'textarea',
      'vendor' => 'text',
      'test_date' => 'text',
      'match_date' => 'text',
      'personID' => 'text',
      'gedcom' => 'text',
      'person_name' => 'text',
      'urls' => 'textarea',
      'mtdna_haplogroup' => 'text',
      'ydna_haplogroup' => 'text',
      'significant_snp' => 'text',
      'terminal_snp' => 'text',
      'markers' => 'text',
      'y_results' => 'text',
      'hvr1_results' => 'text',
      'hvr2_results' => 'text',
      'mtdna_confirmed' => 'text',
      'ydna_confirmed' => 'text',
      'markeropt' => 'text',
      'notesopt' => 'text',
      'linksopt' => 'text',
      'surnamesopt' => 'number',
      'private_dna' => 'text',
      'private_test' => 'text',
      'dna_group' => 'text',
      'surnames' => 'textarea',
      'GEDmatchID' => 'text'
    ));
  }

  /**
   * Convert date format (based on datelib.php)
   */
  private function convert_date($date_string)
  {
    if (empty($date_string)) {
      return '0000-00-00';
    }

    // Basic date conversion - could be enhanced with datelib functionality
    $date = date_create($date_string);
    if ($date) {
      return date_format($date, 'Y-m-d');
    }

    return '0000-00-00';
  }

  // ===========================================
  // AJAX HANDLERS FOR DNA TESTS
  // ===========================================

  /**
   * AJAX: Add DNA test
   */
  public function ajax_add_dna_test()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('edit_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    // Sanitize and validate data
    $test_data = $this->sanitize_dna_test_data($_POST);

    if (empty($test_data['test_type'])) {
      wp_send_json_error('Test type is required.');
    }

    $result = $this->create_dna_test($test_data);

    if ($result) {
      wp_send_json_success('DNA Test created successfully');
    } else {
      wp_send_json_error('Failed to create DNA test');
    }
  }

  /**
   * AJAX: Update DNA test
   */
  public function ajax_update_dna_test()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('edit_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    $test_id = intval($_POST['testID']);
    $test_data = $this->sanitize_dna_test_data($_POST);

    $result = $this->update_dna_test($test_id, $test_data);

    if ($result) {
      wp_send_json_success('DNA Test updated successfully');
    } else {
      wp_send_json_error('Failed to update DNA test');
    }
  }

  /**
   * AJAX: Delete DNA test
   */
  public function ajax_delete_dna_test()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('delete_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    $test_id = intval($_POST['testID']);

    $result = $this->delete_dna_test($test_id);

    if ($result) {
      wp_send_json_success('DNA Test deleted successfully');
    } else {
      wp_send_json_error('Failed to delete DNA test');
    }
  }

  /**
   * AJAX: Get DNA test
   */
  public function ajax_get_dna_test()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    $test_id = intval($_POST['testID']);
    $test = $this->get_dna_test($test_id);

    if ($test) {
      wp_send_json_success($test);
    } else {
      wp_send_json_error('DNA test not found');
    }
  }

  /**
   * AJAX: Search DNA tests
   */
  public function ajax_search_dna_tests()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    $search = sanitize_text_field($_POST['search'] ?? '');
    $test_type = sanitize_text_field($_POST['test_type'] ?? '');
    $test_group = sanitize_text_field($_POST['test_group'] ?? '');
    $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');
    $limit = intval($_POST['limit'] ?? 50);
    $offset = intval($_POST['offset'] ?? 0);

    global $wpdb;
    $dna_tests_table = $wpdb->prefix . 'hp_dna_tests';
    $people_table = $wpdb->prefix . 'hp_people';

    $where_conditions = array();
    $where_params = array();

    if (!empty($gedcom)) {
      $where_conditions[] = "t.gedcom = %s";
      $where_params[] = $gedcom;
    }

    if (!empty($test_type)) {
      $where_conditions[] = "t.test_type = %s";
      $where_params[] = $test_type;
    }

    if (!empty($test_group)) {
      $where_conditions[] = "t.dna_group = %s";
      $where_params[] = $test_group;
    }

    if (!empty($search)) {
      $where_conditions[] = "(t.test_number LIKE %s OR t.person_name LIKE %s OR p.firstname LIKE %s OR p.lastname LIKE %s)";
      $where_params[] = '%' . $search . '%';
      $where_params[] = '%' . $search . '%';
      $where_params[] = '%' . $search . '%';
      $where_params[] = '%' . $search . '%';
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    // Get DNA tests with person info
    $query = "SELECT t.*, p.firstname, p.lastname, p.birthdate, p.deathdate
              FROM $dna_tests_table t
              LEFT JOIN $people_table p ON p.personID = t.personID AND p.gedcom = t.gedcom
              $where_clause
              ORDER BY t.testID DESC
              LIMIT %d OFFSET %d";

    $where_params[] = $limit;
    $where_params[] = $offset;

    $results = $wpdb->get_results($wpdb->prepare($query, $where_params), ARRAY_A);

    // Add additional info
    foreach ($results as &$test) {
      $test['allow_edit'] = $this->check_capability('edit_genealogy');
      $test['allow_delete'] = $this->check_capability('delete_genealogy');
    }

    wp_send_json_success(array('dna_tests' => $results));
  }
}
