<?php

/**
 * Enhanced Date Utilities for HeritagePress
 *
 * Provides utilities for working with dual storage date system
 * Uses sortable date fields for calculations while preserving display formats
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Date_Utils
{

  /**
   * Calculate age from sortable birth and death dates
   *
   * @param string $birth_sortable Sortable birth date (YYYY-MM-DD format)
   * @param string $death_sortable Sortable death date (YYYY-MM-DD format)
   * @return int|null Age in years or null if cannot calculate
   */
  public static function calculate_age($birth_sortable, $death_sortable = null)
  {
    if (empty($birth_sortable) || $birth_sortable === '0000-00-00') {
      return null;
    }

    $end_date = $death_sortable && $death_sortable !== '0000-00-00'
      ? $death_sortable
      : date('Y-m-d');

    try {
      $birth = new DateTime($birth_sortable);
      $death = new DateTime($end_date);
      return $birth->diff($death)->y;
    } catch (Exception $e) {
      return null;
    }
  }

  /**
   * Extract year from sortable date
   *
   * @param string $sortable_date Date in YYYY-MM-DD format
   * @return int|null Year or null if invalid
   */
  public static function extract_year($sortable_date)
  {
    if (empty($sortable_date) || $sortable_date === '0000-00-00') {
      return null;
    }

    $parts = explode('-', $sortable_date);
    $year = intval($parts[0]);
    return $year > 0 ? $year : null;
  }

  /**
   * Get age statistics using sortable dates
   *
   * @param object $wpdb WordPress database object
   * @param string $people_table Table name
   * @param string $tree_filter Optional tree filter
   * @return array Statistics array
   */
  public static function get_age_statistics($wpdb, $people_table, $tree_filter = null)
  {
    $where_clause = "";
    if ($tree_filter) {
      $where_clause = $wpdb->prepare("AND gedcom = %s", $tree_filter);
    }

    // Use sortable date fields for reliable calculations
    $query = "SELECT
                    birthdatetr,
                    deathdatetr
                  FROM {$people_table}
                  WHERE birthdatetr IS NOT NULL
                    AND birthdatetr != ''
                    AND birthdatetr != '0000-00-00'
                    AND deathdatetr IS NOT NULL
                    AND deathdatetr != ''
                    AND deathdatetr != '0000-00-00'
                    {$where_clause}";

    $results = $wpdb->get_results($query, 'ARRAY_A');

    $ages = [];
    foreach ($results as $row) {
      $age = self::calculate_age($row['birthdatetr'], $row['deathdatetr']);
      if ($age !== null) {
        $ages[] = $age;
      }
    }

    if (empty($ages)) {
      return [
        'avg_age' => 0,
        'min_age' => 0,
        'max_age' => 0,
        'total_with_both_dates' => 0
      ];
    }

    return [
      'avg_age' => round(array_sum($ages) / count($ages), 1),
      'min_age' => min($ages),
      'max_age' => max($ages),
      'total_with_both_dates' => count($ages)
    ];
  }

  /**
   * Get birth year distribution using sortable dates
   *
   * @param object $wpdb WordPress database object
   * @param string $people_table Table name
   * @param string $tree_filter Optional tree filter
   * @return array Year distribution
   */
  public static function get_birth_year_distribution($wpdb, $people_table, $tree_filter = null)
  {
    $where_clause = "";
    if ($tree_filter) {
      $where_clause = $wpdb->prepare("AND gedcom = %s", $tree_filter);
    }

    $query = "SELECT
                    LEFT(birthdatetr, 4) as birth_year,
                    COUNT(*) as count
                  FROM {$people_table}
                  WHERE birthdatetr IS NOT NULL
                    AND birthdatetr != ''
                    AND birthdatetr != '0000-00-00'
                    AND LEFT(birthdatetr, 4) != '0000'
                    {$where_clause}
                  GROUP BY LEFT(birthdatetr, 4)
                  ORDER BY birth_year";

    return $wpdb->get_results($query, 'ARRAY_A');
  }
  /**
   * Format display date with fallback to alternative date and standardized formatting
   *
   * @param array $person Person record with date fields
   * @param string $date_type Type of date: 'birth', 'death', 'burial', 'bapt'
   * @return string Formatted date or empty string
   */
  public static function format_display_date($person, $date_type)
  {
    $primary_field = $date_type . 'date';
    $alt_field = 'alt' . $date_type . 'date';
    $sortable_field = $date_type . 'datetr';
    $alt_sortable_field = 'alt' . $date_type . 'datetr';

    if (!empty($person[$primary_field])) {
      $formatted = self::standardize_date_format($person[$primary_field], $person[$sortable_field] ?? '');
      return esc_html($formatted);
    } elseif (!empty($person[$alt_field])) {
      $formatted = self::standardize_date_format($person[$alt_field], $person[$alt_sortable_field] ?? '');
      $suffix = $date_type === 'birth' ? ' (chr.)' : ' (alt.)';
      return esc_html($formatted) . '<em>' . $suffix . '</em>';
    }

    return '';
  }
  /**
   * Standardize a date format for consistent display across HeritagePress
   * Converts various date formats to standardized "d Mon YYYY" format
   *
   * @param string $date_string Raw date string in any format
   * @param string $sortable_date Optional sortable date for fallback
   * @return string Formatted date string or original if parsing fails
   */
  public static function standardize_date_format($date_string, $sortable_date = '')
  {
    if (empty($date_string)) {
      return '';
    }

    // Handle approximate dates and special prefixes
    $prefixes = array();
    $clean_date = $date_string;

    // Extract common genealogy prefixes
    if (preg_match('/^(Abt\.?|About|Circa|C\.?|Before|Bef\.?|After|Aft\.?|Between)\s+(.+)/i', $date_string, $matches)) {
      $prefixes[] = $matches[1];
      $clean_date = $matches[2];
    }

    // Handle "Between X and Y" dates
    if (preg_match('/^(.+?)\s+(and|to|-)\s+(.+)$/i', $clean_date, $matches)) {
      $date1 = self::parse_and_format_single_date($matches[1]);
      $date2 = self::parse_and_format_single_date($matches[3]);
      if ($date1 && $date2) {
        return implode(' ', $prefixes) . ($prefixes ? ' ' : '') . $date1 . ' - ' . $date2;
      }
    }

    // Parse single date
    $formatted = self::parse_and_format_single_date($clean_date);
    if ($formatted) {
      return implode(' ', $prefixes) . ($prefixes ? ' ' : '') . $formatted;
    }

    // If parsing fails, try using sortable date
    if (!empty($sortable_date) && $sortable_date !== '0000-00-00') {
      $formatted = self::parse_and_format_single_date($sortable_date);
      if ($formatted) {
        return implode(' ', $prefixes) . ($prefixes ? ' ' : '') . $formatted;
      }
    }

    // Return original if all else fails
    return $date_string;
  }

  /**
   * Parse and format a single date to "d Mon YYYY" format
   *
   * @param string $date_string Date string to parse
   * @return string|false Formatted date or false if parsing fails
   */
  private static function parse_and_format_single_date($date_string)
  {
    if (empty($date_string)) {
      return false;
    }

    // Clean the date string
    $clean_date = trim($date_string);

    // Handle various date formats
    $formats = array(
      'Y-m-d',           // 1990-05-10
      'm/d/Y',           // 5/10/1990
      'm-d-Y',           // 5-10-1990
      'd/m/Y',           // 10/5/1990
      'd-m-Y',           // 10-5-1990
      'j M Y',           // 10 May 1990
      'j F Y',           // 10 May 1990
      'M j, Y',          // May 10, 1990
      'F j, Y',          // May 10, 1990
      'd M Y',           // 10 May 1990
      'd F Y',           // 10 May 1990
      'j-M-Y',           // 10-May-1990
      'j.M.Y',           // 10.May.1990
      'Y',               // 1990 (year only)
      'M Y',             // May 1990
      'F Y',             // May 1990
    );

    foreach ($formats as $format) {
      $date = DateTime::createFromFormat($format, $clean_date);
      if ($date !== false) {
        // Format as "j M Y" (e.g., "10 May 1990")
        return $date->format('j M Y');
      }
    }

    // Try strtotime as fallback
    $timestamp = strtotime($clean_date);
    if ($timestamp !== false) {
      return date('j M Y', $timestamp);
    }

    return false;
  }

  /**
   * Get sortable date for ordering
   *
   * @param array $person Person record with date fields
   * @param string $date_type Type of date: 'birth', 'death', 'burial', 'bapt'
   * @return string Sortable date or empty string
   */
  public static function get_sortable_date($person, $date_type)
  {
    $sortable_field = $date_type . 'datetr';
    $alt_sortable_field = 'alt' . $date_type . 'datetr';

    if (!empty($person[$sortable_field]) && $person[$sortable_field] !== '0000-00-00') {
      return $person[$sortable_field];
    } elseif (!empty($person[$alt_sortable_field]) && $person[$alt_sortable_field] !== '0000-00-00') {
      return $person[$alt_sortable_field];
    }

    return '';
  }

  /**
   * Create WHERE clause for date range filtering
   *
   * @param string $date_field The sortable date field name (e.g., 'birthdatetr')
   * @param string $start_date Start date in YYYY-MM-DD format (optional)
   * @param string $end_date End date in YYYY-MM-DD format (optional)
   * @param object $wpdb WordPress database object for prepare()
   * @return string WHERE clause condition
   */
  public static function create_date_range_where($date_field, $start_date = '', $end_date = '', $wpdb = null)
  {
    $conditions = [];

    // Base condition - exclude invalid dates
    $conditions[] = "{$date_field} IS NOT NULL AND {$date_field} != '' AND {$date_field} != '0000-00-00'";

    if (!empty($start_date) && $wpdb) {
      $conditions[] = $wpdb->prepare("{$date_field} >= %s", $start_date);
    }

    if (!empty($end_date) && $wpdb) {
      $conditions[] = $wpdb->prepare("{$date_field} <= %s", $end_date);
    }

    return '(' . implode(' AND ', $conditions) . ')';
  }

  /**
   * Parse user-friendly date input for filtering
   *
   * @param string $input User input like "1800", "1800-1850", "after 1900", etc.
   * @return array Array with 'start' and 'end' dates in YYYY-MM-DD format
   */
  public static function parse_date_range_input($input)
  {
    $input = trim(strtolower($input));
    $result = ['start' => '', 'end' => ''];

    // Handle "after YYYY" or "aft YYYY"
    if (preg_match('/(?:after|aft)\s+(\d{4})/', $input, $matches)) {
      $result['start'] = $matches[1] . '-01-01';
      return $result;
    }

    // Handle "before YYYY" or "bef YYYY"
    if (preg_match('/(?:before|bef)\s+(\d{4})/', $input, $matches)) {
      $result['end'] = $matches[1] . '-12-31';
      return $result;
    }

    // Handle range "YYYY-YYYY" or "YYYY to YYYY"
    if (preg_match('/(\d{4})\s*(?:-|to)\s*(\d{4})/', $input, $matches)) {
      $result['start'] = $matches[1] . '-01-01';
      $result['end'] = $matches[2] . '-12-31';
      return $result;
    }

    // Handle single year "YYYY"
    if (preg_match('/^(\d{4})$/', $input, $matches)) {
      $result['start'] = $matches[1] . '-01-01';
      $result['end'] = $matches[1] . '-12-31';
      return $result;
    }

    // Handle full date formats
    if (preg_match('/(\d{4}-\d{2}-\d{2})/', $input, $matches)) {
      $result['start'] = $result['end'] = $matches[1];
      return $result;
    }

    return $result;
  }

  /**
   * Get people within date range
   *
   * @param object $wpdb WordPress database object
   * @param string $people_table Table name
   * @param string $date_type Date type: 'birth', 'death', 'burial', 'bapt'
   * @param string $start_date Start date in YYYY-MM-DD format
   * @param string $end_date End date in YYYY-MM-DD format
   * @param string $tree_filter Optional tree filter
   * @param int $limit Optional limit
   * @return array People records
   */
  public static function get_people_by_date_range($wpdb, $people_table, $date_type, $start_date = '', $end_date = '', $tree_filter = null, $limit = 100)
  {
    $sortable_field = $date_type . 'datetr';
    $alt_sortable_field = 'alt' . $date_type . 'datetr';

    $where_conditions = [];

    // Create date range conditions for both primary and alternative dates
    $primary_condition = self::create_date_range_where($sortable_field, $start_date, $end_date, $wpdb);
    $alt_condition = self::create_date_range_where($alt_sortable_field, $start_date, $end_date, $wpdb);

    $where_conditions[] = "({$primary_condition} OR {$alt_condition})";

    if ($tree_filter) {
      $where_conditions[] = $wpdb->prepare("gedcom = %s", $tree_filter);
    }

    $where_clause = implode(' AND ', $where_conditions);
    $limit_clause = $limit ? "LIMIT {$limit}" : '';

    $query = "SELECT personID, firstname, lastname, birthdate, birthdatetr, deathdate, deathdatetr,
                         altbirthdate, altbirthdatetr, gedcom, living, private
                  FROM {$people_table}
                  WHERE {$where_clause}
                  ORDER BY {$sortable_field}, {$alt_sortable_field}, lastname, firstname
                  {$limit_clause}";

    return $wpdb->get_results($query, 'ARRAY_A');
  }

  /**
   * Get comprehensive date statistics for reports
   *
   * @param object $wpdb WordPress database object
   * @param string $people_table Table name
   * @param string $tree_filter Optional tree filter
   * @return array Comprehensive statistics
   */
  public static function get_comprehensive_date_statistics($wpdb, $people_table, $tree_filter = null)
  {
    $where_clause = "";
    if ($tree_filter) {
      $where_clause = $wpdb->prepare("WHERE gedcom = %s", $tree_filter);
    }

    // Get basic counts
    $stats = [];

    $date_fields = ['birth' => 'birthdatetr', 'death' => 'deathdatetr', 'burial' => 'burialdatetr', 'bapt' => 'baptdatetr'];

    foreach ($date_fields as $type => $field) {
      $display_field = $type . 'date';
      $alt_field = 'alt' . $type . 'date';
      $alt_sortable = 'alt' . $type . 'datetr';

      // Count records with dates
      $query = "SELECT COUNT(*) as count FROM {$people_table} {$where_clause}";
      $total = $wpdb->get_var($query);

      $query = "SELECT COUNT(*) as count FROM {$people_table}
                      {$where_clause} " . ($where_clause ? "AND" : "WHERE") . "
                      (({$field} IS NOT NULL AND {$field} != '' AND {$field} != '0000-00-00') OR
                       ({$alt_sortable} IS NOT NULL AND {$alt_sortable} != '' AND {$alt_sortable} != '0000-00-00'))";
      $with_dates = $wpdb->get_var($query);

      $stats[$type] = [
        'total_records' => $total,
        'with_dates' => $with_dates,
        'without_dates' => $total - $with_dates,
        'percentage' => $total > 0 ? round(($with_dates / $total) * 100, 1) : 0
      ];
    }

    return $stats;
  }
}
