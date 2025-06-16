<?php

/**
 * AJAX Handler for Person Finder (for Family management)
 * Handles searching and finding people for family assignments
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Person_Finder_Handler
{
  /**
   * Find people for autocomplete/search in family context
   */
  public static function find_people_for_family()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_find_people_family')) {
      wp_send_json_error('Security check failed');
      return;
    }

    if (!current_user_can('edit_genealogy')) {
      wp_send_json_error('Permission denied');
      return;
    }

    $search_term = sanitize_text_field($_POST['search']);
    $gedcom = sanitize_text_field($_POST['gedcom']);
    $role = sanitize_text_field($_POST['role']); // 'husband', 'wife', 'child'
    $limit = isset($_POST['limit']) ? min(50, intval($_POST['limit'])) : 20;

    if (empty($search_term)) {
      wp_send_json_success(array('people' => array()));
      return;
    }

    global $wpdb;
    $people_table = $wpdb->prefix . 'hp_people';

    // Build search query
    $search_like = '%' . $wpdb->esc_like($search_term) . '%';

    $sql = "SELECT personID, firstname, lastname, birthdate, deathdate, sex, living, private
            FROM {$people_table}
            WHERE gedcom = %s
            AND (
              personID LIKE %s
              OR firstname LIKE %s
              OR lastname LIKE %s
              OR CONCAT(firstname, ' ', lastname) LIKE %s
              OR CONCAT(lastname, ', ', firstname) LIKE %s
            )";

    // Add sex filter for husband/wife roles
    if ($role === 'husband') {
      $sql .= " AND (sex = 'M' OR sex = '' OR sex IS NULL)";
    } elseif ($role === 'wife') {
      $sql .= " AND (sex = 'F' OR sex = '' OR sex IS NULL)";
    }

    $sql .= " ORDER BY lastname, firstname, personID LIMIT %d";

    $results = $wpdb->get_results($wpdb->prepare(
      $sql,
      $gedcom,
      $search_like,
      $search_like,
      $search_like,
      $search_like,
      $search_like,
      $limit
    ));

    $people = array();
    foreach ($results as $person) {
      $name = trim($person->firstname . ' ' . $person->lastname);
      $display_name = $name ? $name : '[No Name]';

      $extra_info = array();
      if ($person->birthdate) {
        $extra_info[] = 'b. ' . $person->birthdate;
      }
      if ($person->deathdate) {
        $extra_info[] = 'd. ' . $person->deathdate;
      }
      if ($person->sex) {
        $extra_info[] = $person->sex;
      }

      $display_text = $person->personID . ' - ' . $display_name;
      if (!empty($extra_info)) {
        $display_text .= ' (' . implode(', ', $extra_info) . ')';
      }

      $people[] = array(
        'id' => $person->personID,
        'text' => $display_text,
        'firstname' => $person->firstname,
        'lastname' => $person->lastname,
        'name' => $display_name,
        'birthdate' => $person->birthdate,
        'deathdate' => $person->deathdate,
        'sex' => $person->sex,
        'living' => $person->living,
        'private' => $person->private
      );
    }

    wp_send_json_success(array('people' => $people));
  }

  /**
   * Get person details for family assignment
   */
  public static function get_person_details_for_family()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_get_person_family_details')) {
      wp_send_json_error('Security check failed');
      return;
    }

    if (!current_user_can('edit_genealogy')) {
      wp_send_json_error('Permission denied');
      return;
    }

    $person_id = sanitize_text_field($_POST['person_id']);
    $gedcom = sanitize_text_field($_POST['gedcom']);

    if (empty($person_id) || empty($gedcom)) {
      wp_send_json_error('Person ID and tree required');
      return;
    }

    global $wpdb;
    $people_table = $wpdb->prefix . 'hp_people';
    $families_table = $wpdb->prefix . 'hp_families';

    // Get person details
    $person = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM {$people_table} WHERE personID = %s AND gedcom = %s",
      $person_id,
      $gedcom
    ));

    if (!$person) {
      wp_send_json_error('Person not found');
      return;
    }

    // Get existing family relationships
    $spouse_families = $wpdb->get_results($wpdb->prepare(
      "SELECT familyID, husband, wife FROM {$families_table}
       WHERE gedcom = %s AND (husband = %s OR wife = %s)",
      $gedcom,
      $person_id,
      $person_id
    ));

    $child_families = $wpdb->get_results($wpdb->prepare(
      "SELECT famc FROM {$people_table}
       WHERE personID = %s AND gedcom = %s AND famc IS NOT NULL AND famc != ''",
      $person_id,
      $gedcom
    ));

    wp_send_json_success(array(
      'person' => $person,
      'spouse_families' => $spouse_families,
      'child_families' => $child_families
    ));
  }

  /**
   * Check for potential duplicate family relationships
   */
  public static function check_family_conflicts()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_check_family_conflicts')) {
      wp_send_json_error('Security check failed');
      return;
    }

    if (!current_user_can('edit_genealogy')) {
      wp_send_json_error('Permission denied');
      return;
    }

    $husband_id = sanitize_text_field($_POST['husband_id']);
    $wife_id = sanitize_text_field($_POST['wife_id']);
    $gedcom = sanitize_text_field($_POST['gedcom']);
    $exclude_family = sanitize_text_field($_POST['exclude_family']); // For edits

    if (empty($gedcom)) {
      wp_send_json_error('Tree required');
      return;
    }

    global $wpdb;
    $families_table = $wpdb->prefix . 'hp_families';

    $conflicts = array();

    // Check if this husband/wife combination already exists
    if (!empty($husband_id) && !empty($wife_id)) {
      $sql = "SELECT familyID FROM {$families_table}
              WHERE gedcom = %s AND husband = %s AND wife = %s";
      $params = array($gedcom, $husband_id, $wife_id);

      if (!empty($exclude_family)) {
        $sql .= " AND familyID != %s";
        $params[] = $exclude_family;
      }

      $existing = $wpdb->get_var($wpdb->prepare($sql, $params));

      if ($existing) {
        $conflicts[] = array(
          'type' => 'duplicate_family',
          'message' => "This husband/wife combination already exists in family {$existing}",
          'family_id' => $existing
        );
      }
    }

    wp_send_json_success(array('conflicts' => $conflicts));
  }
}

// Register AJAX handlers
add_action('wp_ajax_hp_find_people_family', array('HP_Person_Finder_Handler', 'find_people_for_family'));
add_action('wp_ajax_hp_get_person_family_details', array('HP_Person_Finder_Handler', 'get_person_details_for_family'));
add_action('wp_ajax_hp_check_family_conflicts', array('HP_Person_Finder_Handler', 'check_family_conflicts'));
