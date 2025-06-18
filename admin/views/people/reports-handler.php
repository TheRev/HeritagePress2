<?php

/**
 * AJAX Handler for People Reports
 * Handles report generation and export functionality
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_People_Reports_Handler
{

  /**
   * Generate people report
   */
  public static function generate_people_report()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'heritagepress_people_action')) {
      wp_die('Security check failed');
    }

    if (!current_user_can('view_genealogy')) {
      wp_send_json_error('Permission denied');
    }

    $report_type = sanitize_text_field($_POST['report_type']);
    $tree = sanitize_text_field($_POST['tree']);

    global $wpdb;
    $people_table = $wpdb->prefix . 'hp_people';

    $report_data = array();

    switch ($report_type) {
      case 'statistics':
        $report_data = self::generate_statistics_report($tree);
        break;

      case 'living_people':
        $report_data = self::generate_living_people_report($tree);
        break;

      case 'missing_info':
        $report_data = self::generate_missing_info_report($tree);
        break;

      case 'recent_changes':
        $report_data = self::generate_recent_changes_report($tree);
        break;

      case 'birthdays':
        $report_data = self::generate_birthdays_report($tree);
        break;

      default:
        wp_send_json_error('Invalid report type');
    }

    wp_send_json_success(array(
      'report_type' => $report_type,
      'tree' => $tree,
      'data' => $report_data,
      'generated_at' => current_time('mysql')
    ));
  }

  /**
   * Export people report
   */
  public static function export_people_report()
  {
    // Verify nonce
    if (!wp_verify_nonce($_GET['_wpnonce'], 'heritagepress_people_action')) {
      wp_die('Security check failed');
    }

    if (!current_user_can('view_genealogy')) {
      wp_die('Permission denied');
    }

    $report_type = sanitize_text_field($_GET['report']);
    $tree = sanitize_text_field($_GET['tree']);

    // Generate report data
    $_POST['report_type'] = $report_type;
    $_POST['tree'] = $tree;
    $_POST['_wpnonce'] = $_GET['_wpnonce'];

    self::generate_people_report();
    $response = json_decode(ob_get_clean(), true);

    if (!$response['success']) {
      wp_die('Failed to generate report');
    }

    $data = $response['data'];

    // Set headers for download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="people_report_' . $report_type . '_' . date('Y-m-d') . '.csv"');

    // Generate CSV
    $output = fopen('php://output', 'w');

    // Write headers based on report type
    switch ($report_type) {
      case 'statistics':
        fputcsv($output, array('Statistic', 'Count'));
        foreach ($data as $stat => $count) {
          fputcsv($output, array($stat, $count));
        }
        break;

      case 'living_people':
        fputcsv($output, array('Person ID', 'Name', 'Birth Date', 'Birth Place', 'Sex'));
        foreach ($data as $person) {
          fputcsv($output, array(
            $person['personID'],
            $person['name'],
            $person['birthdate'],
            $person['birthplace'],
            $person['sex']
          ));
        }
        break;

      case 'missing_info':
        fputcsv($output, array('Person ID', 'Name', 'Missing Fields'));
        foreach ($data as $person) {
          fputcsv($output, array(
            $person['personID'],
            $person['name'],
            implode(', ', $person['missing_fields'])
          ));
        }
        break;

      case 'recent_changes':
        fputcsv($output, array('Person ID', 'Name', 'Changed By', 'Change Date'));
        foreach ($data as $person) {
          fputcsv($output, array(
            $person['personID'],
            $person['name'],
            $person['changedby'],
            $person['changedate']
          ));
        }
        break;

      case 'birthdays':
        fputcsv($output, array('Person ID', 'Name', 'Birth Date', 'Age'));
        foreach ($data as $person) {
          fputcsv($output, array(
            $person['personID'],
            $person['name'],
            $person['birthdate'],
            $person['age']
          ));
        }
        break;
    }

    fclose($output);
    exit;
  }

  /**
   * Generate statistics report
   */
  private static function generate_statistics_report($tree)
  {
    global $wpdb;
    $people_table = $wpdb->prefix . 'hp_people';

    $where_clause = $tree ? $wpdb->prepare("WHERE gedcom = %s", $tree) : "";

    $stats = array();

    // Total people
    $stats['Total People'] = $wpdb->get_var("SELECT COUNT(*) FROM {$people_table} {$where_clause}");

    // Living people
    $living_where = $tree ? "WHERE gedcom = %s AND living = 1" : "WHERE living = 1";
    $stats['Living People'] = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$people_table} {$living_where}", $tree));

    // Male/Female counts
    $male_where = $tree ? "WHERE gedcom = %s AND sex = 'M'" : "WHERE sex = 'M'";
    $stats['Males'] = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$people_table} {$male_where}", $tree));

    $female_where = $tree ? "WHERE gedcom = %s AND sex = 'F'" : "WHERE sex = 'F'";
    $stats['Females'] = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$people_table} {$female_where}", $tree));

    // Private people
    $private_where = $tree ? "WHERE gedcom = %s AND private = 1" : "WHERE private = 1";
    $stats['Private People'] = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$people_table} {$private_where}", $tree));

    return $stats;
  }

  /**
   * Generate living people report
   */
  private static function generate_living_people_report($tree)
  {
    global $wpdb;
    $people_table = $wpdb->prefix . 'hp_people';

    $where_clause = "WHERE living = 1";
    $params = array();

    if ($tree) {
      $where_clause .= " AND gedcom = %s";
      $params[] = $tree;
    }

    $query = "SELECT personID, firstname, lastname, birthdate, birthplace, sex
                  FROM {$people_table} {$where_clause}
                  ORDER BY lastname, firstname";

    $results = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);

    foreach ($results as &$person) {
      $person['name'] = trim($person['firstname'] . ' ' . $person['lastname']);
    }

    return $results;
  }

  /**
   * Generate missing information report
   */
  private static function generate_missing_info_report($tree)
  {
    global $wpdb;
    $people_table = $wpdb->prefix . 'hp_people';

    $where_clause = "WHERE 1=1";
    $params = array();

    if ($tree) {
      $where_clause .= " AND gedcom = %s";
      $params[] = $tree;
    }

    $query = "SELECT personID, firstname, lastname, birthdate, birthplace, deathdate, deathplace, sex
                  FROM {$people_table} {$where_clause}
                  ORDER BY lastname, firstname";

    $results = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);

    $missing_info = array();

    foreach ($results as $person) {
      $missing_fields = array();

      if (empty($person['birthdate'])) $missing_fields[] = 'Birth Date';
      if (empty($person['birthplace'])) $missing_fields[] = 'Birth Place';
      if (empty($person['sex'])) $missing_fields[] = 'Sex';
      if (empty($person['deathdate']) && empty($person['deathplace'])) {
        // Check if person should have death info (basic heuristic)
        $birth_year = preg_match('/\b(\d{4})\b/', $person['birthdate'], $matches) ? intval($matches[1]) : 0;
        if ($birth_year && (date('Y') - $birth_year) > 100) {
          $missing_fields[] = 'Death Date/Place';
        }
      }

      if (!empty($missing_fields)) {
        $missing_info[] = array(
          'personID' => $person['personID'],
          'name' => trim($person['firstname'] . ' ' . $person['lastname']),
          'missing_fields' => $missing_fields
        );
      }
    }

    return $missing_info;
  }

  /**
   * Generate recent changes report
   */
  private static function generate_recent_changes_report($tree)
  {
    global $wpdb;
    $people_table = $wpdb->prefix . 'hp_people';

    $where_clause = "WHERE changedate >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $params = array();

    if ($tree) {
      $where_clause .= " AND gedcom = %s";
      $params[] = $tree;
    }

    $query = "SELECT personID, firstname, lastname, changedby, changedate
                  FROM {$people_table} {$where_clause}
                  ORDER BY changedate DESC";

    $results = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);

    foreach ($results as &$person) {
      $person['name'] = trim($person['firstname'] . ' ' . $person['lastname']);
    }

    return $results;
  }

  /**
   * Generate birthdays report
   */
  private static function generate_birthdays_report($tree)
  {
    global $wpdb;
    $people_table = $wpdb->prefix . 'hp_people';

    $where_clause = "WHERE living = 1 AND birthdate IS NOT NULL AND birthdate != ''";
    $params = array();

    if ($tree) {
      $where_clause .= " AND gedcom = %s";
      $params[] = $tree;
    }

    $query = "SELECT personID, firstname, lastname, birthdate
                  FROM {$people_table} {$where_clause}
                  ORDER BY
                    MONTH(STR_TO_DATE(birthdate, '%Y-%m-%d')),
                    DAY(STR_TO_DATE(birthdate, '%Y-%m-%d'))";

    $results = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);

    foreach ($results as &$person) {
      $person['name'] = trim($person['firstname'] . ' ' . $person['lastname']);

      // Calculate age
      $birth_year = preg_match('/\b(\d{4})\b/', $person['birthdate'], $matches) ? intval($matches[1]) : 0;
      $person['age'] = $birth_year ? (date('Y') - $birth_year) : 'Unknown';
    }

    return $results;
  }
}

// Register AJAX actions
add_action('wp_ajax_hp_generate_people_report', array('HP_People_Reports_Handler', 'generate_people_report'));
add_action('wp_ajax_hp_export_people_report', array('HP_People_Reports_Handler', 'export_people_report'));
