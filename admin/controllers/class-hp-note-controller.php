<?php

/**
 * Note Controller
 *
 * Handles all note management functionality including CRUD operations,
 * note validation, and note-related AJAX requests
 *
 *
 */

if (!defined('ABSPATH')) {
  exit;
}

require_once plugin_dir_path(__FILE__) . '../../includes/controllers/class-hp-base-controller.php';

class HP_Note_Controller extends HP_Base_Controller
{
  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct('notes');
    $this->capabilities = array(
      'manage_notes' => 'manage_genealogy',
      'edit_notes' => 'edit_genealogy',
      'delete_notes' => 'delete_genealogy'
    );
  }

  /**
   * Initialize the note controller
   */
  public function init()
  {
    parent::init();
    // Note-specific initialization
  }

  /**
   * Register hooks for note management
   */
  public function register_hooks()
  {
    parent::register_hooks();

    // AJAX handlers for notes
    add_action('wp_ajax_hp_add_note', array($this, 'ajax_add_note'));
    add_action('wp_ajax_hp_update_note', array($this, 'ajax_update_note'));
    add_action('wp_ajax_hp_delete_note', array($this, 'ajax_delete_note'));
    add_action('wp_ajax_hp_get_notes', array($this, 'ajax_get_notes'));
    add_action('wp_ajax_hp_get_note', array($this, 'ajax_get_note'));
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
   * Enqueue assets for note management
   */
  public function enqueue_assets()
  {
    // Note-specific CSS and JS would be enqueued here
    // For now, using the main admin assets
  }

  /**
   * Handle note page actions
   */
  public function handle_note_actions($current_tab)
  {
    if (!$this->check_capability('edit_genealogy')) {
      return;
    }

    // Handle form submissions
    if (isset($_POST['action'])) {
      switch ($_POST['action']) {
        case 'add_note':
          $this->handle_add_note();
          break;
        case 'update_note':
          $this->handle_update_note();
          break;
        case 'delete_note':
          $this->handle_delete_note();
          break;
        case 'bulk_action':
          $this->handle_bulk_note_actions();
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
      $this->handle_note_actions($current_tab);
    }
  }

  /**
   * Display the note management page
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

    // Check if we're editing a specific note
    $note_id = isset($_GET['noteID']) ? sanitize_text_field($_GET['noteID']) : '';
    $is_editing = !empty($note_id);

    if ($is_editing) {
      $current_tab = 'edit';
    }

    // Load the appropriate view based on current page context
    $this->load_note_view($current_tab, $note_id);
  }

  /**
   * Load note view based on tab
   */
  private function load_note_view($tab, $note_id = '')
  {
    switch ($tab) {
      case 'add':
        $this->load_view('notes-add');
        break;
      case 'edit':
        $this->load_view('notes-edit', array('noteID' => $note_id));
        break;
      case 'browse':
      default:
        $this->load_view('notes-main');
        break;
    }
  }

  /**
   * Handle adding a new note
   */
  private function handle_add_note()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!$this->check_capability('edit_genealogy')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    // Sanitize form data (genealogy field names)
    $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');
    $persfamID = sanitize_text_field($_POST['persfamID'] ?? '');
    $eventID = sanitize_text_field($_POST['eventID'] ?? '');
    $note = sanitize_textarea_field($_POST['note'] ?? '');
    $private = !empty($_POST['private']) ? 1 : 0;

    // Validate required fields
    if (empty($gedcom) || empty($persfamID) || empty($note)) {
      $this->add_notice(__('Tree, Person/Family, and Note text are required.', 'heritagepress'), 'error');
      return;
    }

    // Add the note
    $result = $this->add_note(array(
      'gedcom' => $gedcom,
      'persfamID' => $persfamID,
      'eventID' => $eventID,
      'note' => $note,
      'private' => $private
    ));

    if ($result) {
      $this->add_notice(__('Note added successfully!', 'heritagepress'), 'success');
    } else {
      $this->add_notice(__('Failed to add note. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle updating a note
   */
  private function handle_update_note()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!$this->check_capability('edit_genealogy')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    $note_id = intval($_POST['noteID']);
    $link_id = intval($_POST['ID']);
    $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');
    $note = sanitize_textarea_field($_POST['note'] ?? '');
    $private = !empty($_POST['private']) ? 1 : 0;

    $result = $this->update_note($note_id, $link_id, array(
      'note' => $note,
      'private' => $private
    ));

    if ($result) {
      $this->add_notice(__('Note updated successfully!', 'heritagepress'), 'success');
    } else {
      $this->add_notice(__('Failed to update note. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle deleting a note
   */
  private function handle_delete_note()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!$this->check_capability('delete_genealogy')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    $note_id = intval($_POST['noteID']);
    $result = $this->delete_note($note_id);

    if ($result) {
      $this->add_notice(__('Note deleted successfully!', 'heritagepress'), 'success');
    } else {
      $this->add_notice(__('Failed to delete note. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle bulk note actions
   */
  private function handle_bulk_note_actions()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    $action = sanitize_text_field($_POST['bulk_action']);
    $selected_notes = array_map('intval', $_POST['selected_notes'] ?? array());

    if (empty($selected_notes)) {
      $this->add_notice(__('No notes selected.', 'heritagepress'), 'error');
      return;
    }

    switch ($action) {
      case 'delete':
        if (!$this->check_capability('delete_genealogy')) {
          $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
          return;
        }
        $result = $this->handle_bulk_action($action, $selected_notes, array($this, 'delete_note'));
        break;
      default:
        $this->add_notice(__('Invalid bulk action.', 'heritagepress'), 'error');
        return;
    }
  }

  /**
   * AJAX: Add note
   */
  public function ajax_add_note()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('edit_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    $gedcom = sanitize_text_field($_POST['tree'] ?? '');
    $persfamID = sanitize_text_field($_POST['persfamID'] ?? '');
    $eventID = sanitize_text_field($_POST['eventID'] ?? '');
    $note = sanitize_textarea_field($_POST['note'] ?? '');
    $private = !empty($_POST['private']) ? 1 : 0;

    if (empty($note)) {
      wp_send_json_error('Note text is required');
    }

    $result = $this->add_note(array(
      'gedcom' => $gedcom,
      'persfamID' => $persfamID,
      'eventID' => $eventID,
      'note' => $note,
      'private' => $private
    ));

    if ($result) {
      // Get the note display text (truncated as needed)
      $display_text = $this->truncate_note($note, 75);

      wp_send_json_success(array(
        'message' => 'Note added successfully',
        'id' => $result['link_id'],
        'persfamID' => $persfamID,
        'tree' => $gedcom,
        'eventID' => $eventID,
        'display' => $display_text,
        'allow_edit' => current_user_can('edit_genealogy'),
        'allow_delete' => current_user_can('delete_genealogy')
      ));
    } else {
      wp_send_json_error('Failed to add note');
    }
  }

  /**
   * AJAX: Update note
   */
  public function ajax_update_note()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('edit_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    $note_id = intval($_POST['xID']);
    $link_id = intval($_POST['ID']);
    $note = sanitize_textarea_field($_POST['note'] ?? '');
    $private = !empty($_POST['private']) ? 1 : 0;

    $result = $this->update_note($note_id, $link_id, array(
      'note' => $note,
      'private' => $private
    ));

    if ($result) {
      // Get updated note display text (as needed)
      $display_text = $this->truncate_note($note, 75);

      wp_send_json_success(array(
        'message' => 'Note updated successfully',
        'display' => $display_text
      ));
    } else {
      wp_send_json_error('Failed to update note');
    }
  }

  /**
   * AJAX: Delete note
   */
  public function ajax_delete_note()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('delete_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    $note_id = intval($_POST['noteID']);
    $person_id = sanitize_text_field($_POST['personID'] ?? '');
    $event_id = sanitize_text_field($_POST['eventID'] ?? '');
    $tree = sanitize_text_field($_POST['tree'] ?? '');

    // Delete citations first (as needed)
    global $wpdb;
    $citations_table = $wpdb->prefix . 'hp_citations';
    $note_prefix = get_option('heritagepress_note_prefix', 'N');
    $note_suffix = get_option('heritagepress_note_suffix', '');

    $wpdb->delete($citations_table, array(
      'eventID' => $note_prefix . $note_id . $note_suffix
    ));

    // Delete the note
    $result = $this->delete_note($note_id);

    if ($result) {
      // Count remaining notes for this person/event (as needed)
      $notelinks_table = $wpdb->prefix . 'hp_notelinks';
      $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(ID) FROM $notelinks_table WHERE gedcom = %s AND persfamID = %s AND eventID = %s",
        $tree,
        $person_id,
        $event_id
      ));

      wp_send_json_success(array(
        'message' => 'Note deleted successfully',
        'count' => intval($count)
      ));
    } else {
      wp_send_json_error('Failed to delete note');
    }
  }

  /**
   * AJAX: Get notes for person/family/event
   */
  public function ajax_get_notes()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    $gedcom = sanitize_text_field($_POST['tree'] ?? '');
    $persfamID = sanitize_text_field($_POST['persfamID'] ?? '');
    $eventID = sanitize_text_field($_POST['eventID'] ?? '');

    $notes = $this->get_notes($gedcom, $persfamID, $eventID);

    wp_send_json_success(array(
      'notes' => $notes
    ));
  }

  /**
   * AJAX: Get single note for editing
   */
  public function ajax_get_note()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    $note_id = intval($_POST['noteID']);
    $note = $this->get_note($note_id);

    if ($note) {
      wp_send_json_success(array(
        'note' => $note
      ));
    } else {
      wp_send_json_error('Note not found');
    }
  }

  /**
   * Add a new note
   */
  private function add_note($data)
  {
    global $wpdb;

    $xnotes_table = $wpdb->prefix . 'hp_xnotes';
    $notelinks_table = $wpdb->prefix . 'hp_notelinks';

    // Decode note text if needed (as needed)
    $note_text = wp_unslash($data['note']);

    // Insert into xnotes table
    $xnote_result = $wpdb->insert($xnotes_table, array(
      'noteID' => '', // Will be generated
      'gedcom' => $data['gedcom'],
      'note' => $note_text
    ));

    if ($xnote_result === false) {
      return false;
    }

    $xnote_id = $wpdb->insert_id;

    // Insert link into notelinks table
    $link_result = $wpdb->insert($notelinks_table, array(
      'persfamID' => $data['persfamID'],
      'gedcom' => $data['gedcom'],
      'xnoteID' => $xnote_id,
      'eventID' => $data['eventID'],
      'secret' => $data['private'],
      'ordernum' => 999
    ));

    if ($link_result === false) {
      // Rollback xnote insert
      $wpdb->delete($xnotes_table, array('ID' => $xnote_id));
      return false;
    }

    $link_id = $wpdb->insert_id;

    // Log the action (as needed)
    $this->log_admin_action('Added note: ' . $data['gedcom'] . '/' . $data['persfamID'] . '/' . $xnote_id . '/' . $data['eventID']);

    return array(
      'xnote_id' => $xnote_id,
      'link_id' => $link_id
    );
  }

  /**
   * Update an existing note
   */
  private function update_note($note_id, $link_id, $data)
  {
    global $wpdb;

    $xnotes_table = $wpdb->prefix . 'hp_xnotes';
    $notelinks_table = $wpdb->prefix . 'hp_notelinks';

    // Decode note text if needed (as needed)
    $note_text = wp_unslash($data['note']);

    // Update xnotes table
    if ($note_id) {
      $xnote_result = $wpdb->update(
        $xnotes_table,
        array('note' => $note_text),
        array('ID' => $note_id)
      );
    }

    // Update notelinks table
    $link_result = $wpdb->update(
      $notelinks_table,
      array('secret' => $data['private']),
      array('ID' => $link_id)
    );

    // Log the action (as needed)
    $this->log_admin_action('Modified note: ' . $link_id);

    return ($xnote_result !== false && $link_result !== false);
  }

  /**
   * Delete a note
   */
  private function delete_note($note_id)
  {
    global $wpdb;

    $xnotes_table = $wpdb->prefix . 'hp_xnotes';
    $notelinks_table = $wpdb->prefix . 'hp_notelinks';

    // Delete from notelinks first (foreign key constraint)
    $links_result = $wpdb->delete($notelinks_table, array('ID' => $note_id));

    // Then delete from xnotes if there are no more links
    if ($links_result) {
      // Get the xnoteID from the deleted link
      $xnote_id = $wpdb->get_var($wpdb->prepare(
        "SELECT xnoteID FROM $notelinks_table WHERE ID = %d",
        $note_id
      ));

      if ($xnote_id) {
        // Check if this xnote has other links
        $link_count = $wpdb->get_var($wpdb->prepare(
          "SELECT COUNT(*) FROM $notelinks_table WHERE xnoteID = %d",
          $xnote_id
        ));

        if ($link_count == 0) {
          // No more links, delete the xnote
          $wpdb->delete($xnotes_table, array('ID' => $xnote_id));
        }
      }

      // Log the action
      $this->log_admin_action('Deleted note: ' . $note_id);

      return true;
    }

    return false;
  }

  /**
   * Get notes for a specific person/family/event
   */
  private function get_notes($gedcom, $persfamID, $eventID = '')
  {
    global $wpdb;

    $notelinks_table = $wpdb->prefix . 'hp_notelinks';
    $xnotes_table = $wpdb->prefix . 'hp_xnotes';

    $where_clause = "WHERE nl.gedcom = %s AND nl.persfamID = %s";
    $params = array($gedcom, $persfamID);

    if (!empty($eventID)) {
      $where_clause .= " AND nl.eventID = %s";
      $params[] = $eventID;
    }

    $sql = "SELECT nl.*, xn.note, xn.noteID
            FROM $notelinks_table nl
            LEFT JOIN $xnotes_table xn ON nl.xnoteID = xn.ID AND nl.gedcom = xn.gedcom
            $where_clause
            ORDER BY nl.ordernum, nl.ID";

    return $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
  }

  /**
   * Get a single note by ID
   */
  private function get_note($note_id)
  {
    global $wpdb;

    $notelinks_table = $wpdb->prefix . 'hp_notelinks';
    $xnotes_table = $wpdb->prefix . 'hp_xnotes';

    $sql = "SELECT nl.*, xn.note, xn.noteID, xn.ID as xID
            FROM $notelinks_table nl
            LEFT JOIN $xnotes_table xn ON nl.xnoteID = xn.ID AND nl.gedcom = xn.gedcom
            WHERE nl.ID = %d";

    return $wpdb->get_row($wpdb->prepare($sql, $note_id), ARRAY_A);
  }

  /**
   * Truncate note text for display (as needed)
   */
  private function truncate_note($note, $length = 75)
  {
    // Clean the note text
    $note = strip_tags($note);
    $note = str_replace(array("\r", "\n"), ' ', $note);

    if (strlen($note) <= $length) {
      return $note;
    }

    return substr($note, 0, $length) . '...';
  }

  /**
   * Log admin action (simplified admin logging)
   */
  private function log_admin_action($message)
  {
    $current_user = wp_get_current_user();
    $log_message = sprintf(
      '[%s] %s - %s',
      current_time('Y-m-d H:i:s'),
      $current_user->user_login,
      $message
    );

    error_log('HeritagePress Admin: ' . $log_message);
  }
}
