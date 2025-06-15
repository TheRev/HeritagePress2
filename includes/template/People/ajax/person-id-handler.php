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
    if (!wp_verify_nonce($_POST['_wpnonce'], 'heritagepress_people_action')) {
      wp_die('Security check failed');
    }

    if (!current_user_can('edit_genealogy')) {
      wp_send_json_error('Permission denied');
    }

    $gedcom = sanitize_text_field($_POST['gedcom']);
    if (empty($gedcom)) {
      wp_send_json_error('Tree selection required');
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
    if (!wp_verify_nonce($_POST['_wpnonce'], 'heritagepress_people_action')) {
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
}

// Register AJAX actions
add_action('wp_ajax_hp_generate_person_id', array('HP_Person_ID_Handler', 'generate_person_id'));
add_action('wp_ajax_hp_check_person_id', array('HP_Person_ID_Handler', 'check_person_id'));
