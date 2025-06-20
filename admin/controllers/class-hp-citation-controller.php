<?php

/**
 * Citation Controller
 *
 * Handles all citation management functionality including CRUD       operations, AJAX requests, and display logic.
 *
 * citation validation, and citation-related AJAX requests
 * * @package HeritagePress
 * @subpackage Controllers
 * @since 1.0.0
 *
 */

if (!defined('ABSPATH')) {
  exit;
}

require_once plugin_dir_path(__FILE__) . '../../includes/controllers/class-hp-base-controller.php';

class HP_Citation_Controller extends HP_Base_Controller
{
  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct('citations');
    $this->capabilities = array(
      'manage_citations' => 'manage_genealogy',
      'edit_citations' => 'edit_genealogy',
      'delete_citations' => 'delete_genealogy'
    );
  }

  /**
   * Initialize the citation controller
   */
  public function init()
  {
    parent::init();
    // Citation-specific initialization
  }

  /**
   * Register hooks for citation management
   */
  public function register_hooks()
  {
    parent::register_hooks();

    // AJAX handlers for citations
    add_action('wp_ajax_hp_add_citation', array($this, 'ajax_add_citation'));
    add_action('wp_ajax_hp_update_citation', array($this, 'ajax_update_citation'));
    add_action('wp_ajax_hp_delete_citation', array($this, 'ajax_delete_citation'));
    add_action('wp_ajax_hp_get_citations', array($this, 'ajax_get_citations'));
    add_action('wp_ajax_hp_search_citations', array($this, 'ajax_search_citations'));
    add_action('wp_ajax_hp_get_last_citation', array($this, 'ajax_get_last_citation'));

    // AJAX handlers for sources (needed for citation forms)
    add_action('wp_ajax_hp_search_sources', array($this, 'ajax_search_sources'));
    add_action('wp_ajax_hp_create_source', array($this, 'ajax_create_source'));

    // AJAX handlers for citations modal
    add_action('wp_ajax_hp_get_citations_modal', array($this, 'ajax_get_citations_modal'));
    add_action('wp_ajax_hp_load_add_citation_form', array($this, 'ajax_load_add_citation_form'));
    add_action('wp_ajax_hp_load_edit_citation_form', array($this, 'ajax_load_edit_citation_form'));
    add_action('wp_ajax_hp_update_citation_order', array($this, 'ajax_update_citation_order'));
  }

  /**
   * Handle AJAX requests
   */
  public function handle_ajax()
  {
    // All AJAX handlers are registered in register_hooks
    // This method can be used for additional AJAX processing if needed
  }

  /**
   * Enqueue assets for citation management
   */
  public function enqueue_assets()
  {
    // Citation-specific CSS and JS would be enqueued here
    // For now, using the main admin assets
  }

  /**
   * Handle citation page actions
   */
  public function handle_citation_actions($current_tab)
  {
    if (!$this->check_capability('edit_genealogy')) {
      return;
    }

    // Handle form submissions
    if (isset($_POST['action'])) {
      switch ($_POST['action']) {
        case 'add_citation':
          $this->handle_add_citation();
          break;
        case 'update_citation':
          $this->handle_update_citation();
          break;
        case 'delete_citation':
          $this->handle_delete_citation();
          break;
        case 'bulk_action':
          $this->handle_bulk_citation_actions();
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
      $this->handle_citation_actions($current_tab);
    }
  }

  /**
   * Display the citation management page
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

    // Check if we're editing a specific citation
    $citation_id = isset($_GET['citationID']) ? sanitize_text_field($_GET['citationID']) : '';
    $is_editing = !empty($citation_id);

    if ($is_editing) {
      $current_tab = 'edit';
    }

    // Load the appropriate view based on current page context
    $this->load_citation_view($current_tab, $citation_id);
  }

  /**
   * Load citation view based on tab
   */
  private function load_citation_view($tab, $citation_id = '')
  {
    switch ($tab) {
      case 'add':
        $this->load_view('citations-add');
        break;
      case 'edit':
        $this->load_view('citations-edit', array('citationID' => $citation_id));
        break;
      case 'browse':
      default:
        $this->load_view('citations-main');
        break;
    }
  }

  /**
   * Handle adding a new citation
   */
  public function handle_add_citation()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!$this->check_capability('edit_genealogy')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    // Sanitize form data (field names)
    $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');
    $persfamID = sanitize_text_field($_POST['persfamID'] ?? '');
    $eventID = sanitize_text_field($_POST['eventID'] ?? '');
    $sourceID = sanitize_text_field($_POST['sourceID'] ?? '');
    $description = sanitize_textarea_field($_POST['description'] ?? '');
    $page = sanitize_textarea_field($_POST['citepage'] ?? '');
    $quay = sanitize_text_field($_POST['quay'] ?? '');
    $citedate = sanitize_text_field($_POST['citedate'] ?? '');
    $citetext = sanitize_textarea_field($_POST['citetext'] ?? '');
    $note = sanitize_textarea_field($_POST['citenote'] ?? '');

    // Validate required fields
    if (empty($gedcom) || empty($persfamID)) {
      $this->add_notice(__('Tree and Person/Family are required.', 'heritagepress'), 'error');
      return;
    }

    // Add the citation
    $result = $this->add_citation(array(
      'gedcom' => $gedcom,
      'persfamID' => $persfamID,
      'eventID' => $eventID,
      'sourceID' => $sourceID,
      'description' => $description,
      'page' => $page,
      'quay' => $quay,
      'citedate' => $citedate,
      'citetext' => $citetext,
      'note' => $note
    ));

    if ($result) {
      $this->add_notice(__('Citation added successfully!', 'heritagepress'), 'success');
    } else {
      $this->add_notice(__('Failed to add citation. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle updating a citation
   */
  private function handle_update_citation()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!$this->check_capability('edit_genealogy')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    $citation_id = intval($_POST['citationID']);
    $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');
    $sourceID = sanitize_text_field($_POST['sourceID'] ?? '');
    $description = sanitize_textarea_field($_POST['description'] ?? '');
    $page = sanitize_textarea_field($_POST['citepage'] ?? '');
    $quay = sanitize_text_field($_POST['quay'] ?? '');
    $citedate = sanitize_text_field($_POST['citedate'] ?? '');
    $citetext = sanitize_textarea_field($_POST['citetext'] ?? '');
    $note = sanitize_textarea_field($_POST['citenote'] ?? '');

    $result = $this->update_citation($citation_id, array(
      'sourceID' => $sourceID,
      'description' => $description,
      'page' => $page,
      'quay' => $quay,
      'citedate' => $citedate,
      'citedatetr' => $citedatetr,
      'citetext' => $citetext,
      'note' => $note
    ));

    if ($result) {
      $this->add_notice(__('Citation updated successfully!', 'heritagepress'), 'success');
    } else {
      $this->add_notice(__('Failed to update citation. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle deleting a citation
   */
  public function handle_delete_citation()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!$this->check_capability('delete_genealogy')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    $citation_id = intval($_POST['citationID']);

    $result = $this->delete_citation($citation_id);

    if ($result) {
      $this->add_notice(__('Citation deleted successfully!', 'heritagepress'), 'success');
    } else {
      $this->add_notice(__('Failed to delete citation. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle bulk citation actions
   */
  private function handle_bulk_citation_actions()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    $action = sanitize_text_field($_POST['bulk_action']);
    $citation_ids = array_map('intval', $_POST['citation_ids'] ?? array());

    if (empty($citation_ids)) {
      $this->add_notice(__('No citations selected.', 'heritagepress'), 'error');
      return;
    }

    switch ($action) {
      case 'delete':
        if (!$this->check_capability('delete_genealogy')) {
          $this->add_notice(__('Insufficient permissions for bulk delete.', 'heritagepress'), 'error');
          return;
        }
        $result = $this->handle_bulk_action($action, $citation_ids, array($this, 'delete_citation'));
        break;
      default:
        $this->add_notice(__('Invalid bulk action.', 'heritagepress'), 'error');
        return;
    }

    if ($result['success'] > 0) {
      $this->add_notice(
        sprintf(__('%d citations processed successfully.', 'heritagepress'), $result['success']),
        'success'
      );
    }
  }

  /**
   * Add a new citation to the database
   */
  private function add_citation($data)
  {
    global $wpdb;

    $citations_table = $wpdb->prefix . 'hp_citations';

    // Process date
    $citedatetr = !empty($data['citedate']) ? $this->convert_date($data['citedate']) : '0000-00-00';

    // Get next order number
    $ordernum = $this->get_next_citation_order($data['gedcom'], $data['persfamID'], $data['eventID']);

    $insert_data = array(
      'gedcom' => $data['gedcom'],
      'persfamID' => $data['persfamID'],
      'eventID' => $data['eventID'],
      'sourceID' => $data['sourceID'],
      'ordernum' => $ordernum,
      'description' => $data['description'],
      'citedate' => $data['citedate'],
      'citedatetr' => $citedatetr,
      'citetext' => $data['citetext'],
      'page' => $data['page'],
      'quay' => $data['quay'],
      'note' => $data['note']
    );

    $result = $wpdb->insert($citations_table, $insert_data);

    if ($result !== false) {
      // Set last citation session variable (compatibility)
      $_SESSION['lastcite'] = $data['gedcom'] . "|" . $wpdb->insert_id;
      return $wpdb->insert_id;
    }

    return false;
  }

  /**
   * Update an existing citation
   */
  private function update_citation($citation_id, $data)
  {
    global $wpdb;

    $citations_table = $wpdb->prefix . 'hp_citations';

    // Process date
    $citedatetr = !empty($data['citedate']) ? $this->convert_date($data['citedate']) : '0000-00-00';

    $update_data = array(
      'sourceID' => $data['sourceID'],
      'description' => $data['description'],
      'page' => $data['page'],
      'quay' => $data['quay'],
      'citedate' => $data['citedate'],
      'citedatetr' => $citedatetr,
      'citetext' => $data['citetext'],
      'note' => $data['note']
    );

    $result = $wpdb->update(
      $citations_table,
      $update_data,
      array('citationID' => $citation_id)
    );

    if ($result !== false) {
      // Update last citation session variable
      $citation = $this->get_citation($citation_id);
      if ($citation) {
        $_SESSION['lastcite'] = $citation['gedcom'] . "|" . $citation_id;
      }
      return true;
    }

    return false;
  }

  /**
   * Delete a citation
   */
  private function delete_citation($citation_id)
  {
    global $wpdb;

    $citations_table = $wpdb->prefix . 'hp_citations';

    // Clear last citation session if it matches
    if (isset($_SESSION['lastcite'])) {
      $lastcite_parts = explode('|', $_SESSION['lastcite']);
      if (count($lastcite_parts) == 2 && $lastcite_parts[1] == $citation_id) {
        unset($_SESSION['lastcite']);
      }
    }

    $result = $wpdb->delete(
      $citations_table,
      array('citationID' => $citation_id)
    );

    return $result !== false;
  }

  /**
   * Get citation by ID
   */
  private function get_citation($citation_id)
  {
    global $wpdb;

    $citations_table = $wpdb->prefix . 'hp_citations';
    $sources_table = $wpdb->prefix . 'hp_sources';

    $query = $wpdb->prepare("
      SELECT c.*, s.title, s.shorttitle
      FROM $citations_table c
      LEFT JOIN $sources_table s ON c.sourceID = s.sourceID AND s.gedcom = c.gedcom
      WHERE c.citationID = %d
    ", $citation_id);

    return $wpdb->get_row($query, ARRAY_A);
  }

  /**
   * Get citations for a specific person/family and event
   */
  public function get_citations($gedcom, $persfamID, $eventID = '')
  {
    global $wpdb;

    $citations_table = $wpdb->prefix . 'hp_citations';
    $sources_table = $wpdb->prefix . 'hp_sources';

    $where_clause = "WHERE c.gedcom = %s AND c.persfamID = %s";
    $params = array($gedcom, $persfamID);

    if (!empty($eventID)) {
      $where_clause .= " AND c.eventID = %s";
      $params[] = $eventID;
    }

    $query = $wpdb->prepare("
      SELECT c.*, s.title, s.shorttitle
      FROM $citations_table c
      LEFT JOIN $sources_table s ON c.sourceID = s.sourceID AND s.gedcom = c.gedcom
      $where_clause
      ORDER BY c.ordernum, c.citationID
    ", $params);

    return $wpdb->get_results($query, ARRAY_A);
  }

  /**
   * Get next citation order number
   */
  private function get_next_citation_order($gedcom, $persfamID, $eventID)
  {
    global $wpdb;

    $citations_table = $wpdb->prefix . 'hp_citations';

    $query = $wpdb->prepare("
      SELECT MAX(ordernum) as max_order
      FROM $citations_table
      WHERE gedcom = %s AND persfamID = %s AND eventID = %s
    ", $gedcom, $persfamID, $eventID);

    $result = $wpdb->get_var($query);
    return ($result !== null) ? $result + 1 : 1;
  }

  /**
   * Convert date string to MySQL date format (basic implementation)
   */
  private function convert_date($date_string)
  {
    if (empty($date_string)) {
      return '0000-00-00';
    }

    // Try to parse common date formats
    $timestamp = strtotime($date_string);
    if ($timestamp !== false) {
      return date('Y-m-d', $timestamp);
    }

    // If parsing fails, return default
    return '0000-00-00';
  }

  /**
   * AJAX: Add citation
   */
  public function ajax_add_citation()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('edit_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    $citation_data = array(
      'gedcom' => sanitize_text_field($_POST['gedcom'] ?? ''),
      'persfamID' => sanitize_text_field($_POST['persfamID'] ?? ''),
      'eventID' => sanitize_text_field($_POST['eventID'] ?? ''),
      'sourceID' => sanitize_text_field($_POST['sourceID'] ?? ''),
      'description' => sanitize_textarea_field($_POST['description'] ?? ''),
      'page' => sanitize_textarea_field($_POST['page'] ?? ''),
      'quay' => sanitize_text_field($_POST['quay'] ?? ''),
      'citedate' => sanitize_text_field($_POST['citedate'] ?? ''),
      'citetext' => sanitize_textarea_field($_POST['citetext'] ?? ''),
      'note' => sanitize_textarea_field($_POST['note'] ?? '')
    );

    $result = $this->add_citation($citation_data);

    if ($result) {
      wp_send_json_success(array(
        'message' => 'Citation added successfully',
        'citationID' => $result
      ));
    } else {
      wp_send_json_error('Failed to add citation');
    }
  }

  /**
   * AJAX: Update citation
   */
  public function ajax_update_citation()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('edit_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    $citation_id = intval($_POST['citationID']);
    $citation_data = array(
      'sourceID' => sanitize_text_field($_POST['sourceID'] ?? ''),
      'description' => sanitize_textarea_field($_POST['description'] ?? ''),
      'page' => sanitize_textarea_field($_POST['page'] ?? ''),
      'quay' => sanitize_text_field($_POST['quay'] ?? ''),
      'citedate' => sanitize_text_field($_POST['citedate'] ?? ''),
      'citetext' => sanitize_textarea_field($_POST['citetext'] ?? ''),
      'note' => sanitize_textarea_field($_POST['note'] ?? '')
    );

    $result = $this->update_citation($citation_id, $citation_data);

    if ($result) {
      // Get updated citation display info
      $citation = $this->get_citation($citation_id);
      $display_text = $this->get_citation_display_text($citation);

      wp_send_json_success(array(
        'message' => 'Citation updated successfully',
        'display' => $display_text
      ));
    } else {
      wp_send_json_error('Failed to update citation');
    }
  }

  /**
   * AJAX: Delete citation
   */
  public function ajax_delete_citation()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('delete_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    $citation_id = intval($_POST['citationID']);
    $persfamID = sanitize_text_field($_POST['persfamID'] ?? '');
    $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');
    $eventID = sanitize_text_field($_POST['eventID'] ?? '');

    $result = $this->delete_citation($citation_id);

    if ($result) {
      // Get remaining citation count
      $remaining_count = count($this->get_citations($gedcom, $persfamID, $eventID));

      wp_send_json_success(array(
        'message' => 'Citation deleted successfully',
        'remaining_count' => $remaining_count
      ));
    } else {
      wp_send_json_error('Failed to delete citation');
    }
  }

  /**
   * AJAX: Get citations for person/family/event
   */
  public function ajax_get_citations()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');
    $persfamID = sanitize_text_field($_POST['persfamID'] ?? '');
    $eventID = sanitize_text_field($_POST['eventID'] ?? '');

    $citations = $this->get_citations($gedcom, $persfamID, $eventID);

    wp_send_json_success(array('citations' => $citations));
  }

  /**
   * AJAX: Search citations
   */
  public function ajax_search_citations()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    $search_term = sanitize_text_field($_POST['search'] ?? '');
    $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');

    global $wpdb;

    $citations_table = $wpdb->prefix . 'hp_citations';
    $sources_table = $wpdb->prefix . 'hp_sources';

    $query = $wpdb->prepare("
      SELECT c.*, s.title, s.shorttitle
      FROM $citations_table c
      LEFT JOIN $sources_table s ON c.sourceID = s.sourceID AND s.gedcom = c.gedcom
      WHERE c.gedcom = %s
      AND (c.description LIKE %s OR c.citetext LIKE %s OR c.page LIKE %s OR s.title LIKE %s)
      ORDER BY c.citationID DESC
      LIMIT 50
    ", $gedcom, "%$search_term%", "%$search_term%", "%$search_term%", "%$search_term%");

    $results = $wpdb->get_results($query, ARRAY_A);

    wp_send_json_success(array('citations' => $results));
  }

  /**
   * AJAX: Get last citation (compatibility)
   */
  public function ajax_get_last_citation()
  {
    if (isset($_SESSION['lastcite'])) {
      $lastcite_parts = explode('|', $_SESSION['lastcite']);
      if (count($lastcite_parts) == 2) {
        $citation = $this->get_citation($lastcite_parts[1]);
        wp_send_json_success(array('citation' => $citation));
      }
    }

    wp_send_json_success(array('citation' => null));
  }

  /**
   * AJAX: Search sources (for citation forms)
   */
  public function ajax_search_sources()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    $search_term = sanitize_text_field($_POST['search'] ?? '');
    $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');

    global $wpdb;

    $sources_table = $wpdb->prefix . 'hp_sources';

    $query = $wpdb->prepare("
      SELECT sourceID, title, shorttitle, author
      FROM $sources_table
      WHERE gedcom = %s
      AND (sourceID LIKE %s OR title LIKE %s OR shorttitle LIKE %s OR author LIKE %s)
      ORDER BY sourceID
      LIMIT 20
    ", $gedcom, "%$search_term%", "%$search_term%", "%$search_term%", "%$search_term%");

    $results = $wpdb->get_results($query, ARRAY_A);

    wp_send_json_success(array('sources' => $results));
  }

  /**
   * AJAX: Create new source (basic implementation)
   */
  public function ajax_create_source()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('edit_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    // This would require a proper source controller - placeholder for now
    wp_send_json_error('Source creation not yet implemented');
  }

  /**
   * AJAX: Get citations modal HTML
   * Replicates HeritagePress admin_citations.php modal interface
   */
  public function ajax_get_citations_modal()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    $persfamID = sanitize_text_field($_POST['persfamID'] ?? '');
    $tree = sanitize_text_field($_POST['tree'] ?? '');
    $eventID = sanitize_text_field($_POST['eventID'] ?? '');
    $noteID = sanitize_text_field($_POST['noteID'] ?? '');

    if (empty($persfamID) || empty($tree)) {
      wp_send_json_error('Missing required parameters');
    }

    // Get event type description
    $event_type_desc = $this->get_event_type_description($eventID);

    // Get citations
    $citations = $this->get_citations_for_modal($tree, $persfamID, $eventID, $noteID);
    $citation_count = count($citations);

    // Generate HTML
    ob_start();
    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/citations-modal.php';
    $html = ob_get_clean();

    wp_send_json_success(array(
      'html' => $html,
      'citationCount' => $citation_count,
      'eventTypeDesc' => $event_type_desc
    ));
  }

  /**
   * AJAX: Load add citation form
   */
  public function ajax_load_add_citation_form()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    $persfamID = sanitize_text_field($_POST['persfamID'] ?? '');
    $tree = sanitize_text_field($_POST['tree'] ?? '');
    $eventID = sanitize_text_field($_POST['eventID'] ?? '');

    // Get event list for person/family
    $events = $this->get_available_events($persfamID, $tree);

    // Check for last citation
    $last_citation = null;
    if (isset($_SESSION['lastcite'])) {
      $lastcite_parts = explode('|', $_SESSION['lastcite']);
      if (count($lastcite_parts) == 2 && $lastcite_parts[0] == $tree) {
        $last_citation = $lastcite_parts[1];
      }
    }

    ob_start();
    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/citations-add-modal.php';
    $html = ob_get_clean();

    wp_send_json_success(array('html' => $html));
  }

  /**
   * AJAX: Load edit citation form
   */
  public function ajax_load_edit_citation_form()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    $citation_id = intval($_POST['citationID']);
    $citation = $this->get_citation($citation_id);

    if (!$citation) {
      wp_send_json_error('Citation not found');
    }

    // Get event list for person/family
    $events = $this->get_available_events($citation['persfamID'], $citation['gedcom']);

    ob_start();
    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/citations-edit-modal.php';
    $html = ob_get_clean();

    wp_send_json_success(array('html' => $html));
  }

  /**
   * AJAX: Update citation order
   */
  public function ajax_update_citation_order()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('edit_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    $citation_ids = array_map('intval', $_POST['citationIds'] ?? array());

    if (empty($citation_ids)) {
      wp_send_json_error('No citation IDs provided');
    }

    global $wpdb;
    $citations_table = $wpdb->prefix . 'hp_citations';

    // Update order numbers
    $order = 1;
    foreach ($citation_ids as $citation_id) {
      $wpdb->update(
        $citations_table,
        array('ordernum' => $order),
        array('citationID' => $citation_id)
      );
      $order++;
    }

    wp_send_json_success('Order updated');
  }

  /**
   * Get event type description
   */
  private function get_event_type_description($eventID)
  {
    if (empty($eventID)) {
      return 'General';
    }

    global $wpdb;
    $events_table = $wpdb->prefix . 'hp_events';
    $eventtypes_table = $wpdb->prefix . 'hp_eventtypes';

    $query = $wpdb->prepare("
      SELECT et.tag, et.display
      FROM $events_table e
      LEFT JOIN $eventtypes_table et ON e.eventtypeID = et.eventtypeID
      WHERE e.eventID = %s
    ", $eventID);

    $result = $wpdb->get_row($query, ARRAY_A);

    if ($result) {
      return !empty($result['display']) ? $result['display'] : $result['tag'];
    }

    return $eventID;
  }

  /**
   * Get citations for modal display
   */
  private function get_citations_for_modal($tree, $persfamID, $eventID, $noteID = '')
  {
    global $wpdb;
    $citations_table = $wpdb->prefix . 'hp_citations';
    $sources_table = $wpdb->prefix . 'hp_sources';

    $where_clause = "WHERE c.gedcom = %s AND c.persfamID = %s";
    $params = array($tree, $persfamID);

    if (!empty($eventID)) {
      $where_clause .= " AND c.eventID = %s";
      $params[] = $eventID;
    }

    // Include note citations if noteID is provided
    $note_clause = '';
    if (!empty($noteID)) {
      $note_clause = " OR c.persfamID = %s";
      $params[] = $noteID;
    }

    $query = $wpdb->prepare("
      SELECT c.citationID, c.sourceID, c.description, c.page, c.ordernum,
             s.title, s.shorttitle
      FROM $citations_table c
      LEFT JOIN $sources_table s ON c.sourceID = s.sourceID AND s.gedcom = c.gedcom
      $where_clause$note_clause
      ORDER BY c.ordernum, c.citationID
    ", $params);

    return $wpdb->get_results($query, ARRAY_A);
  }

  /**
   * Get available events for person/family
   */
  private function get_available_events($persfamID, $tree)
  {
    // Determine if person or family
    $is_person = strpos($persfamID, 'I') === 0 || !preg_match('/^F/', $persfamID);

    $events = array();

    if ($is_person) {
      // Standard person events
      $events[''] = 'Person';
      $events['BIRT'] = 'Birth';
      $events['DEAT'] = 'Death';
      $events['BURI'] = 'Burial';
    } else {
      // Standard family events
      $events[''] = 'Family';
      $events['MARR'] = 'Marriage';
      $events['DIV'] = 'Divorce';
    }

    // Get custom events from database
    global $wpdb;
    $events_table = $wpdb->prefix . 'hp_events';
    $eventtypes_table = $wpdb->prefix . 'hp_eventtypes';

    $query = $wpdb->prepare("
      SELECT e.eventID, et.tag, et.display, e.eventdate
      FROM $events_table e
      LEFT JOIN $eventtypes_table et ON e.eventtypeID = et.eventtypeID
      WHERE e.persfamID = %s AND e.gedcom = %s AND e.keep = '1'
      ORDER BY e.ordernum, et.tag
    ", $persfamID, $tree);

    $custom_events = $wpdb->get_results($query, ARRAY_A);

    foreach ($custom_events as $event) {
      $display = !empty($event['display']) ? $event['display'] : $event['tag'];
      if (!empty($event['eventdate'])) {
        $display .= ' (' . $event['eventdate'] . ')';
      }
      $events[$event['eventID']] = $display;
    }

    return $events;
  }
}
