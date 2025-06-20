<?php

/**
 * Repository Controller for HeritagePress
 *
 * Handles repository management functionality including:
 * - Repository creation, editing, and deletion
 * - Repository search and listing
 * - Repository merging
 * - Address management for repositories
 * * This controller integrates with WordPress AJAX for dynamic operations
 * * Provides a user interface for managing repositories in the HeritagePress plugin
 * * This file is part of the HeritagePress plugin for WordPress.
 *
 *
 * @package HeritagePress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

class HP_Repository_Controller
{
  /**
   * Initialize the controller
   */
  public function __construct()
  {
    $this->init_hooks();
  }

  /**
   * Initialize WordPress hooks
   */
  private function init_hooks()
  {
    // AJAX handlers
    add_action('wp_ajax_hp_add_repository', array($this, 'ajax_add_repository'));
    add_action('wp_ajax_hp_update_repository', array($this, 'ajax_update_repository'));
    add_action('wp_ajax_hp_delete_repository', array($this, 'ajax_delete_repository'));
    add_action('wp_ajax_hp_search_repositories', array($this, 'ajax_search_repositories'));
    add_action('wp_ajax_hp_get_repository', array($this, 'ajax_get_repository'));
    add_action('wp_ajax_hp_generate_repo_id', array($this, 'ajax_generate_repo_id'));
    add_action('wp_ajax_hp_check_repo_id', array($this, 'ajax_check_repo_id'));
    add_action('wp_ajax_hp_merge_repositories', array($this, 'ajax_merge_repositories'));
  }

  /**
   * Display the repositories management page
   */
  public function display_page()
  {
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'search';

    echo '<div class="wrap">';
    echo '<h1>' . __('Repository Management', 'heritagepress') . '</h1>';

    // Tab navigation
    $this->display_tabs($current_tab);

    // Tab content
    switch ($current_tab) {
      case 'search':
        $this->display_search_tab();
        break;
      case 'add':
        $this->display_add_tab();
        break;
      case 'edit':
        $this->display_edit_tab();
        break;
      case 'merge':
        $this->display_merge_tab();
        break;
      default:
        $this->display_search_tab();
        break;
    }

    echo '</div>';
  }

  /**
   * Display tab navigation
   */
  private function display_tabs($current_tab)
  {
    $tabs = array(
      'search' => __('Search Repositories', 'heritagepress'),
      'add' => __('Add New', 'heritagepress'),
      'merge' => __('Merge', 'heritagepress')
    );

    echo '<h2 class="nav-tab-wrapper">';
    foreach ($tabs as $tab => $label) {
      $active = ($current_tab === $tab) ? 'nav-tab-active' : '';
      $url = admin_url('admin.php?page=heritagepress-repositories&tab=' . $tab);
      echo '<a href="' . esc_url($url) . '" class="nav-tab ' . $active . '">' . esc_html($label) . '</a>';
    }
    echo '</h2>';
  }

  /**
   * Display search repositories tab
   */
  private function display_search_tab()
  {
    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/repositories-main.php';
  }

  /**
   * Display add repository tab
   */
  private function display_add_tab()
  {
    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/repositories-add.php';
  }

  /**
   * Display edit repository tab
   */
  private function display_edit_tab()
  {
    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/repositories-edit.php';
  }

  /**
   * Display merge repositories tab
   */
  private function display_merge_tab()
  {
    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/repositories-merge.php';
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
      case 'add_repository':
        $this->handle_add_repository();
        break;
      case 'update_repository':
        $this->handle_update_repository();
        break;
      case 'delete_repository':
        $this->handle_delete_repository();
        break;
    }
  }

  /**
   * Handle repository addition (from admin_addrepo.php)
   */
  private function handle_add_repository()
  {
    // Security check
    if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['hp_repository_nonce'], 'hp_add_repository')) {
      wp_die(__('Security check failed.', 'heritagepress'));
    }

    // Sanitize input data
    $repo_data = $this->sanitize_repository_data($_POST);

    // Validate required fields
    if (empty($repo_data['repoID'])) {
      $this->add_admin_notice(__('Repository ID is required.', 'heritagepress'), 'error');
      return;
    }

    if (empty($repo_data['reponame'])) {
      $this->add_admin_notice(__('Repository name is required.', 'heritagepress'), 'error');
      return;
    }

    // Check if repository ID already exists
    if ($this->repository_exists($repo_data['repoID'], $repo_data['gedcom'])) {
      $this->add_admin_notice(__('Repository ID already exists.', 'heritagepress'), 'error');
      return;
    }

    // Create address record if address data provided
    $address_id = $this->create_address_record($repo_data);

    // Create repository record
    $repo_id = $this->create_repository_record($repo_data, $address_id);

    if ($repo_id) {
      // Log the action
      $this->log_admin_action('add_repository', $repo_data['repoID'], $repo_data['gedcom']);

      $this->add_admin_notice(__('Repository added successfully.', 'heritagepress'), 'success');

      // Redirect to edit page
      $edit_url = admin_url('admin.php?page=heritagepress-repositories&tab=edit&repoID=' . urlencode($repo_data['repoID']) . '&gedcom=' . urlencode($repo_data['gedcom']) . '&added=1');
      wp_redirect($edit_url);
      exit;
    } else {
      $this->add_admin_notice(__('Failed to add repository.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle repository update
   */
  private function handle_update_repository()
  {
    // Security check
    if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['hp_repository_nonce'], 'hp_update_repository')) {
      wp_die(__('Security check failed.', 'heritagepress'));
    }

    // Sanitize input data
    $repo_data = $this->sanitize_repository_data($_POST);

    // Validate required fields
    if (empty($repo_data['reponame'])) {
      $this->add_admin_notice(__('Repository name is required.', 'heritagepress'), 'error');
      return;
    }

    // Update address record
    if (!empty($repo_data['addressID'])) {
      $this->update_address_record($repo_data['addressID'], $repo_data);
    } else if ($this->has_address_data($repo_data)) {
      // Create new address record if address data provided but no existing addressID
      $repo_data['addressID'] = $this->create_address_record($repo_data);
    }

    // Update repository record
    if ($this->update_repository_record($repo_data)) {
      // Log the action
      $this->log_admin_action('update_repository', $repo_data['repoID'], $repo_data['gedcom']);

      $this->add_admin_notice(__('Repository updated successfully.', 'heritagepress'), 'success');
    } else {
      $this->add_admin_notice(__('Failed to update repository.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle repository deletion
   */
  private function handle_delete_repository()
  {
    // Security check
    if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['hp_repository_nonce'], 'hp_delete_repository')) {
      wp_die(__('Security check failed.', 'heritagepress'));
    }

    $repo_id = sanitize_text_field($_POST['repoID']);
    $gedcom = sanitize_text_field($_POST['gedcom']);

    if ($this->delete_repository($repo_id, $gedcom)) {
      // Log the action
      $this->log_admin_action('delete_repository', $repo_id, $gedcom);

      $this->add_admin_notice(__('Repository deleted successfully.', 'heritagepress'), 'success');
    } else {
      $this->add_admin_notice(__('Failed to delete repository.', 'heritagepress'), 'error');
    }
  }

  /**
   * AJAX: Add repository
   */
  public function ajax_add_repository()
  {
    if (!$this->verify_ajax_nonce()) {
      wp_send_json_error('Security check failed');
    }

    $repo_data = $this->sanitize_repository_data($_POST);

    // Validate required fields
    if (empty($repo_data['repoID']) || empty($repo_data['reponame'])) {
      wp_send_json_error('Repository ID and name are required');
    }

    // Check if repository already exists
    if ($this->repository_exists($repo_data['repoID'], $repo_data['gedcom'])) {
      wp_send_json_error('Repository ID already exists');
    }

    // Create address record if needed
    $address_id = $this->create_address_record($repo_data);

    // Create repository record
    $repo_id = $this->create_repository_record($repo_data, $address_id);

    if ($repo_id) {
      wp_send_json_success(array(
        'message' => __('Repository added successfully', 'heritagepress'),
        'repoID' => $repo_data['repoID'],
        'gedcom' => $repo_data['gedcom']
      ));
    } else {
      wp_send_json_error('Failed to add repository');
    }
  }

  /**
   * AJAX: Update repository
   */
  public function ajax_update_repository()
  {
    if (!$this->verify_ajax_nonce()) {
      wp_send_json_error('Security check failed');
    }

    $repo_data = $this->sanitize_repository_data($_POST);

    // Validate required fields
    if (empty($repo_data['reponame'])) {
      wp_send_json_error('Repository name is required');
    }

    // Handle address update/creation
    if (!empty($repo_data['addressID'])) {
      $this->update_address_record($repo_data['addressID'], $repo_data);
    } else if ($this->has_address_data($repo_data)) {
      $repo_data['addressID'] = $this->create_address_record($repo_data);
    }

    // Update repository
    if ($this->update_repository_record($repo_data)) {
      wp_send_json_success(array(
        'message' => __('Repository updated successfully', 'heritagepress')
      ));
    } else {
      wp_send_json_error('Failed to update repository');
    }
  }

  /**
   * AJAX: Delete repository
   */
  public function ajax_delete_repository()
  {
    if (!$this->verify_ajax_nonce()) {
      wp_send_json_error('Security check failed');
    }

    $repo_id = sanitize_text_field($_POST['repoID']);
    $gedcom = sanitize_text_field($_POST['gedcom']);

    if ($this->delete_repository($repo_id, $gedcom)) {
      wp_send_json_success(array(
        'message' => __('Repository deleted successfully', 'heritagepress')
      ));
    } else {
      wp_send_json_error('Failed to delete repository');
    }
  }

  /**
   * AJAX: Search repositories
   */
  public function ajax_search_repositories()
  {
    if (!$this->verify_ajax_nonce()) {
      wp_send_json_error('Security check failed');
    }

    $search_term = sanitize_text_field($_POST['search_term'] ?? '');
    $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');
    $limit = intval($_POST['limit'] ?? 20);

    $repositories = $this->search_repositories($search_term, $gedcom, $limit);

    wp_send_json_success(array('repositories' => $repositories));
  }

  /**
   * AJAX: Get repository data
   */
  public function ajax_get_repository()
  {
    if (!$this->verify_ajax_nonce()) {
      wp_send_json_error('Security check failed');
    }

    $repo_id = sanitize_text_field($_POST['repoID']);
    $gedcom = sanitize_text_field($_POST['gedcom']);

    $repository = $this->get_repository($repo_id, $gedcom);

    if ($repository) {
      wp_send_json_success(array('repository' => $repository));
    } else {
      wp_send_json_error('Repository not found');
    }
  }

  /**
   * AJAX: Generate repository ID
   */
  public function ajax_generate_repo_id()
  {
    if (!$this->verify_ajax_nonce()) {
      wp_send_json_error('Security check failed');
    }

    $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');
    $repo_id = $this->generate_repository_id($gedcom);

    wp_send_json_success(array('repoID' => $repo_id));
  }

  /**
   * AJAX: Check if repository ID exists
   */
  public function ajax_check_repo_id()
  {
    if (!$this->verify_ajax_nonce()) {
      wp_send_json_error('Security check failed');
    }

    $repo_id = sanitize_text_field($_POST['repoID']);
    $gedcom = sanitize_text_field($_POST['gedcom']);

    $exists = $this->repository_exists($repo_id, $gedcom);

    wp_send_json_success(array(
      'exists' => $exists,
      'message' => $exists ? __('Repository ID already exists', 'heritagepress') : __('Repository ID is available', 'heritagepress')
    ));
  }

  /**
   * AJAX handler for merging two repositories
   * Expects: source_repo_id (to be merged/deleted), target_repo_id (to keep)
   */
  public function ajax_merge_repositories()
  {
    check_ajax_referer('heritagepress_admin', 'nonce');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(__('You do not have sufficient permissions.', 'heritagepress'));
    }
    global $wpdb;
    $source_repo_id = sanitize_text_field($_POST['source_repo_id'] ?? '');
    $target_repo_id = sanitize_text_field($_POST['target_repo_id'] ?? '');
    $tree = sanitize_text_field($_POST['tree'] ?? '');
    if (!$source_repo_id || !$target_repo_id || $source_repo_id === $target_repo_id) {
      wp_send_json_error(__('Invalid repository selection.', 'heritagepress'));
    }
    $repositories_table = $wpdb->prefix . 'hp_repositories';
    $sources_table = $wpdb->prefix . 'hp_sources';
    $notelinks_table = $wpdb->prefix . 'hp_notelinks';
    $address_table = $wpdb->prefix . 'hp_addresses';
    // Merge address if target is missing
    $target_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $repositories_table WHERE repoID = %s AND gedcom = %s", $target_repo_id, $tree), ARRAY_A);
    $source_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $repositories_table WHERE repoID = %s AND gedcom = %s", $source_repo_id, $tree), ARRAY_A);
    if (!$target_row || !$source_row) {
      wp_send_json_error(__('Could not find both repositories.', 'heritagepress'));
    }
    $update = [];
    $fields = ['reponame', 'addressID', 'changedate', 'changedby', 'notes'];
    foreach ($fields as $field) {
      if (empty($target_row[$field]) && !empty($source_row[$field])) {
        $update[$field] = $source_row[$field];
      }
    }
    if ($update) {
      $wpdb->update($repositories_table, $update, ['repoID' => $target_repo_id, 'gedcom' => $tree]);
    }
    // Merge address if needed
    if (!empty($source_row['addressID']) && (empty($target_row['addressID']) || $target_row['addressID'] === $source_row['addressID'])) {
      $address = $wpdb->get_row($wpdb->prepare("SELECT * FROM $address_table WHERE addressID = %s", $source_row['addressID']), ARRAY_A);
      if ($address) {
        $wpdb->update($address_table, $address, ['addressID' => $target_row['addressID']]);
      }
    }
    // Update all sources to point to the kept repo
    $wpdb->query($wpdb->prepare("UPDATE $sources_table SET repoID = %s WHERE repoID = %s AND gedcom = %s", $target_repo_id, $source_repo_id, $tree));
    // Update notelinks
    $wpdb->query($wpdb->prepare("UPDATE $notelinks_table SET persfamID = %s WHERE persfamID = %s AND gedcom = %s", $target_repo_id, $source_repo_id, $tree));
    // Delete the merged (source) repository
    $wpdb->delete($repositories_table, array('repoID' => $source_repo_id, 'gedcom' => $tree));
    wp_send_json_success(['message' => __('Repositories merged successfully.', 'heritagepress')]);
  }

  /**
   * Get repository data with address information
   */
  public function get_repository($repo_id, $gedcom)
  {
    global $wpdb;

    $repositories_table = $wpdb->prefix . 'hp_repositories';
    $address_table = $wpdb->prefix . 'hp_addresses';

    $query = $wpdb->prepare("
      SELECT r.*,
             a.address1, a.address2, a.city, a.state, a.zip, a.country, a.phone, a.email, a.www,
             DATE_FORMAT(r.changedate, '%%d %%b %%Y %%H:%%i:%%s') as formatted_date
      FROM $repositories_table r
      LEFT JOIN $address_table a ON r.addressID = a.addressID
      WHERE r.repoID = %s AND r.gedcom = %s
    ", $repo_id, $gedcom);

    return $wpdb->get_row($query, ARRAY_A);
  }

  /**
   * Search repositories
   */
  public function search_repositories($search_term = '', $gedcom = '', $limit = 20, $offset = 0)
  {
    global $wpdb;

    $repositories_table = $wpdb->prefix . 'hp_repositories';
    $address_table = $wpdb->prefix . 'hp_addresses';

    $where_conditions = array('1=1');
    $query_params = array();

    // Add gedcom filter
    if (!empty($gedcom)) {
      $where_conditions[] = 'r.gedcom = %s';
      $query_params[] = $gedcom;
    }

    // Add search term filter
    if (!empty($search_term)) {
      $where_conditions[] = '(r.repoID LIKE %s OR r.reponame LIKE %s)';
      $search_like = '%' . $wpdb->esc_like($search_term) . '%';
      $query_params[] = $search_like;
      $query_params[] = $search_like;
    }

    $where_clause = implode(' AND ', $where_conditions);

    $query = "
      SELECT r.*,
             a.address1, a.city, a.state, a.country,
             DATE_FORMAT(r.changedate, '%%d %%b %%Y') as formatted_date
      FROM $repositories_table r
      LEFT JOIN $address_table a ON r.addressID = a.addressID
      WHERE $where_clause
      ORDER BY r.reponame
      LIMIT %d OFFSET %d
    ";

    $query_params[] = $limit;
    $query_params[] = $offset;

    $prepared_query = $wpdb->prepare($query, $query_params);

    return $wpdb->get_results($prepared_query, ARRAY_A);
  }

  /**
   * Get repository count for pagination
   */
  public function get_repositories_count($search_term = '', $gedcom = '')
  {
    global $wpdb;

    $repositories_table = $wpdb->prefix . 'hp_repositories';

    $where_conditions = array('1=1');
    $query_params = array();

    if (!empty($gedcom)) {
      $where_conditions[] = 'gedcom = %s';
      $query_params[] = $gedcom;
    }

    if (!empty($search_term)) {
      $where_conditions[] = '(repoID LIKE %s OR reponame LIKE %s)';
      $search_like = '%' . $wpdb->esc_like($search_term) . '%';
      $query_params[] = $search_like;
      $query_params[] = $search_like;
    }

    $where_clause = implode(' AND ', $where_conditions);

    $query = "SELECT COUNT(*) FROM $repositories_table WHERE $where_clause";

    if (!empty($query_params)) {
      return $wpdb->get_var($wpdb->prepare($query, $query_params));
    } else {
      return $wpdb->get_var($query);
    }
  }

  /**
   * Check if repository exists
   */
  private function repository_exists($repo_id, $gedcom)
  {
    global $wpdb;

    $repositories_table = $wpdb->prefix . 'hp_repositories';

    $count = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $repositories_table WHERE repoID = %s AND gedcom = %s",
      $repo_id,
      $gedcom
    ));

    return $count > 0;
  }

  /**
   * Create repository record
   */
  private function create_repository_record($repo_data, $address_id = 0)
  {
    global $wpdb;

    $repositories_table = $wpdb->prefix . 'hp_repositories';

    $data = array(
      'repoID' => $repo_data['repoID'],
      'reponame' => $repo_data['reponame'],
      'gedcom' => $repo_data['gedcom'],
      'addressID' => $address_id,
      'changedate' => current_time('mysql'),
      'changedby' => wp_get_current_user()->user_login
    );

    $result = $wpdb->insert($repositories_table, $data);

    return $result ? $wpdb->insert_id : false;
  }

  /**
   * Update repository record
   */
  private function update_repository_record($repo_data)
  {
    global $wpdb;

    $repositories_table = $wpdb->prefix . 'hp_repositories';

    $data = array(
      'reponame' => $repo_data['reponame'],
      'addressID' => $repo_data['addressID'] ?? 0,
      'changedate' => current_time('mysql'),
      'changedby' => wp_get_current_user()->user_login
    );

    $where = array(
      'repoID' => $repo_data['repoID'],
      'gedcom' => $repo_data['gedcom']
    );

    return $wpdb->update($repositories_table, $data, $where);
  }

  /**
   * Delete repository and related records
   */
  private function delete_repository($repo_id, $gedcom)
  {
    global $wpdb;

    $repositories_table = $wpdb->prefix . 'hp_repositories';

    // Get repository data first
    $repository = $this->get_repository($repo_id, $gedcom);

    if (!$repository) {
      return false;
    }

    // Delete repository record
    $result = $wpdb->delete(
      $repositories_table,
      array('repoID' => $repo_id, 'gedcom' => $gedcom),
      array('%s', '%s')
    );

    if ($result && !empty($repository['addressID'])) {
      // Delete associated address record
      $address_table = $wpdb->prefix . 'hp_addresses';
      $wpdb->delete(
        $address_table,
        array('addressID' => $repository['addressID']),
        array('%d')
      );
    }

    return $result !== false;
  }

  /**
   * Create address record if address data provided
   */
  private function create_address_record($repo_data)
  {
    if (!$this->has_address_data($repo_data)) {
      return 0;
    }

    global $wpdb;

    $address_table = $wpdb->prefix . 'hp_addresses';

    $address_data = array(
      'address1' => $repo_data['address1'] ?? '',
      'address2' => $repo_data['address2'] ?? '',
      'city' => $repo_data['city'] ?? '',
      'state' => $repo_data['state'] ?? '',
      'zip' => $repo_data['zip'] ?? '',
      'country' => $repo_data['country'] ?? '',
      'phone' => $repo_data['phone'] ?? '',
      'email' => $repo_data['email'] ?? '',
      'www' => $repo_data['www'] ?? '',
      'gedcom' => $repo_data['gedcom']
    );

    $result = $wpdb->insert($address_table, $address_data);

    return $result ? $wpdb->insert_id : 0;
  }

  /**
   * Update address record
   */
  private function update_address_record($address_id, $repo_data)
  {
    global $wpdb;

    $address_table = $wpdb->prefix . 'hp_addresses';

    $address_data = array(
      'address1' => $repo_data['address1'] ?? '',
      'address2' => $repo_data['address2'] ?? '',
      'city' => $repo_data['city'] ?? '',
      'state' => $repo_data['state'] ?? '',
      'zip' => $repo_data['zip'] ?? '',
      'country' => $repo_data['country'] ?? '',
      'phone' => $repo_data['phone'] ?? '',
      'email' => $repo_data['email'] ?? '',
      'www' => $repo_data['www'] ?? ''
    );

    return $wpdb->update(
      $address_table,
      $address_data,
      array('addressID' => $address_id),
      null,
      array('%d')
    );
  }

  /**
   * Check if repository data contains address information
   */
  private function has_address_data($repo_data)
  {
    $address_fields = array('address1', 'address2', 'city', 'state', 'zip', 'country', 'phone', 'email', 'www');

    foreach ($address_fields as $field) {
      if (!empty($repo_data[$field])) {
        return true;
      }
    }

    return false;
  }

  /**
   * Generate unique repository ID
   */
  private function generate_repository_id($gedcom)
  {
    global $wpdb;

    $repositories_table = $wpdb->prefix . 'hp_repositories';

    // Get the highest existing repository ID number
    $query = $wpdb->prepare("
      SELECT repoID
      FROM $repositories_table
      WHERE gedcom = %s
      ORDER BY CAST(REPLACE(repoID, 'R', '') AS UNSIGNED) DESC
      LIMIT 1
    ", $gedcom);

    $last_repo = $wpdb->get_var($query);

    if ($last_repo) {
      // Extract number and increment
      $number = intval(str_replace('R', '', $last_repo));
      $new_number = $number + 1;
    } else {
      $new_number = 1;
    }

    return 'R' . $new_number;
  }

  /**
   * Sanitize repository form data
   */
  private function sanitize_repository_data($data)
  {
    return array(
      'repoID' => strtoupper(sanitize_text_field($data['repoID'] ?? '')),
      'reponame' => sanitize_text_field($data['reponame'] ?? ''),
      'gedcom' => sanitize_text_field($data['gedcom'] ?? ''),
      'addressID' => intval($data['addressID'] ?? 0),
      'address1' => sanitize_text_field($data['address1'] ?? ''),
      'address2' => sanitize_text_field($data['address2'] ?? ''),
      'city' => sanitize_text_field($data['city'] ?? ''),
      'state' => sanitize_text_field($data['state'] ?? ''),
      'zip' => sanitize_text_field($data['zip'] ?? ''),
      'country' => sanitize_text_field($data['country'] ?? ''),
      'phone' => sanitize_text_field($data['phone'] ?? ''),
      'email' => sanitize_email($data['email'] ?? ''),
      'www' => esc_url_raw($data['www'] ?? '')
    );
  }

  /**
   * Verify AJAX nonce
   */
  private function verify_ajax_nonce()
  {
    return wp_verify_nonce($_POST['nonce'] ?? '', 'hp_repository_nonce');
  }

  /**
   * Add admin notice
   */
  private function add_admin_notice($message, $type = 'info')
  {
    add_action('admin_notices', function () use ($message, $type) {
      echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible">';
      echo '<p>' . esc_html($message) . '</p>';
      echo '</div>';
    });
  }

  /**
   * Log admin action (placeholder for admin logging)
   */
  private function log_admin_action($action, $repo_id, $gedcom)
  {
    // This would integrate with the admin logging system
    // For now, just a placeholder
    error_log("HeritagePress: Repository action '$action' for $gedcom/$repo_id by " . wp_get_current_user()->user_login);
  }

  /**
   * Get available trees for repository selection
   */
  public function get_available_trees()
  {
    global $wpdb;

    $trees_table = $wpdb->prefix . 'hp_trees';

    return $wpdb->get_results(
      "SELECT gedcom, treename FROM $trees_table ORDER BY treename",
      ARRAY_A
    );
  }
}
