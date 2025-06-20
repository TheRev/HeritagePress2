<?php

/**
 * Source Controller
 *
 * Complete replication of source management functionality:
 * - admin_sources.php (search/list sources)
 * - admin_newsource.php (add new source form)
 * - admin_addsource.php (process source creation)
 * - admin_editsource.php (edit source form)
 * - admin_updatesource.php (process source updates)
 * - admin_mergesources.php (merge sources functionality)
 *
 * @package HeritagePress
 * @subpackage Controllers
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

require_once plugin_dir_path(__FILE__) . '../../includes/controllers/class-hp-base-controller.php';

class HP_Source_Controller extends HP_Base_Controller
{
  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct('sources');
    $this->capabilities = array(
      'manage_sources' => 'manage_genealogy',
      'edit_sources' => 'edit_genealogy',
      'delete_sources' => 'delete_genealogy'
    );
  }

  /**
   * Initialize the source controller
   */
  public function init()
  {
    parent::init();
    // Source-specific initialization
  }

  /**
   * Register hooks for source management
   */
  public function register_hooks()
  {
    parent::register_hooks();

    // AJAX handlers for sources
    add_action('wp_ajax_hp_add_source', array($this, 'ajax_add_source'));
    add_action('wp_ajax_hp_update_source', array($this, 'ajax_update_source'));
    add_action('wp_ajax_hp_delete_source', array($this, 'ajax_delete_source'));
    add_action('wp_ajax_hp_search_sources', array($this, 'ajax_search_sources'));
    add_action('wp_ajax_hp_generate_source_id', array($this, 'ajax_generate_source_id'));
    add_action('wp_ajax_hp_check_source_id', array($this, 'ajax_check_source_id'));
    add_action('wp_ajax_hp_merge_sources', array($this, 'ajax_merge_sources'));
  }

  /**
   * Handle source management actions based on current tab
   */
  public function handle_source_actions($current_tab)
  {
    if (!$this->check_capability('edit_genealogy')) {
      return;
    }

    // Handle form submissions
    if (isset($_POST['action'])) {
      switch ($_POST['action']) {
        case 'add_source':
          $this->handle_add_source();
          break;
        case 'update_source':
          $this->handle_update_source();
          break;
        case 'delete_source':
          $this->handle_delete_source();
          break;
        case 'bulk_action':
          $this->handle_bulk_source_actions();
          break;
        case 'merge_sources':
          $this->handle_merge_sources();
          break;
      }
    }
  }

  /**
   * Handle form submission - delegates to individual action handlers
   */
  public function handle_form_submission()
  {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
      $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'browse';
      $this->handle_source_actions($current_tab);
    }
  }

  /**
   * Display the source management page
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

    switch ($current_tab) {
      case 'add':
        $this->load_view('sources-add');
        break;
      case 'edit':
        $this->load_view('sources-edit');
        break;
      case 'merge':
        $this->load_view('sources-merge');
        break;
      default:
        $this->load_view('sources-main');
        break;
    }
  }

  /**
   * Handle adding a new source
   */
  private function handle_add_source()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!$this->check_capability('edit_genealogy')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }    // Sanitize form data (exact field names for compatibility)
    $gedcom = sanitize_text_field($_POST['gedcom'] ?? $_POST['tree1'] ?? '');
    $sourceID = strtoupper(sanitize_text_field($_POST['sourceID'] ?? ''));
    $shorttitle = sanitize_text_field($_POST['shorttitle'] ?? '');
    $title = sanitize_text_field($_POST['title'] ?? '');
    $author = sanitize_text_field($_POST['author'] ?? '');
    $callnum = sanitize_text_field($_POST['callnum'] ?? $_POST['callnumber'] ?? '');
    $publisher = sanitize_text_field($_POST['publisher'] ?? $_POST['publisherinfo'] ?? '');
    $repoID = sanitize_text_field($_POST['repoID'] ?? $_POST['repositoryID'] ?? '');
    $actualtext = sanitize_textarea_field($_POST['actualtext'] ?? $_POST['sourcetext'] ?? '');

    // Handle character encoding properly
    if (!mb_check_encoding($shorttitle, 'UTF-8')) {
      $shorttitle = mb_convert_encoding($shorttitle, 'UTF-8');
    }
    if (!mb_check_encoding($title, 'UTF-8')) {
      $title = mb_convert_encoding($title, 'UTF-8');
    }
    if (!mb_check_encoding($author, 'UTF-8')) {
      $author = mb_convert_encoding($author, 'UTF-8');
    }
    if (!mb_check_encoding($callnum, 'UTF-8')) {
      $callnum = mb_convert_encoding($callnum, 'UTF-8');
    }
    if (!mb_check_encoding($publisher, 'UTF-8')) {
      $publisher = mb_convert_encoding($publisher, 'UTF-8');
    }
    if (!mb_check_encoding($actualtext, 'UTF-8')) {
      $actualtext = mb_convert_encoding($actualtext, 'UTF-8');
    }    // Validate required fields
    if (empty($gedcom)) {
      $this->add_notice(__('Tree is required.', 'heritagepress'), 'error');
      return;
    }    // Handle empty repoID as needed (set to 0 if empty)
    if (empty($repoID)) {
      $repoID = 0;
    }

    // Auto-generate sourceID if not provided (as needed)
    if (empty($sourceID)) {
      $sourceID = $this->generate_source_id($gedcom);
    }

    // Ensure sourceID is uppercase (standard style)
    $sourceID = strtoupper($sourceID);

    // Check if source ID already exists
    if ($this->source_id_exists($sourceID, $gedcom)) {
      $this->add_notice(__('Source ID already exists in this tree.', 'heritagepress'), 'error');
      return;
    }

    // Add the source
    $result = $this->add_source(array(
      'gedcom' => $gedcom,
      'sourceID' => $sourceID,
      'shorttitle' => $shorttitle,
      'title' => $title,
      'author' => $author,
      'callnum' => $callnum,
      'publisher' => $publisher,
      'repoID' => $repoID,
      'actualtext' => $actualtext
    ));
    if ($result) {
      // Log the addition (Heritage Press style admin logging)
      $this->write_admin_log("Add new source: $gedcom/$sourceID", $sourceID, $gedcom);

      $this->add_notice(__('Source added successfully!', 'heritagepress'), 'success');
      // Redirect to edit page with added=1 parameter (as needed)
      wp_redirect(admin_url('admin.php?page=heritagepress-sources&tab=edit&sourceID=' . $sourceID . '&tree=' . $gedcom . '&added=1'));
      exit;
    } else {
      $this->add_notice(__('Failed to add source. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle updating a source
   */
  private function handle_update_source()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!$this->check_capability('edit_genealogy')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    $sourceID = sanitize_text_field($_POST['sourceID'] ?? '');
    $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');
    $shorttitle = sanitize_text_field($_POST['shorttitle'] ?? '');
    $title = sanitize_text_field($_POST['title'] ?? '');
    $author = sanitize_text_field($_POST['author'] ?? '');
    $callnum = sanitize_text_field($_POST['callnum'] ?? '');
    $publisher = sanitize_text_field($_POST['publisher'] ?? '');
    $repoID = sanitize_text_field($_POST['repoID'] ?? '');
    $actualtext = sanitize_textarea_field($_POST['actualtext'] ?? '');

    $result = $this->update_source($sourceID, $gedcom, array(
      'shorttitle' => $shorttitle,
      'title' => $title,
      'author' => $author,
      'callnum' => $callnum,
      'publisher' => $publisher,
      'repoID' => $repoID,
      'actualtext' => $actualtext
    ));

    if ($result) {
      $this->add_notice(__('Source updated successfully!', 'heritagepress'), 'success');
    } else {
      $this->add_notice(__('Failed to update source. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle deleting a source
   */
  private function handle_delete_source()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!$this->check_capability('delete_genealogy')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    $sourceID = sanitize_text_field($_POST['sourceID'] ?? '');
    $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');

    // Check for citations that reference this source
    $citations_count = $this->get_source_citations_count($sourceID, $gedcom);
    if ($citations_count > 0) {
      $this->add_notice(
        sprintf(__('Cannot delete source. It is referenced by %d citation(s). Please remove or reassign citations first.', 'heritagepress'), $citations_count),
        'error'
      );
      return;
    }

    $result = $this->delete_source($sourceID, $gedcom);

    if ($result) {
      $this->add_notice(__('Source deleted successfully!', 'heritagepress'), 'success');
      // Redirect to main sources page
      wp_redirect(admin_url('admin.php?page=heritagepress-sources'));
      exit;
    } else {
      $this->add_notice(__('Failed to delete source. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle bulk source actions
   */
  private function handle_bulk_source_actions()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    $action = sanitize_text_field($_POST['bulk_action'] ?? '');
    $selected_sources = array_map('sanitize_text_field', $_POST['selected_sources'] ?? array());

    if (empty($selected_sources)) {
      $this->add_notice(__('No sources selected.', 'heritagepress'), 'error');
      return;
    }

    switch ($action) {
      case 'delete':
        if (!$this->check_capability('delete_genealogy')) {
          $this->add_notice(__('Insufficient permissions for bulk delete.', 'heritagepress'), 'error');
          return;
        }
        $result = $this->handle_bulk_action($action, $selected_sources, array($this, 'delete_source'));
        break;
      default:
        $this->add_notice(__('Invalid bulk action.', 'heritagepress'), 'error');
        return;
    }

    if ($result['success'] > 0) {
      $this->add_notice(
        sprintf(__('%d sources processed successfully.', 'heritagepress'), $result['success']),
        'success'
      );
    }
  }

  /**
   * Handle merging sources
   */
  private function handle_merge_sources()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!$this->check_capability('edit_genealogy')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    $primary_sourceID = sanitize_text_field($_POST['primary_sourceID'] ?? '');
    $merge_sourceID = sanitize_text_field($_POST['merge_sourceID'] ?? '');
    $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');

    if (empty($primary_sourceID) || empty($merge_sourceID) || empty($gedcom)) {
      $this->add_notice(__('Please specify both sources to merge.', 'heritagepress'), 'error');
      return;
    }

    if ($primary_sourceID === $merge_sourceID) {
      $this->add_notice(__('Cannot merge a source with itself.', 'heritagepress'), 'error');
      return;
    }

    $result = $this->merge_sources($primary_sourceID, $merge_sourceID, $gedcom);

    if ($result) {
      $this->add_notice(__('Sources merged successfully!', 'heritagepress'), 'success');
    } else {
      $this->add_notice(__('Failed to merge sources. Please try again.', 'heritagepress'), 'error');
    }
  }
  /**
   * Add a new source to the database (compatible)
   */
  private function add_source($data)
  {
    global $wpdb;

    $sources_table = $wpdb->prefix . 'hp_sources';

    // Prepare data exactly as needed schema
    $source_data = array(
      'gedcom' => $data['gedcom'],
      'sourceID' => $data['sourceID'],
      'shorttitle' => $data['shorttitle'],
      'title' => $data['title'],
      'author' => $data['author'],
      'callnum' => $data['callnum'],
      'publisher' => $data['publisher'],
      'repoID' => !empty($data['repoID']) ? intval($data['repoID']) : 0, // Default sets 0 for empty
      'actualtext' => $data['actualtext'],
      'changedate' => current_time('mysql'),
      'changedby' => wp_get_current_user()->user_login,
      'type' => '', // Default sets empty by default
      'other' => '', // Default sets empty by default
      'comments' => '' // Default sets empty by default
    );

    $result = $wpdb->insert($sources_table, $source_data);

    return $result !== false;
  }

  /**
   * Update an existing source
   */
  private function update_source($sourceID, $gedcom, $data)
  {
    global $wpdb;

    $sources_table = $wpdb->prefix . 'hp_sources';

    $update_data = array(
      'shorttitle' => $data['shorttitle'],
      'title' => $data['title'],
      'author' => $data['author'],
      'callnum' => $data['callnum'],
      'publisher' => $data['publisher'],
      'repoID' => !empty($data['repoID']) ? $data['repoID'] : '',
      'actualtext' => $data['actualtext'],
      'changedate' => current_time('mysql'),
      'changedby' => wp_get_current_user()->user_login
    );

    $result = $wpdb->update(
      $sources_table,
      $update_data,
      array('sourceID' => $sourceID, 'gedcom' => $gedcom)
    );

    return $result !== false;
  }

  /**
   * Delete a source
   */
  private function delete_source($sourceID, $gedcom)
  {
    global $wpdb;

    $sources_table = $wpdb->prefix . 'hp_sources';

    $result = $wpdb->delete(
      $sources_table,
      array('sourceID' => $sourceID, 'gedcom' => $gedcom)
    );

    return $result !== false;
  }

  /**
   * Merge two sources (move all citations from merge source to primary source)
   */
  private function merge_sources($primary_sourceID, $merge_sourceID, $gedcom)
  {
    global $wpdb;

    $citations_table = $wpdb->prefix . 'hp_citations';
    $sources_table = $wpdb->prefix . 'hp_sources';

    // Start transaction
    $wpdb->query('START TRANSACTION');

    try {
      // Update all citations to reference the primary source
      $citations_updated = $wpdb->update(
        $citations_table,
        array('sourceID' => $primary_sourceID),
        array('sourceID' => $merge_sourceID, 'gedcom' => $gedcom)
      );

      // Delete the merged source
      $source_deleted = $wpdb->delete(
        $sources_table,
        array('sourceID' => $merge_sourceID, 'gedcom' => $gedcom)
      );

      if ($source_deleted !== false) {
        $wpdb->query('COMMIT');
        return true;
      } else {
        $wpdb->query('ROLLBACK');
        return false;
      }
    } catch (Exception $e) {
      $wpdb->query('ROLLBACK');
      return false;
    }
  }

  /**
   * Check if source ID exists
   */
  private function source_id_exists($sourceID, $gedcom)
  {
    global $wpdb;

    $sources_table = $wpdb->prefix . 'hp_sources';

    $count = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $sources_table WHERE sourceID = %s AND gedcom = %s",
      $sourceID,
      $gedcom
    ));

    return $count > 0;
  }

  /**
   * Get count of citations for a source
   */
  private function get_source_citations_count($sourceID, $gedcom)
  {
    global $wpdb;

    $citations_table = $wpdb->prefix . 'hp_citations';

    $count = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $citations_table WHERE sourceID = %s AND gedcom = %s",
      $sourceID,
      $gedcom
    ));

    return intval($count);
  }

  /**
   * Get a single source by ID
   */
  public function get_source($sourceID, $gedcom)
  {
    global $wpdb;

    $sources_table = $wpdb->prefix . 'hp_sources';

    $source = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM $sources_table WHERE sourceID = %s AND gedcom = %s",
      $sourceID,
      $gedcom
    ), ARRAY_A);

    return $source;
  }

  /**
   * AJAX: Add source
   */
  public function ajax_add_source()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('edit_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');
    $sourceID = strtoupper(sanitize_text_field($_POST['sourceID'] ?? ''));
    $shorttitle = sanitize_text_field($_POST['shorttitle'] ?? '');
    $title = sanitize_text_field($_POST['title'] ?? '');
    $author = sanitize_text_field($_POST['author'] ?? '');
    $callnum = sanitize_text_field($_POST['callnum'] ?? '');
    $publisher = sanitize_text_field($_POST['publisher'] ?? '');
    $repoID = sanitize_text_field($_POST['repoID'] ?? '');
    $actualtext = sanitize_textarea_field($_POST['actualtext'] ?? '');

    // Validate required fields
    if (empty($gedcom) || empty($sourceID)) {
      wp_send_json_error('Tree and Source ID are required');
    }

    // Check if source ID already exists
    if ($this->source_id_exists($sourceID, $gedcom)) {
      wp_send_json_error('Source ID already exists in this tree');
    }

    $result = $this->add_source(array(
      'gedcom' => $gedcom,
      'sourceID' => $sourceID,
      'shorttitle' => $shorttitle,
      'title' => $title,
      'author' => $author,
      'callnum' => $callnum,
      'publisher' => $publisher,
      'repoID' => $repoID,
      'actualtext' => $actualtext
    ));

    if ($result) {
      wp_send_json_success(array(
        'message' => 'Source added successfully',
        'sourceID' => $sourceID,
        'redirect' => admin_url('admin.php?page=heritagepress-sources&tab=edit&sourceID=' . $sourceID . '&tree=' . $gedcom)
      ));
    } else {
      wp_send_json_error('Failed to add source');
    }
  }

  /**
   * AJAX: Update source
   */
  public function ajax_update_source()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('edit_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    $sourceID = sanitize_text_field($_POST['sourceID'] ?? '');
    $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');
    $shorttitle = sanitize_text_field($_POST['shorttitle'] ?? '');
    $title = sanitize_text_field($_POST['title'] ?? '');
    $author = sanitize_text_field($_POST['author'] ?? '');
    $callnum = sanitize_text_field($_POST['callnum'] ?? '');
    $publisher = sanitize_text_field($_POST['publisher'] ?? '');
    $repoID = sanitize_text_field($_POST['repoID'] ?? '');
    $actualtext = sanitize_textarea_field($_POST['actualtext'] ?? '');

    $result = $this->update_source($sourceID, $gedcom, array(
      'shorttitle' => $shorttitle,
      'title' => $title,
      'author' => $author,
      'callnum' => $callnum,
      'publisher' => $publisher,
      'repoID' => $repoID,
      'actualtext' => $actualtext
    ));

    if ($result) {
      wp_send_json_success(array(
        'message' => 'Source updated successfully'
      ));
    } else {
      wp_send_json_error('Failed to update source');
    }
  }

  /**
   * AJAX: Delete source
   */
  public function ajax_delete_source()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('delete_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    $sourceID = sanitize_text_field($_POST['sourceID'] ?? '');
    $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');

    // Check for citations
    $citations_count = $this->get_source_citations_count($sourceID, $gedcom);
    if ($citations_count > 0) {
      wp_send_json_error("Cannot delete source. It is referenced by $citations_count citation(s).");
    }

    $result = $this->delete_source($sourceID, $gedcom);

    if ($result) {
      wp_send_json_success(array(
        'message' => 'Source deleted successfully',
        'redirect' => admin_url('admin.php?page=heritagepress-sources')
      ));
    } else {
      wp_send_json_error('Failed to delete source');
    }
  }

  /**
   * AJAX: Search sources
   */
  public function ajax_search_sources()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    $search_term = sanitize_text_field($_POST['search'] ?? '');
    $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');
    $page = intval($_POST['page'] ?? 1);
    $per_page = 20;

    global $wpdb;

    $sources_table = $wpdb->prefix . 'hp_sources';

    $where_conditions = array('gedcom = %s');
    $query_params = array($gedcom);

    if (!empty($search_term)) {
      $where_conditions[] = "(sourceID LIKE %s OR title LIKE %s OR shorttitle LIKE %s OR author LIKE %s OR publisher LIKE %s)";
      $search_like = '%' . $wpdb->esc_like($search_term) . '%';
      $query_params[] = $search_like;
      $query_params[] = $search_like;
      $query_params[] = $search_like;
      $query_params[] = $search_like;
      $query_params[] = $search_like;
    }

    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    $offset = ($page - 1) * $per_page;

    // Get total count
    $count_query = "SELECT COUNT(*) FROM $sources_table $where_clause";
    $total = $wpdb->get_var($wpdb->prepare($count_query, $query_params));

    // Get sources
    $sources_query = "
      SELECT sourceID, title, shorttitle, author, publisher, changedate
      FROM $sources_table
      $where_clause
      ORDER BY sourceID
      LIMIT %d OFFSET %d
    ";
    $query_params[] = $per_page;
    $query_params[] = $offset;

    $sources = $wpdb->get_results($wpdb->prepare($sources_query, $query_params), ARRAY_A);

    wp_send_json_success(array(
      'sources' => $sources,
      'total' => $total,
      'page' => $page,
      'per_page' => $per_page,
      'total_pages' => ceil($total / $per_page)
    ));
  }

  /**
   * AJAX: Generate source ID
   */
  public function ajax_generate_source_id()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');

    if (empty($gedcom)) {
      wp_send_json_error('Tree is required');
    }

    global $wpdb;

    $sources_table = $wpdb->prefix . 'hp_sources';

    // Get the next available source ID (as needed)
    $max_id = $wpdb->get_var($wpdb->prepare(
      "SELECT MAX(CAST(SUBSTRING(sourceID, 2) AS UNSIGNED)) FROM $sources_table WHERE gedcom = %s AND sourceID REGEXP '^S[0-9]+$'",
      $gedcom
    ));

    $next_id = $max_id ? $max_id + 1 : 1;
    $sourceID = 'S' . $next_id;

    wp_send_json_success(array('sourceID' => $sourceID));
  }

  /**
   * AJAX: Check if source ID exists
   */
  public function ajax_check_source_id()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    $sourceID = sanitize_text_field($_POST['sourceID'] ?? '');
    $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');

    if (empty($sourceID) || empty($gedcom)) {
      wp_send_json_error('Source ID and tree are required');
    }

    $exists = $this->source_id_exists($sourceID, $gedcom);

    wp_send_json_success(array(
      'exists' => $exists,
      'message' => $exists ? 'Source ID already exists' : 'Source ID is available'
    ));
  }

  /**
   * AJAX: Merge sources
   */
  public function ajax_merge_sources()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('edit_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    $primary_sourceID = sanitize_text_field($_POST['primary_sourceID'] ?? '');
    $merge_sourceID = sanitize_text_field($_POST['merge_sourceID'] ?? '');
    $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');

    if (empty($primary_sourceID) || empty($merge_sourceID) || empty($gedcom)) {
      wp_send_json_error('Both sources and tree are required');
    }

    if ($primary_sourceID === $merge_sourceID) {
      wp_send_json_error('Cannot merge a source with itself');
    }

    $result = $this->merge_sources($primary_sourceID, $merge_sourceID, $gedcom);

    if ($result) {
      wp_send_json_success(array(
        'message' => 'Sources merged successfully'
      ));
    } else {
      wp_send_json_error('Failed to merge sources');
    }
  }

  /**
   * Generate a unique source ID for the tree (Heritage Press style)
   *
   * @param string $gedcom Tree identifier
   * @return string Generated source ID
   */
  private function generate_source_id($gedcom)
  {
    global $wpdb;
    $sources_table = $wpdb->prefix . 'hp_sources';

    // Start with S1 and increment until we find an available ID
    $counter = 1;
    do {
      $sourceID = 'S' . $counter;
      $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $sources_table WHERE sourceID = %s AND gedcom = %s",
        $sourceID,
        $gedcom
      ));
      $counter++;
    } while ($exists > 0);

    return $sourceID;
  }

  /**
   * Write admin log entry (Heritage Press style logging)
   *
   * @param string $message Log message
   * @param string $sourceID Source ID
   * @param string $gedcom Tree identifier
   */
  private function write_admin_log($message, $sourceID = '', $gedcom = '')
  {
    $user = wp_get_current_user();
    $user_name = $user->user_login ?? 'unknown';

    // Create admin log message with link as needed
    if (!empty($sourceID) && !empty($gedcom)) {
      $admin_url = admin_url("admin.php?page=heritagepress-sources&tab=edit&sourceID=$sourceID&tree=$gedcom");
      $log_message = "<a href=\"$admin_url\">$message</a>";
    } else {
      $log_message = $message;
    }

    // Log to WordPress error log
    error_log("HeritagePress Admin Log [$user_name]: $message");

    // Store in admin log table if it exists
    global $wpdb;
    $admin_log_table = $wpdb->prefix . 'hp_admin_log';

    // Check if admin log table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$admin_log_table'") === $admin_log_table) {
      $wpdb->insert(
        $admin_log_table,
        array(
          'user_login' => $user_name,
          'log_date' => current_time('mysql'),
          'log_message' => $log_message,
          'gedcom' => $gedcom
        )
      );
    }

    // Fire action hook for extensibility
    do_action('heritagepress_admin_log', $message, $sourceID, $gedcom, $user_name);
  }
}
