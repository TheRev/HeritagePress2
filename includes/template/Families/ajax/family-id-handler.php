<?php

/**
 * AJAX Handler for Family ID Generation and Checking
 * Handles family ID generation and availability checking
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Family_ID_Handler
{
  /**
   * Generate a new Family ID
   */
  public static function generate_family_id()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_generate_family_id')) {
      wp_send_json_error('Security check failed');
      return;
    }

    if (!current_user_can('edit_genealogy')) {
      wp_send_json_error('Permission denied');
      return;
    }

    $gedcom = sanitize_text_field($_POST['gedcom']);
    if (empty($gedcom)) {
      wp_send_json_error('Tree selection required');
      return;
    }

    global $wpdb;
    $families_table = $wpdb->prefix . 'hp_families';

    // Get the highest numbered family ID for this tree
    $last_id = $wpdb->get_var($wpdb->prepare(
      "SELECT familyID FROM {$families_table}
             WHERE gedcom = %s
             AND familyID REGEXP '^F[0-9]+$'
             ORDER BY CAST(SUBSTRING(familyID, 2) AS UNSIGNED) DESC
             LIMIT 1",
      $gedcom
    ));

    // Extract number and increment
    $next_number = 1;
    if ($last_id && preg_match('/^F(\d+)$/', $last_id, $matches)) {
      $next_number = intval($matches[1]) + 1;
    }

    // Generate new ID
    $new_id = 'F' . $next_number;

    // Double-check it doesn't exist (race condition protection)
    $exists = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$families_table} WHERE gedcom = %s AND familyID = %s",
      $gedcom,
      $new_id
    ));

    if ($exists > 0) {
      // If it exists, try incrementing until we find an available one
      do {
        $next_number++;
        $new_id = 'F' . $next_number;
        $exists = $wpdb->get_var($wpdb->prepare(
          "SELECT COUNT(*) FROM {$families_table} WHERE gedcom = %s AND familyID = %s",
          $gedcom,
          $new_id
        ));
      } while ($exists > 0 && $next_number < 100000); // Safety limit
    }

    wp_send_json_success(array('family_id' => $new_id));
  }

  /**
   * Check if a Family ID is available
   */
  public static function check_family_id_availability()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_check_family_id')) {
      wp_send_json_error('Security check failed');
      return;
    }

    if (!current_user_can('edit_genealogy')) {
      wp_send_json_error('Permission denied');
      return;
    }

    $family_id = sanitize_text_field($_POST['family_id']);
    $gedcom = sanitize_text_field($_POST['gedcom']);

    if (empty($family_id) || empty($gedcom)) {
      wp_send_json_error('Family ID and tree selection required');
      return;
    }

    // Validate family ID format
    if (!preg_match('/^F\d+$/', $family_id)) {
      wp_send_json_error('Invalid family ID format. Must be F followed by numbers (e.g., F1, F123)');
      return;
    }

    global $wpdb;
    $families_table = $wpdb->prefix . 'hp_families';

    $exists = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$families_table} WHERE gedcom = %s AND familyID = %s",
      $gedcom,
      $family_id
    ));

    wp_send_json_success(array('available' => ($exists == 0)));
  }
}

// Register AJAX handlers
add_action('wp_ajax_hp_generate_family_id', array('HP_Family_ID_Handler', 'generate_family_id'));
add_action('wp_ajax_hp_check_family_id', array('HP_Family_ID_Handler', 'check_family_id_availability'));
