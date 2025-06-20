<?php

/**
 * People Controller
 * Handles admin and AJAX actions for people (individuals)
 *
 * @package HeritagePress
 * @subpackage Controllers
 */
if (!defined('ABSPATH')) {
  exit;
}

require_once dirname(__FILE__) . '/../../includes/controllers/class-hp-base-controller.php';

class HP_People_Controller extends HP_Base_Controller
{
  public function __construct()
  {
    parent::__construct('people');
  }

  // List all people
  public function ajax_list_people()
  {
    // TODO: Implement logic
    wp_send_json_success(['people' => []]);
  }

  // Add a new person (with all major fields)
  public function ajax_add_person()
  {
    // TODO: Implement logic for all fields: names, gender, birth, death, living, private, branch, tree, events, notes, citations
    wp_send_json_success(['message' => 'Person added']);
  }

  // Edit an existing person
  public function ajax_edit_person()
  {
    // Forward to the main controller's AJAX update logic
    if (!class_exists('HP_People_Controller')) {
      require_once dirname(__FILE__) . '/../../includes/controllers/class-hp-people-controller.php';
    }
    $main_controller = new \HP_People_Controller();
    $main_controller->ajax_update_person();
  }

  // Delete a person
  public function ajax_delete_person()
  {
    // TODO: Implement logic
    wp_send_json_success(['message' => 'Person deleted']);
  }

  // Get a single person by ID
  public function ajax_get_person()
  {
    // TODO: Implement logic
    wp_send_json_success(['person' => null]);
  }

  // Search people (advanced search)
  public function ajax_search_people()
  {
    // TODO: Implement logic
    wp_send_json_success(['results' => []]);
  }

  // Duplicate a person (for quick entry)
  public function ajax_duplicate_person()
  {
    // TODO: Implement logic
    wp_send_json_success(['message' => 'Person duplicated']);
  }

  // Validate person ID (for uniqueness)
  public function ajax_validate_person_id()
  {
    // TODO: Implement logic
    wp_send_json_success(['valid' => true]);
  }

  // Bulk delete people
  public function ajax_bulk_delete_people()
  {
    // TODO: Implement logic
    wp_send_json_success(['message' => 'People deleted']);
  }

  // Bulk update people (e.g., living/private flags)
  public function ajax_bulk_update_people()
  {
    // TODO: Implement logic
    wp_send_json_success(['message' => 'People updated']);
  }

  // Person lookup (for linking, search, etc.)
  public function ajax_person_lookup()
  {
    // TODO: Implement logic
    wp_send_json_success(['results' => []]);
  }

  // Add event to person
  public function ajax_add_person_event()
  {
    // TODO: Implement logic
    wp_send_json_success(['message' => 'Event added to person']);
  }

  // Add note to person
  public function ajax_add_person_note()
  {
    // TODO: Implement logic
    wp_send_json_success(['message' => 'Note added to person']);
  }

  // Add citation to person
  public function ajax_add_person_citation()
  {
    // TODO: Implement logic
    wp_send_json_success(['message' => 'Citation added to person']);
  }

  // Add a new person as a child to a family, with relationship type and parent IDs
  public function ajax_add_child_to_family()
  {
    // Example expected POST fields: personID, tree, fatherID, motherID, frel, mrel, child_data (array)
    $personID = sanitize_text_field($_POST['personID'] ?? '');
    $tree = sanitize_text_field($_POST['tree'] ?? '');
    $fatherID = sanitize_text_field($_POST['fatherID'] ?? '');
    $motherID = sanitize_text_field($_POST['motherID'] ?? '');
    $frel = sanitize_text_field($_POST['frel'] ?? ''); // father relationship type
    $mrel = sanitize_text_field($_POST['mrel'] ?? ''); // mother relationship type
    $child_data = isset($_POST['child_data']) ? $_POST['child_data'] : [];

    // TODO: Validate required fields, check permissions, insert person, link to family, set relationship types
    // (Stub only)
    wp_send_json_success([
      'message' => 'Child added to family',
      'personID' => $personID,
      'tree' => $tree,
      'fatherID' => $fatherID,
      'motherID' => $motherID,
      'frel' => $frel,
      'mrel' => $mrel,
      'child_data' => $child_data
    ]);
  }

  public function register_hooks()
  {
    parent::register_hooks();
    add_action('wp_ajax_hp_list_people', array($this, 'ajax_list_people'));
    add_action('wp_ajax_hp_add_person', array($this, 'ajax_add_person'));
    add_action('wp_ajax_hp_edit_person', array($this, 'ajax_edit_person'));
    add_action('wp_ajax_hp_delete_person', array($this, 'ajax_delete_person'));
    add_action('wp_ajax_hp_get_person', array($this, 'ajax_get_person'));
    add_action('wp_ajax_hp_search_people', array($this, 'ajax_search_people'));
    add_action('wp_ajax_hp_duplicate_person', array($this, 'ajax_duplicate_person'));
    add_action('wp_ajax_hp_validate_person_id', array($this, 'ajax_validate_person_id'));
    add_action('wp_ajax_hp_bulk_delete_people', array($this, 'ajax_bulk_delete_people'));
    add_action('wp_ajax_hp_bulk_update_people', array($this, 'ajax_bulk_update_people'));
    add_action('wp_ajax_hp_person_lookup', array($this, 'ajax_person_lookup'));
    add_action('wp_ajax_hp_add_person_event', array($this, 'ajax_add_person_event'));
    add_action('wp_ajax_hp_add_person_note', array($this, 'ajax_add_person_note'));
    add_action('wp_ajax_hp_add_person_citation', array($this, 'ajax_add_person_citation'));
    add_action('wp_ajax_hp_add_child_to_family', array($this, 'ajax_add_child_to_family'));
  }
}
