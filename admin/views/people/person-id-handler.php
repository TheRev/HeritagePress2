<?php

/**
 * AJAX Handler for Person ID Generation and Checking
 * Handles person ID generation and availability checking
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Person_ID_Handler
{
  /**
   * Generate a new Person ID
   */
  public static function generate_person_id()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_generate_person_id')) {
      wp_send_json_error('Security check failed');
      return;
    }

    if (!current_user_can('manage_options')) { // Temporarily use manage_options instead
      wp_send_json_error('Permission denied');
      return;
    }

    $gedcom = sanitize_text_field($_POST['gedcom']);
    if (empty($gedcom)) {
      wp_send_json_error('Tree selection required');
      return;
    }
    global $wpdb;
    $people_table = $wpdb->prefix . 'hp_people';

    // Get the highest numbered person ID for this tree
    $last_id = $wpdb->get_var($wpdb->prepare(
      "SELECT personID FROM {$people_table}
             WHERE gedcom = %s
             AND personID REGEXP '^I[0-9]+$'
             ORDER BY CAST(SUBSTRING(personID, 2) AS UNSIGNED) DESC
             LIMIT 1",
      $gedcom
    ));

    // Extract number and increment
    $next_number = 1;
    if ($last_id && preg_match('/^I(\d+)$/', $last_id, $matches)) {
      $next_number = intval($matches[1]) + 1;
    }

    // Generate new ID
    $new_id = 'I' . $next_number;

    // Double-check availability
    $exists = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$people_table} WHERE personID = %s AND gedcom = %s",
      $new_id,
      $gedcom
    ));

    // If somehow exists, try sequential IDs
    while ($exists > 0 && $next_number < 999999) {
      $next_number++;
      $new_id = 'I' . $next_number;
      $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$people_table} WHERE personID = %s AND gedcom = %s",
        $new_id,
        $gedcom
      ));
    }
    wp_send_json_success(array(
      'personID' => $new_id,
      'message' => 'Person ID generated successfully'
    ));
  }
  /**
   * Check Person ID availability
   */
  public static function check_person_id()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_check_person_id')) {
      wp_die('Security check failed');
    }

    if (!current_user_can('edit_genealogy')) {
      wp_send_json_error('Permission denied');
    }

    $person_id = sanitize_text_field($_POST['personID']);
    $gedcom = sanitize_text_field($_POST['gedcom']);

    if (empty($person_id)) {
      wp_send_json_error('Person ID required');
    }

    if (empty($gedcom)) {
      wp_send_json_error('Tree selection required');
    }

    // Validate Person ID format
    if (!preg_match('/^[A-Za-z0-9_-]+$/', $person_id)) {
      wp_send_json_error('Invalid Person ID format. Use only letters, numbers, hyphens, and underscores.');
    }

    global $wpdb;
    $people_table = $wpdb->prefix . 'hp_people';

    $exists = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$people_table} WHERE personID = %s AND gedcom = %s",
      $person_id,
      $gedcom
    ));

    wp_send_json_success(array(
      'available' => ($exists == 0),
      'personID' => $person_id,
      'message' => ($exists == 0) ? 'Person ID is available' : 'Person ID is already in use'
    ));
  }

  /**
   * Lock/Reserve a Person ID
   */
  public static function lock_person_id()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_lock_person_id')) {
      wp_send_json_error('Security check failed');
      return;
    }

    if (!current_user_can('manage_options')) {
      wp_send_json_error('Permission denied');
      return;
    }

    $person_id = sanitize_text_field($_POST['personID']);
    $gedcom = sanitize_text_field($_POST['gedcom']);

    if (empty($person_id)) {
      wp_send_json_error('Person ID required');
      return;
    }

    if (empty($gedcom)) {
      wp_send_json_error('Tree selection required');
      return;
    }

    // Validate Person ID format
    if (!preg_match('/^[A-Za-z0-9_-]+$/', $person_id)) {
      wp_send_json_error('Invalid Person ID format. Use only letters, numbers, hyphens, and underscores.');
      return;
    }

    global $wpdb;
    $people_table = $wpdb->prefix . 'hp_people';

    // Check if ID already exists
    $exists = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$people_table} WHERE personID = %s AND gedcom = %s",
      $person_id,
      $gedcom
    ));

    if ($exists > 0) {
      wp_send_json_error('Person ID already exists and cannot be locked');
      return;
    }    // Create a placeholder/locked record to reserve the ID - Full TNG schema
    $lock_data = array(
      'personID' => $person_id,
      'gedcom' => $gedcom,
      'firstname' => '** LOCKED **',
      'lastname' => '** RESERVED **',
      'lnprefix' => '',
      'prefix' => '',
      'suffix' => '',
      'nickname' => '',
      'title' => '',
      'nameorder' => 0,
      'sex' => 'U',
      'birthdate' => '',
      'birthdatetr' => '0000-00-00',
      'birthplace' => '',
      'altbirthtype' => '',
      'altbirthdate' => '',
      'altbirthdatetr' => '0000-00-00',
      'altbirthplace' => '',
      'deathdate' => '',
      'deathdatetr' => '0000-00-00',
      'deathplace' => '',
      'burialdate' => '',
      'burialdatetr' => '0000-00-00',
      'burialplace' => '',
      'burialtype' => 0,
      'baptdate' => '',
      'baptdatetr' => '0000-00-00',
      'baptplace' => '',
      'confdate' => '',
      'confdatetr' => '0000-00-00',
      'confplace' => '',
      'initdate' => '',
      'initdatetr' => '0000-00-00',
      'initplace' => '',
      'endldate' => '',
      'endldatetr' => '0000-00-00',
      'endlplace' => '',
      'famc' => '',
      'metaphone' => '',
      'living' => 0,
      'private' => 1, // Mark as private so it doesn't show in public views
      'branch' => '',
      'changedate' => current_time('mysql'),
      'changedby' => wp_get_current_user()->user_login,
      'edituser' => '',
      'edittime' => 0
    );

    $result = $wpdb->insert($people_table, $lock_data);

    if ($result === false) {
      wp_send_json_error('Failed to lock Person ID: ' . $wpdb->last_error);
      return;
    }

    wp_send_json_success(array(
      'personID' => $person_id,
      'message' => 'Person ID locked successfully'
    ));
  }
}

// Register AJAX actions
add_action('wp_ajax_hp_generate_person_id', array('HP_Person_ID_Handler', 'generate_person_id'));
add_action('wp_ajax_hp_check_person_id', array('HP_Person_ID_Handler', 'check_person_id'));
add_action('wp_ajax_hp_lock_person_id', array('HP_Person_ID_Handler', 'lock_person_id'));
