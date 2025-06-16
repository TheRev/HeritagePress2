<?php

/**
 * AJAX Handler for Family Reports
 * Handles family statistics, reports, and analysis
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Family_Reports_Handler
{
  /**
   * Generate family statistics
   */
  public static function generate_family_statistics()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_family_statistics')) {
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

    $stats = array();

    // Total families
    $stats['total_families'] = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$families_table} WHERE gedcom = %s",
      $gedcom
    ));

    // Families with both spouses
    $stats['complete_families'] = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$families_table} 
       WHERE gedcom = %s AND husband IS NOT NULL AND husband != '' 
       AND wife IS NOT NULL AND wife != ''",
      $gedcom
    ));

    // Families with only husband
    $stats['husband_only'] = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$families_table} 
       WHERE gedcom = %s AND husband IS NOT NULL AND husband != '' 
       AND (wife IS NULL OR wife = '')",
      $gedcom
    ));

    // Families with only wife
    $stats['wife_only'] = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$families_table} 
       WHERE gedcom = %s AND (husband IS NULL OR husband = '') 
       AND wife IS NOT NULL AND wife != ''",
      $gedcom
    ));

    // Families with children
    $stats['families_with_children'] = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(DISTINCT famc) FROM {$people_table} 
       WHERE gedcom = %s AND famc IS NOT NULL AND famc != ''",
      $gedcom
    ));

    // Average children per family
    $total_children = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$people_table} 
       WHERE gedcom = %s AND famc IS NOT NULL AND famc != ''",
      $gedcom
    ));
    
    $stats['avg_children'] = $stats['families_with_children'] > 0 
      ? round($total_children / $stats['families_with_children'], 2) 
      : 0;

    // Living families
    $stats['living_families'] = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$families_table} WHERE gedcom = %s AND living = 1",
      $gedcom
    ));

    // Private families
    $stats['private_families'] = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$families_table} WHERE gedcom = %s AND private = 1",
      $gedcom
    ));

    wp_send_json_success($stats);
  }

  /**
   * Generate marriage date analysis
   */
  public static function analyze_marriage_dates()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_marriage_analysis')) {
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

    // Get families with marriage dates
    $results = $wpdb->get_results($wpdb->prepare(
      "SELECT familyID, marrdate, divdate FROM {$families_table} 
       WHERE gedcom = %s AND marrdate IS NOT NULL AND marrdate != ''
       ORDER BY marrdate",
      $gedcom
    ));

    $analysis = array(
      'by_decade' => array(),
      'by_month' => array(),
      'total_with_dates' => count($results),
      'divorced' => 0,
      'common_dates' => array()
    );

    // Initialize month array
    for ($i = 1; $i <= 12; $i++) {
      $analysis['by_month'][$i] = 0;
    }

    $date_counts = array();

    foreach ($results as $family) {
      // Extract year and decade
      if (preg_match('/(\d{4})/', $family->marrdate, $matches)) {
        $year = intval($matches[1]);
        $decade = floor($year / 10) * 10;
        
        if (!isset($analysis['by_decade'][$decade])) {
          $analysis['by_decade'][$decade] = 0;
        }
        $analysis['by_decade'][$decade]++;
      }

      // Extract month
      if (preg_match('/^\d{1,2}\s+(\d{1,2})\s+\d{4}/', $family->marrdate, $matches) ||
          preg_match('/^(\d{1,2})\s+\w+\s+\d{4}/', $family->marrdate, $matches)) {
        $month = intval($matches[1]);
        if ($month >= 1 && $month <= 12) {
          $analysis['by_month'][$month]++;
        }
      }

      // Count divorced families
      if (!empty($family->divdate)) {
        $analysis['divorced']++;
      }

      // Track common dates
      $date = $family->marrdate;
      if (!isset($date_counts[$date])) {
        $date_counts[$date] = 0;
      }
      $date_counts[$date]++;
    }

    // Find most common dates (more than 1 occurrence)
    arsort($date_counts);
    foreach ($date_counts as $date => $count) {
      if ($count > 1) {
        $analysis['common_dates'][] = array('date' => $date, 'count' => $count);
      }
    }

    wp_send_json_success($analysis);
  }

  /**
   * Find families missing information
   */
  public static function find_incomplete_families()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_incomplete_families')) {
      wp_send_json_error('Security check failed');
      return;
    }

    if (!current_user_can('edit_genealogy')) {
      wp_send_json_error('Permission denied');
      return;
    }

    $gedcom = sanitize_text_field($_POST['gedcom']);
    $issue_type = sanitize_text_field($_POST['issue_type']);
    
    if (empty($gedcom)) {
      wp_send_json_error('Tree selection required');
      return;
    }

    global $wpdb;
    $families_table = $wpdb->prefix . 'hp_families';
    $people_table = $wpdb->prefix . 'hp_people';

    $issues = array();

    switch ($issue_type) {
      case 'no_spouses':
        $results = $wpdb->get_results($wpdb->prepare(
          "SELECT familyID FROM {$families_table} 
           WHERE gedcom = %s AND (husband IS NULL OR husband = '') 
           AND (wife IS NULL OR wife = '')",
          $gedcom
        ));
        foreach ($results as $family) {
          $issues[] = array(
            'family_id' => $family->familyID,
            'issue' => 'No husband or wife assigned'
          );
        }
        break;

      case 'no_marriage_date':
        $results = $wpdb->get_results($wpdb->prepare(
          "SELECT familyID, husband, wife FROM {$families_table} 
           WHERE gedcom = %s AND (marrdate IS NULL OR marrdate = '')",
          $gedcom
        ));
        foreach ($results as $family) {
          $issues[] = array(
            'family_id' => $family->familyID,
            'issue' => 'Missing marriage date',
            'husband' => $family->husband,
            'wife' => $family->wife
          );
        }
        break;

      case 'invalid_dates':
        $results = $wpdb->get_results($wpdb->prepare(
          "SELECT familyID, marrdate, divdate FROM {$families_table} 
           WHERE gedcom = %s AND (marrdate IS NOT NULL AND marrdate != '') 
           OR (divdate IS NOT NULL AND divdate != '')",
          $gedcom
        ));
        foreach ($results as $family) {
          $date_issues = array();
          
          // Check marriage date format
          if (!empty($family->marrdate) && !preg_match('/^\d{1,2}\s+\w+\s+\d{4}$/', $family->marrdate)) {
            $date_issues[] = 'Invalid marriage date format: ' . $family->marrdate;
          }
          
          // Check divorce date format
          if (!empty($family->divdate) && !preg_match('/^\d{1,2}\s+\w+\s+\d{4}$/', $family->divdate)) {
            $date_issues[] = 'Invalid divorce date format: ' . $family->divdate;
          }
          
          if (!empty($date_issues)) {
            $issues[] = array(
              'family_id' => $family->familyID,
              'issue' => implode('; ', $date_issues)
            );
          }
        }
        break;
    }

    wp_send_json_success(array('issues' => $issues));
  }

  /**
   * Generate family tree comparison report
   */
  public static function compare_family_trees()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_compare_trees')) {
      wp_send_json_error('Security check failed');
      return;
    }

    if (!current_user_can('edit_genealogy')) {
      wp_send_json_error('Permission denied');
      return;
    }

    $tree1 = sanitize_text_field($_POST['tree1']);
    $tree2 = sanitize_text_field($_POST['tree2']);
    
    if (empty($tree1) || empty($tree2)) {
      wp_send_json_error('Two trees required for comparison');
      return;
    }

    global $wpdb;
    $families_table = $wpdb->prefix . 'hp_families';

    // Get family counts for each tree
    $tree1_count = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$families_table} WHERE gedcom = %s",
      $tree1
    ));

    $tree2_count = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$families_table} WHERE gedcom = %s",
      $tree2
    ));

    // Find families that might exist in both trees (by similar husband/wife)
    $potential_duplicates = $wpdb->get_results($wpdb->prepare(
      "SELECT f1.familyID as family1, f1.husband as husband1, f1.wife as wife1,
              f2.familyID as family2, f2.husband as husband2, f2.wife as wife2
       FROM {$families_table} f1
       JOIN {$families_table} f2 ON (
         (f1.husband = f2.husband AND f1.husband IS NOT NULL AND f1.husband != '') OR
         (f1.wife = f2.wife AND f1.wife IS NOT NULL AND f1.wife != '')
       )
       WHERE f1.gedcom = %s AND f2.gedcom = %s AND f1.familyID != f2.familyID",
      $tree1,
      $tree2
    ));

    wp_send_json_success(array(
      'tree1_count' => $tree1_count,
      'tree2_count' => $tree2_count,
      'potential_duplicates' => $potential_duplicates
    ));
  }
}

// Register AJAX handlers
add_action('wp_ajax_hp_family_statistics', array('HP_Family_Reports_Handler', 'generate_family_statistics'));
add_action('wp_ajax_hp_marriage_analysis', array('HP_Family_Reports_Handler', 'analyze_marriage_dates'));
add_action('wp_ajax_hp_incomplete_families', array('HP_Family_Reports_Handler', 'find_incomplete_families'));
add_action('wp_ajax_hp_compare_trees', array('HP_Family_Reports_Handler', 'compare_family_trees'));
