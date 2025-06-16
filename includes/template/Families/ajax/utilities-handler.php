<?php

/**
 * AJAX Handler for Family Utilities
 * Handles merge, delete, validation, renumbering, and export operations
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Family_Utilities_Handler
{
  /**
   * Merge two families
   */
  public static function merge_families()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_merge_families')) {
      wp_send_json_error('Security check failed');
      return;
    }

    if (!current_user_can('edit_genealogy')) {
      wp_send_json_error('Permission denied');
      return;
    }

    $source_family = sanitize_text_field($_POST['source_family']);
    $target_family = sanitize_text_field($_POST['target_family']);
    $gedcom = sanitize_text_field($_POST['gedcom']);
    $keep_source = isset($_POST['keep_source']) ? (bool)$_POST['keep_source'] : false;

    if (empty($source_family) || empty($target_family) || empty($gedcom)) {
      wp_send_json_error('Source family, target family, and tree required');
      return;
    }

    if ($source_family === $target_family) {
      wp_send_json_error('Cannot merge a family with itself');
      return;
    }

    global $wpdb;
    $families_table = $wpdb->prefix . 'hp_families';
    $people_table = $wpdb->prefix . 'hp_people';

    // Start transaction
    $wpdb->query('START TRANSACTION');

    try {
      // Get both families
      $source = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$families_table} WHERE familyID = %s AND gedcom = %s",
        $source_family,
        $gedcom
      ));

      $target = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$families_table} WHERE familyID = %s AND gedcom = %s",
        $target_family,
        $gedcom
      ));

      if (!$source || !$target) {
        throw new Exception('One or both families not found');
      }

      // Update children to point to target family
      $wpdb->update(
        $people_table,
        array('famc' => $target_family),
        array('famc' => $source_family, 'gedcom' => $gedcom),
        array('%s'),
        array('%s', '%s')
      );

      // If not keeping source family, delete it
      if (!$keep_source) {
        $wpdb->delete(
          $families_table,
          array('familyID' => $source_family, 'gedcom' => $gedcom),
          array('%s', '%s')
        );
      }

      $wpdb->query('COMMIT');
      wp_send_json_success(array('message' => 'Families merged successfully'));
    } catch (Exception $e) {
      $wpdb->query('ROLLBACK');
      wp_send_json_error('Merge failed: ' . $e->getMessage());
    }
  }

  /**
   * Bulk delete families
   */
  public static function bulk_delete_families()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_bulk_delete_families')) {
      wp_send_json_error('Security check failed');
      return;
    }

    if (!current_user_can('edit_genealogy')) {
      wp_send_json_error('Permission denied');
      return;
    }

    $family_ids = isset($_POST['family_ids']) ? $_POST['family_ids'] : array();
    $gedcom = sanitize_text_field($_POST['gedcom']);

    if (empty($family_ids) || empty($gedcom)) {
      wp_send_json_error('Family IDs and tree required');
      return;
    }

    // Sanitize family IDs
    $family_ids = array_map('sanitize_text_field', $family_ids);

    global $wpdb;
    $families_table = $wpdb->prefix . 'hp_families';
    $people_table = $wpdb->prefix . 'hp_people';

    $wpdb->query('START TRANSACTION');

    try {
      $deleted_count = 0;

      foreach ($family_ids as $family_id) {
        // Clear family references from people
        $wpdb->update(
          $people_table,
          array('famc' => ''),
          array('famc' => $family_id, 'gedcom' => $gedcom),
          array('%s'),
          array('%s', '%s')
        );

        // Clear spouse references
        $wpdb->update(
          $people_table,
          array('fams' => ''),
          array('fams' => $family_id, 'gedcom' => $gedcom),
          array('%s'),
          array('%s', '%s')
        );

        // Delete the family
        $result = $wpdb->delete(
          $families_table,
          array('familyID' => $family_id, 'gedcom' => $gedcom),
          array('%s', '%s')
        );

        if ($result) {
          $deleted_count++;
        }
      }

      $wpdb->query('COMMIT');
      wp_send_json_success(array(
        'message' => "Successfully deleted {$deleted_count} families",
        'deleted_count' => $deleted_count
      ));
    } catch (Exception $e) {
      $wpdb->query('ROLLBACK');
      wp_send_json_error('Delete failed: ' . $e->getMessage());
    }
  }

  /**
   * Validate family data
   */
  public static function validate_families()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_validate_families')) {
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
    $people_table = $wpdb->prefix . 'hp_people';

    $issues = array();

    // Check for families with invalid spouse references
    $invalid_spouses = $wpdb->get_results($wpdb->prepare(
      "SELECT f.familyID, f.husband, f.wife
       FROM {$families_table} f
       LEFT JOIN {$people_table} h ON f.husband = h.personID AND f.gedcom = h.gedcom
       LEFT JOIN {$people_table} w ON f.wife = w.personID AND f.gedcom = w.gedcom
       WHERE f.gedcom = %s
       AND ((f.husband IS NOT NULL AND f.husband != '' AND h.personID IS NULL)
            OR (f.wife IS NOT NULL AND f.wife != '' AND w.personID IS NULL))",
      $gedcom
    ));

    foreach ($invalid_spouses as $family) {
      $issue = "Family {$family->familyID}: ";
      if (!empty($family->husband)) {
        $husband_exists = $wpdb->get_var($wpdb->prepare(
          "SELECT COUNT(*) FROM {$people_table} WHERE personID = %s AND gedcom = %s",
          $family->husband,
          $gedcom
        ));
        if (!$husband_exists) {
          $issue .= "Invalid husband reference ({$family->husband}) ";
        }
      }
      if (!empty($family->wife)) {
        $wife_exists = $wpdb->get_var($wpdb->prepare(
          "SELECT COUNT(*) FROM {$people_table} WHERE personID = %s AND gedcom = %s",
          $family->wife,
          $gedcom
        ));
        if (!$wife_exists) {
          $issue .= "Invalid wife reference ({$family->wife}) ";
        }
      }
      $issues[] = array('type' => 'invalid_spouse', 'message' => trim($issue));
    }

    // Check for orphaned children (famc points to non-existent family)
    $orphaned_children = $wpdb->get_results($wpdb->prepare(
      "SELECT p.personID, p.famc
       FROM {$people_table} p
       LEFT JOIN {$families_table} f ON p.famc = f.familyID AND p.gedcom = f.gedcom
       WHERE p.gedcom = %s AND p.famc IS NOT NULL AND p.famc != ''
       AND f.familyID IS NULL",
      $gedcom
    ));

    foreach ($orphaned_children as $child) {
      $issues[] = array(
        'type' => 'orphaned_child',
        'message' => "Person {$child->personID}: References non-existent family {$child->famc}"
      );
    }

    // Check for duplicate family IDs (shouldn't happen but let's verify)
    $duplicates = $wpdb->get_results($wpdb->prepare(
      "SELECT familyID, COUNT(*) as count
       FROM {$families_table}
       WHERE gedcom = %s
       GROUP BY familyID
       HAVING count > 1",
      $gedcom
    ));

    foreach ($duplicates as $dup) {
      $issues[] = array(
        'type' => 'duplicate_id',
        'message' => "Duplicate family ID: {$dup->familyID} ({$dup->count} occurrences)"
      );
    }

    wp_send_json_success(array(
      'issues' => $issues,
      'total_issues' => count($issues)
    ));
  }

  /**
   * Renumber families
   */
  public static function renumber_families()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_renumber_families')) {
      wp_send_json_error('Security check failed');
      return;
    }

    if (!current_user_can('edit_genealogy')) {
      wp_send_json_error('Permission denied');
      return;
    }

    $gedcom = sanitize_text_field($_POST['gedcom']);
    $start_number = isset($_POST['start_number']) ? intval($_POST['start_number']) : 1;
    $preview_only = isset($_POST['preview_only']) ? (bool)$_POST['preview_only'] : false;

    if (empty($gedcom)) {
      wp_send_json_error('Tree selection required');
      return;
    }

    global $wpdb;
    $families_table = $wpdb->prefix . 'hp_families';
    $people_table = $wpdb->prefix . 'hp_people';

    // Get all families ordered by current ID
    $families = $wpdb->get_results($wpdb->prepare(
      "SELECT familyID FROM {$families_table}
       WHERE gedcom = %s
       ORDER BY CAST(SUBSTRING(familyID, 2) AS UNSIGNED)",
      $gedcom
    ));

    $renumber_plan = array();
    $current_number = $start_number;

    foreach ($families as $family) {
      $old_id = $family->familyID;
      $new_id = 'F' . $current_number;

      if ($old_id !== $new_id) {
        $renumber_plan[] = array(
          'old_id' => $old_id,
          'new_id' => $new_id
        );
      }

      $current_number++;
    }

    if ($preview_only) {
      wp_send_json_success(array(
        'preview' => $renumber_plan,
        'total_changes' => count($renumber_plan)
      ));
      return;
    }

    // Execute renumbering
    $wpdb->query('START TRANSACTION');

    try {
      $updated_count = 0;

      foreach ($renumber_plan as $change) {
        // Update family table
        $wpdb->update(
          $families_table,
          array('familyID' => $change['new_id']),
          array('familyID' => $change['old_id'], 'gedcom' => $gedcom),
          array('%s'),
          array('%s', '%s')
        );

        // Update people references
        $wpdb->update(
          $people_table,
          array('famc' => $change['new_id']),
          array('famc' => $change['old_id'], 'gedcom' => $gedcom),
          array('%s'),
          array('%s', '%s')
        );

        $wpdb->update(
          $people_table,
          array('fams' => $change['new_id']),
          array('fams' => $change['old_id'], 'gedcom' => $gedcom),
          array('%s'),
          array('%s', '%s')
        );

        $updated_count++;
      }

      $wpdb->query('COMMIT');
      wp_send_json_success(array(
        'message' => "Successfully renumbered {$updated_count} families",
        'updated_count' => $updated_count
      ));
    } catch (Exception $e) {
      $wpdb->query('ROLLBACK');
      wp_send_json_error('Renumbering failed: ' . $e->getMessage());
    }
  }

  /**
   * Export families to various formats
   */
  public static function export_families()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_export_families')) {
      wp_send_json_error('Security check failed');
      return;
    }

    if (!current_user_can('edit_genealogy')) {
      wp_send_json_error('Permission denied');
      return;
    }

    $gedcom_tree = sanitize_text_field($_POST['gedcom']);
    $format = sanitize_text_field($_POST['format']);
    $include_private = isset($_POST['include_private']) ? (bool)$_POST['include_private'] : false;

    if (empty($gedcom_tree) || empty($format)) {
      wp_send_json_error('Tree and format required');
      return;
    }

    global $wpdb;
    $families_table = $wpdb->prefix . 'hp_families';
    $people_table = $wpdb->prefix . 'hp_people';

    // Build query based on privacy settings
    $privacy_clause = $include_private ? '' : ' AND f.private != 1';

    $families = $wpdb->get_results($wpdb->prepare(
      "SELECT f.*,
              h.firstname AS husband_first, h.lastname AS husband_last,
              w.firstname AS wife_first, w.lastname AS wife_last
       FROM {$families_table} f
       LEFT JOIN {$people_table} h ON f.husband = h.personID AND f.gedcom = h.gedcom
       LEFT JOIN {$people_table} w ON f.wife = w.personID AND f.gedcom = w.gedcom
       WHERE f.gedcom = %s{$privacy_clause}
       ORDER BY f.familyID",
      $gedcom_tree
    ));

    switch ($format) {
      case 'csv':
        $output = self::export_families_csv($families);
        break;
      case 'json':
        $output = self::export_families_json($families);
        break;
      case 'xml':
        $output = self::export_families_xml($families);
        break;
      default:
        wp_send_json_error('Unsupported export format');
        return;
    }

    wp_send_json_success(array(
      'data' => $output,
      'filename' => "families_{$gedcom_tree}_{$format}." . $format,
      'count' => count($families)
    ));
  }

  private static function export_families_csv($families)
  {
    $output = "Family ID,Husband ID,Husband Name,Wife ID,Wife Name,Marriage Date,Divorce Date,Marriage Place,Living,Private\n";

    foreach ($families as $family) {
      $husband_name = trim(($family->husband_first ?? '') . ' ' . ($family->husband_last ?? ''));
      $wife_name = trim(($family->wife_first ?? '') . ' ' . ($family->wife_last ?? ''));

      $output .= sprintf(
        '"%s","%s","%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
        $family->familyID,
        $family->husband ?? '',
        $husband_name,
        $family->wife ?? '',
        $wife_name,
        $family->marrdate ?? '',
        $family->divdate ?? '',
        $family->marrplace ?? '',
        $family->living ? 'Yes' : 'No',
        $family->private ? 'Yes' : 'No'
      );
    }

    return $output;
  }

  private static function export_families_json($families)
  {
    return json_encode($families, JSON_PRETTY_PRINT);
  }

  private static function export_families_xml($families)
  {
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<families>\n";

    foreach ($families as $family) {
      $xml .= "  <family>\n";
      $xml .= "    <familyID>" . htmlspecialchars($family->familyID) . "</familyID>\n";
      $xml .= "    <husband>" . htmlspecialchars($family->husband ?? '') . "</husband>\n";
      $xml .= "    <wife>" . htmlspecialchars($family->wife ?? '') . "</wife>\n";
      $xml .= "    <marriageDate>" . htmlspecialchars($family->marrdate ?? '') . "</marriageDate>\n";
      $xml .= "    <divorceDate>" . htmlspecialchars($family->divdate ?? '') . "</divorceDate>\n";
      $xml .= "    <marriagePlace>" . htmlspecialchars($family->marrplace ?? '') . "</marriagePlace>\n";
      $xml .= "    <living>" . ($family->living ? 'true' : 'false') . "</living>\n";
      $xml .= "    <private>" . ($family->private ? 'true' : 'false') . "</private>\n";
      $xml .= "  </family>\n";
    }

    $xml .= "</families>";
    return $xml;
  }
}

// Register AJAX handlers
add_action('wp_ajax_hp_merge_families', array('HP_Family_Utilities_Handler', 'merge_families'));
add_action('wp_ajax_hp_bulk_delete_families', array('HP_Family_Utilities_Handler', 'bulk_delete_families'));
add_action('wp_ajax_hp_validate_families', array('HP_Family_Utilities_Handler', 'validate_families'));
add_action('wp_ajax_hp_renumber_families', array('HP_Family_Utilities_Handler', 'renumber_families'));
add_action('wp_ajax_hp_export_families', array('HP_Family_Utilities_Handler', 'export_families'));
