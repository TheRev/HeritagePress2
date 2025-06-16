<?php

/**
 * AJAX Handler for Family Finder
 * Handles searching and finding families with autocomplete support
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Family_Finder_Handler
{
  /**
   * Find families for autocomplete/search
   */
  public static function find_families()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_find_families')) {
      wp_send_json_error('Security check failed');
      return;
    }

    if (!current_user_can('edit_genealogy')) {
      wp_send_json_error('Permission denied');
      return;
    }

    $search_term = sanitize_text_field($_POST['search']);
    $gedcom = sanitize_text_field($_POST['gedcom']);
    $limit = isset($_POST['limit']) ? min(50, intval($_POST['limit'])) : 20;

    if (empty($search_term)) {
      wp_send_json_success(array('families' => array()));
      return;
    }

    global $wpdb;
    $families_table = $wpdb->prefix . 'hp_families';
    $people_table = $wpdb->prefix . 'hp_people';

    // Search families by ID, husband/wife names, or description
    $search_like = '%' . $wpdb->esc_like($search_term) . '%';
    
    $sql = "SELECT DISTINCT f.familyID, f.husband, f.wife, f.living, f.private,
                   h.firstname AS husband_first, h.lastname AS husband_last,
                   w.firstname AS wife_first, w.lastname AS wife_last
            FROM {$families_table} f
            LEFT JOIN {$people_table} h ON f.husband = h.personID AND f.gedcom = h.gedcom
            LEFT JOIN {$people_table} w ON f.wife = w.personID AND f.gedcom = w.gedcom
            WHERE f.gedcom = %s 
            AND (
              f.familyID LIKE %s
              OR h.firstname LIKE %s
              OR h.lastname LIKE %s  
              OR w.firstname LIKE %s
              OR w.lastname LIKE %s
              OR CONCAT(h.firstname, ' ', h.lastname) LIKE %s
              OR CONCAT(w.firstname, ' ', w.lastname) LIKE %s
              OR CONCAT(h.lastname, ', ', h.firstname) LIKE %s
              OR CONCAT(w.lastname, ', ', w.firstname) LIKE %s
            )
            ORDER BY f.familyID
            LIMIT %d";

    $results = $wpdb->get_results($wpdb->prepare(
      $sql,
      $gedcom,
      $search_like, $search_like, $search_like, $search_like, $search_like,
      $search_like, $search_like, $search_like, $search_like,
      $limit
    ));

    $families = array();
    foreach ($results as $family) {
      $husband_name = '';
      if ($family->husband_first || $family->husband_last) {
        $husband_name = trim($family->husband_first . ' ' . $family->husband_last);
      }

      $wife_name = '';
      if ($family->wife_first || $family->wife_last) {
        $wife_name = trim($family->wife_first . ' ' . $family->wife_last);
      }

      $family_display = $family->familyID;
      if ($husband_name && $wife_name) {
        $family_display .= " - {$husband_name} & {$wife_name}";
      } elseif ($husband_name) {
        $family_display .= " - {$husband_name}";
      } elseif ($wife_name) {
        $family_display .= " - {$wife_name}";
      }

      $families[] = array(
        'id' => $family->familyID,
        'text' => $family_display,
        'husband' => $family->husband,
        'wife' => $family->wife,
        'husband_name' => $husband_name,
        'wife_name' => $wife_name,
        'living' => $family->living,
        'private' => $family->private
      );
    }

    wp_send_json_success(array('families' => $families));
  }

  /**
   * Get family details for display
   */
  public static function get_family_details()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_get_family_details')) {
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
      wp_send_json_error('Family ID and tree required');
      return;
    }

    global $wpdb;
    $families_table = $wpdb->prefix . 'hp_families';
    $people_table = $wpdb->prefix . 'hp_people';

    // Get family details
    $family = $wpdb->get_row($wpdb->prepare(
      "SELECT f.*, 
              h.firstname AS husband_first, h.lastname AS husband_last,
              h.birthdate AS husband_birth, h.deathdate AS husband_death,
              w.firstname AS wife_first, w.lastname AS wife_last,
              w.birthdate AS wife_birth, w.deathdate AS wife_death
       FROM {$families_table} f
       LEFT JOIN {$people_table} h ON f.husband = h.personID AND f.gedcom = h.gedcom
       LEFT JOIN {$people_table} w ON f.wife = w.personID AND f.gedcom = w.gedcom
       WHERE f.familyID = %s AND f.gedcom = %s",
      $family_id,
      $gedcom
    ));

    if (!$family) {
      wp_send_json_error('Family not found');
      return;
    }

    // Get children
    $children = $wpdb->get_results($wpdb->prepare(
      "SELECT personID, firstname, lastname, birthdate, deathdate, living, sex
       FROM {$people_table}
       WHERE gedcom = %s AND (famc = %s)
       ORDER BY birthdate, personID",
      $gedcom,
      $family_id
    ));

    wp_send_json_success(array(
      'family' => $family,
      'children' => $children
    ));
  }
}

// Register AJAX handlers
add_action('wp_ajax_hp_find_families', array('HP_Family_Finder_Handler', 'find_families'));
add_action('wp_ajax_hp_get_family_details', array('HP_Family_Finder_Handler', 'get_family_details'));
