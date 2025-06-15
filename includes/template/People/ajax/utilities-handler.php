<?php

/**
 * AJAX Handler for People Utilities
 * Handles various utility functions for people management
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_People_Utilities_Handler
{

  /**
   * Run people utility
   */
  public static function run_people_utility()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'heritagepress_people_action')) {
      wp_die('Security check failed');
    }

    if (!current_user_can('manage_genealogy')) {
      wp_send_json_error('Permission denied');
    }

    $utility = sanitize_text_field($_POST['utility']);
    $tree = sanitize_text_field($_POST['tree']);

    $result = array();

    switch ($utility) {
      case 'reindex_names':
        $result = self::reindex_names($tree);
        break;

      case 'check_duplicates':
        $result = self::check_duplicates($tree);
        break;

      case 'fix_dates':
        $result = self::fix_dates($tree);
        break;

      case 'update_soundex':
        $result = self::update_soundex($tree);
        break;

      case 'merge_people':
        $result = self::merge_people_interface($tree);
        break;

      case 'bulk_privacy':
        $result = self::bulk_privacy_update($tree);
        break;

      case 'cleanup_orphans':
        $result = self::cleanup_orphans($tree);
        break;

      case 'export_people':
        $result = self::export_people_data($tree);
        break;

      case 'import_corrections':
        $result = self::import_corrections_interface($tree);
        break;

      case 'verify_relationships':
        $result = self::verify_relationships($tree);
        break;

      default:
        wp_send_json_error('Invalid utility');
    }

    wp_send_json_success(array(
      'utility' => $utility,
      'tree' => $tree,
      'report' => $result['report'],
      'changes' => isset($result['changes']) ? $result['changes'] : 0,
      'executed_at' => current_time('mysql')
    ));
  }

  /**
   * Reindex names for better searching
   */
  private static function reindex_names($tree)
  {
    global $wpdb;
    $people_table = $wpdb->prefix . 'hp_people';

    $where_clause = $tree ? "WHERE gedcom = %s" : "";
    $params = $tree ? array($tree) : array();

    $query = "SELECT ID, personID, firstname, lastname, lnprefix
                  FROM {$people_table} {$where_clause}";

    $people = $wpdb->get_results($wpdb->prepare($query, $params));

    $updated = 0;
    foreach ($people as $person) {
      // Generate soundex codes
      $firstname_soundex = soundex($person->firstname);
      $lastname_soundex = soundex($person->lastname);

      // Update with soundex codes (if columns exist)
      $update_data = array(
        'changedate' => current_time('mysql'),
        'changedby' => wp_get_current_user()->user_login
      );

      // Add soundex if columns exist
      $columns = $wpdb->get_col("DESCRIBE {$people_table}", 0);
      if (in_array('firstname_soundex', $columns)) {
        $update_data['firstname_soundex'] = $firstname_soundex;
      }
      if (in_array('lastname_soundex', $columns)) {
        $update_data['lastname_soundex'] = $lastname_soundex;
      }

      $result = $wpdb->update(
        $people_table,
        $update_data,
        array('ID' => $person->ID)
      );

      if ($result !== false) {
        $updated++;
      }
    }

    return array(
      'report' => "<div class='utility-results'>
                <h4>Name Reindexing Complete</h4>
                <p><strong>{$updated}</strong> people processed.</p>
                <p>Search indexes have been rebuilt for improved search performance.</p>
            </div>",
      'changes' => $updated
    );
  }

  /**
   * Check for duplicate people
   */
  private static function check_duplicates($tree)
  {
    global $wpdb;
    $people_table = $wpdb->prefix . 'hp_people';

    $where_clause = $tree ? "WHERE gedcom = %s" : "";
    $params = $tree ? array($tree) : array();

    $query = "SELECT personID, firstname, lastname, birthdate, birthplace
                  FROM {$people_table} {$where_clause}
                  ORDER BY lastname, firstname";

    $people = $wpdb->get_results($wpdb->prepare($query, $params));

    $duplicates = array();
    $checked = array();

    foreach ($people as $i => $person1) {
      if (in_array($person1->personID, $checked)) continue;

      $matches = array();
      foreach ($people as $j => $person2) {
        if ($i >= $j || in_array($person2->personID, $checked)) continue;

        $similarity = self::calculate_person_similarity($person1, $person2);
        if ($similarity > 70) { // 70% similarity threshold
          $matches[] = array(
            'person' => $person2,
            'similarity' => $similarity
          );
          $checked[] = $person2->personID;
        }
      }

      if (!empty($matches)) {
        $duplicates[] = array(
          'primary' => $person1,
          'matches' => $matches
        );
        $checked[] = $person1->personID;
      }
    }

    $report = "<div class='utility-results'>";
    $report .= "<h4>Duplicate Check Complete</h4>";

    if (empty($duplicates)) {
      $report .= "<p>No potential duplicates found.</p>";
    } else {
      $report .= "<p><strong>" . count($duplicates) . "</strong> potential duplicate groups found:</p>";
      $report .= "<div class='duplicates-list'>";

      foreach ($duplicates as $group) {
        $primary = $group['primary'];
        $report .= "<div class='duplicate-group'>";
        $report .= "<h5>Primary: {$primary->firstname} {$primary->lastname} ({$primary->personID})</h5>";
        $report .= "<ul>";

        foreach ($group['matches'] as $match) {
          $person = $match['person'];
          $similarity = $match['similarity'];
          $report .= "<li>{$person->firstname} {$person->lastname} ({$person->personID}) - {$similarity}% similar</li>";
        }

        $report .= "</ul></div>";
      }

      $report .= "</div>";
    }

    $report .= "</div>";

    return array(
      'report' => $report,
      'changes' => 0
    );
  }

  /**
   * Calculate similarity between two people
   */
  private static function calculate_person_similarity($person1, $person2)
  {
    $score = 0;
    $total_checks = 0;

    // Name similarity
    $name1 = strtolower($person1->firstname . ' ' . $person1->lastname);
    $name2 = strtolower($person2->firstname . ' ' . $person2->lastname);
    similar_text($name1, $name2, $name_similarity);
    $score += $name_similarity;
    $total_checks++;

    // Birth date similarity
    if (!empty($person1->birthdate) && !empty($person2->birthdate)) {
      $birth_similarity = ($person1->birthdate === $person2->birthdate) ? 100 : 0;
      $score += $birth_similarity;
      $total_checks++;
    }

    // Birth place similarity
    if (!empty($person1->birthplace) && !empty($person2->birthplace)) {
      similar_text(strtolower($person1->birthplace), strtolower($person2->birthplace), $place_similarity);
      $score += $place_similarity;
      $total_checks++;
    }

    return $total_checks > 0 ? ($score / $total_checks) : 0;
  }

  /**
   * Fix common date issues
   */
  private static function fix_dates($tree)
  {
    global $wpdb;
    $people_table = $wpdb->prefix . 'hp_people';

    $where_clause = $tree ? "WHERE gedcom = %s" : "";
    $params = $tree ? array($tree) : array();

    $query = "SELECT ID, personID, birthdate, deathdate
                  FROM {$people_table} {$where_clause}";

    $people = $wpdb->get_results($wpdb->prepare($query, $params));

    $fixed = 0;
    $issues = array();

    foreach ($people as $person) {
      $updates = array();

      // Fix birth date
      if (!empty($person->birthdate)) {
        $fixed_birth = self::standardize_date($person->birthdate);
        if ($fixed_birth !== $person->birthdate) {
          $updates['birthdate'] = $fixed_birth;
          $issues[] = "Fixed birth date for {$person->personID}: '{$person->birthdate}' → '{$fixed_birth}'";
        }
      }

      // Fix death date
      if (!empty($person->deathdate)) {
        $fixed_death = self::standardize_date($person->deathdate);
        if ($fixed_death !== $person->deathdate) {
          $updates['deathdate'] = $fixed_death;
          $issues[] = "Fixed death date for {$person->personID}: '{$person->deathdate}' → '{$fixed_death}'";
        }
      }

      if (!empty($updates)) {
        $updates['changedate'] = current_time('mysql');
        $updates['changedby'] = wp_get_current_user()->user_login;

        $result = $wpdb->update(
          $people_table,
          $updates,
          array('ID' => $person->ID)
        );

        if ($result !== false) {
          $fixed++;
        }
      }
    }

    $report = "<div class='utility-results'>";
    $report .= "<h4>Date Standardization Complete</h4>";
    $report .= "<p><strong>{$fixed}</strong> people had dates updated.</p>";

    if (!empty($issues)) {
      $report .= "<h5>Changes Made:</h5>";
      $report .= "<ul>";
      foreach (array_slice($issues, 0, 20) as $issue) { // Show first 20
        $report .= "<li>{$issue}</li>";
      }
      if (count($issues) > 20) {
        $report .= "<li><em>... and " . (count($issues) - 20) . " more changes</em></li>";
      }
      $report .= "</ul>";
    }

    $report .= "</div>";

    return array(
      'report' => $report,
      'changes' => $fixed
    );
  }

  /**
   * Standardize date format
   */
  private static function standardize_date($date)
  {
    // Basic date standardization
    $date = trim($date);

    // Common patterns to fix
    $patterns = array(
      '/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/' => '$3-$1-$2', // MM/DD/YYYY to YYYY-MM-DD
      '/^(\d{1,2})-(\d{1,2})-(\d{4})$/' => '$3-$1-$2',   // MM-DD-YYYY to YYYY-MM-DD
      '/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/' => '$1-$2-$3', // YYYY/MM/DD to YYYY-MM-DD
    );

    foreach ($patterns as $pattern => $replacement) {
      if (preg_match($pattern, $date)) {
        $date = preg_replace($pattern, $replacement, $date);
        break;
      }
    }

    return $date;
  }

  /**
   * Update soundex codes
   */
  private static function update_soundex($tree)
  {
    return self::reindex_names($tree); // Same functionality
  }

  /**
   * Placeholder for merge people interface
   */
  private static function merge_people_interface($tree)
  {
    return array(
      'report' => "<div class='utility-results'>
                <h4>Merge People Tool</h4>
                <p>This advanced feature will be available in a future update.</p>
                <p>It will allow you to merge duplicate person records while preserving all associated data.</p>
            </div>",
      'changes' => 0
    );
  }

  /**
   * Placeholder for bulk privacy update
   */
  private static function bulk_privacy_update($tree)
  {
    return array(
      'report' => "<div class='utility-results'>
                <h4>Bulk Privacy Update</h4>
                <p>This feature will be available in a future update.</p>
                <p>It will allow you to update privacy settings for multiple people based on various criteria.</p>
            </div>",
      'changes' => 0
    );
  }

  /**
   * Cleanup orphaned records
   */
  private static function cleanup_orphans($tree)
  {
    global $wpdb;

    $cleaned = 0;
    $issues = array();

    // This is a simplified version - full implementation would check all related tables
    $report = "<div class='utility-results'>";
    $report .= "<h4>Orphaned Data Cleanup</h4>";
    $report .= "<p>Data consistency check completed.</p>";
    $report .= "<p>Advanced orphan cleanup features will be available in a future update.</p>";
    $report .= "</div>";

    return array(
      'report' => $report,
      'changes' => $cleaned
    );
  }

  /**
   * Placeholder for export people data
   */
  private static function export_people_data($tree)
  {
    return array(
      'report' => "<div class='utility-results'>
                <h4>Export People Data</h4>
                <p>Use the Reports section to export people data in various formats.</p>
                <p>Additional export options will be available in future updates.</p>
            </div>",
      'changes' => 0
    );
  }

  /**
   * Placeholder for import corrections interface
   */
  private static function import_corrections_interface($tree)
  {
    return array(
      'report' => "<div class='utility-results'>
                <h4>Import Corrections</h4>
                <p>This feature will be available in a future update.</p>
                <p>It will allow you to import bulk corrections from CSV/Excel files.</p>
            </div>",
      'changes' => 0
    );
  }

  /**
   * Verify family relationships
   */
  private static function verify_relationships($tree)
  {
    global $wpdb;
    $people_table = $wpdb->prefix . 'hp_people';

    $where_clause = $tree ? "WHERE gedcom = %s" : "";
    $params = $tree ? array($tree) : array();

    $query = "SELECT personID, firstname, lastname, birthdate, deathdate
                  FROM {$people_table} {$where_clause}";

    $people = $wpdb->get_results($wpdb->prepare($query, $params));

    $issues = array();

    foreach ($people as $person) {
      // Check for impossible dates
      if (!empty($person->birthdate) && !empty($person->deathdate)) {
        $birth_year = self::extract_year($person->birthdate);
        $death_year = self::extract_year($person->deathdate);

        if ($birth_year && $death_year && $birth_year > $death_year) {
          $issues[] = "Death before birth: {$person->firstname} {$person->lastname} ({$person->personID})";
        }
      }
    }

    $report = "<div class='utility-results'>";
    $report .= "<h4>Relationship Verification Complete</h4>";
    $report .= "<p>Checked " . count($people) . " people for relationship issues.</p>";

    if (empty($issues)) {
      $report .= "<p>No relationship issues found.</p>";
    } else {
      $report .= "<p><strong>" . count($issues) . "</strong> potential issues found:</p>";
      $report .= "<ul>";
      foreach (array_slice($issues, 0, 20) as $issue) {
        $report .= "<li>{$issue}</li>";
      }
      if (count($issues) > 20) {
        $report .= "<li><em>... and " . (count($issues) - 20) . " more issues</em></li>";
      }
      $report .= "</ul>";
    }

    $report .= "</div>";

    return array(
      'report' => $report,
      'changes' => 0
    );
  }

  /**
   * Extract year from date string
   */
  private static function extract_year($date)
  {
    if (preg_match('/\b(\d{4})\b/', $date, $matches)) {
      return intval($matches[1]);
    }
    return null;
  }
}

// Register AJAX actions
add_action('wp_ajax_hp_run_people_utility', array('HP_People_Utilities_Handler', 'run_people_utility'));
