<?php

/**
 * HeritagePress Date Parser
 *
 * Handles conversion between human-readable genealogical dates and sortable database dates
 * Supports uncertain dates, partial dates, and various date formats commonly used in genealogy
 *
 * @package HeritagePress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Date_Parser
{

  /**
   * Date qualifiers and their meanings
   * @var array
   */
  private static $qualifiers = [
    'ABT' => 'about',
    'ABOUT' => 'about',
    'CIRCA' => 'about',
    'C.' => 'about',
    'CA' => 'about',
    'BEF' => 'before',
    'BEFORE' => 'before',
    'AFT' => 'after',
    'AFTER' => 'after',
    'BET' => 'between',
    'BETWEEN' => 'between',
    'FROM' => 'from',
    'TO' => 'to',
    'EST' => 'estimated',
    'CALC' => 'calculated'
  ];

  /**
   * Month abbreviations
   * @var array
   */
  private static $months = [
    'JAN' => '01',
    'FEB' => '02',
    'MAR' => '03',
    'APR' => '04',
    'MAY' => '05',
    'JUN' => '06',
    'JUL' => '07',
    'AUG' => '08',
    'SEP' => '09',
    'OCT' => '10',
    'NOV' => '11',
    'DEC' => '12',
    'JANUARY' => '01',
    'FEBRUARY' => '02',
    'MARCH' => '03',
    'APRIL' => '04',
    'JUNE' => '06',
    'JULY' => '07',
    'AUGUST' => '08',
    'SEPTEMBER' => '09',
    'OCTOBER' => '10',
    'NOVEMBER' => '11',
    'DECEMBER' => '12'
  ];

  /**
   * Parse a human-readable date into a sortable date
   *
   * @param string $date_string The human-readable date
   * @return array Array containing parsed components
   */
  public static function parse_date($date_string)
  {
    if (empty($date_string)) {
      return [
        'sortable' => null,
        'year' => null,
        'month' => null,
        'day' => null,
        'qualifier' => null,
        'is_valid' => false,
        'original' => $date_string
      ];
    }

    $date_string = trim(strtoupper($date_string));
    $original = $date_string;

    // Initialize result
    $result = [
      'sortable' => null,
      'year' => null,
      'month' => null,
      'day' => null,
      'qualifier' => null,
      'is_valid' => false,
      'original' => $original
    ];

    // Check for qualifiers
    $qualifier = self::extract_qualifier($date_string);
    if ($qualifier) {
      $result['qualifier'] = $qualifier;
      $date_string = self::remove_qualifier($date_string);
    }

    // Handle range dates (BET 1820 AND 1825)
    if (strpos($date_string, ' AND ') !== false) {
      return self::parse_range_date($date_string, $result);
    }

    // Try different date formats
    $parsed = self::try_parse_formats($date_string);

    if ($parsed) {
      $result = array_merge($result, $parsed);
      $result['sortable'] = self::create_sortable_date($result);
      $result['is_valid'] = true;
    }

    return $result;
  }

  /**
   * Extract qualifier from date string
   *
   * @param string $date_string
   * @return string|null
   */
  private static function extract_qualifier($date_string)
  {
    foreach (self::$qualifiers as $abbr => $full) {
      if (strpos($date_string, $abbr . ' ') === 0) {
        return $full;
      }
    }
    return null;
  }

  /**
   * Remove qualifier from date string
   *
   * @param string $date_string
   * @return string
   */
  private static function remove_qualifier($date_string)
  {
    foreach (self::$qualifiers as $abbr => $full) {
      if (strpos($date_string, $abbr . ' ') === 0) {
        return trim(substr($date_string, strlen($abbr)));
      }
    }
    return $date_string;
  }

  /**
   * Parse range dates like "BET 1820 AND 1825"
   *
   * @param string $date_string
   * @param array $result
   * @return array
   */
  private static function parse_range_date($date_string, $result)
  {
    $parts = explode(' AND ', $date_string);
    if (count($parts) === 2) {
      $start_date = self::try_parse_formats(trim($parts[0]));
      $end_date = self::try_parse_formats(trim($parts[1]));

      if ($start_date && $end_date) {
        // Use the start date for sorting
        $result = array_merge($result, $start_date);
        $result['sortable'] = self::create_sortable_date($start_date);
        $result['is_valid'] = true;
        $result['end_year'] = $end_date['year'];
      }
    }
    return $result;
  }

  /**
   * Try various date formats
   *
   * @param string $date_string
   * @return array|null
   */
  private static function try_parse_formats($date_string)
  {
    $date_string = trim($date_string);

    // Format: "2 OCT 1822" or "02 OCT 1822"
    if (preg_match('/^(\d{1,2})\s+([A-Z]{3,9})\s+(\d{4})$/', $date_string, $matches)) {
      $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
      $month = self::$months[$matches[2]] ?? null;
      $year = $matches[3];

      if ($month) {
        return ['year' => $year, 'month' => $month, 'day' => $day];
      }
    }

    // Format: "OCT 1822" or "OCTOBER 1822"
    if (preg_match('/^([A-Z]{3,9})\s+(\d{4})$/', $date_string, $matches)) {
      $month = self::$months[$matches[1]] ?? null;
      $year = $matches[2];

      if ($month) {
        return ['year' => $year, 'month' => $month, 'day' => null];
      }
    }

    // Format: "1822" (year only)
    if (preg_match('/^(\d{4})$/', $date_string, $matches)) {
      return ['year' => $matches[1], 'month' => null, 'day' => null];
    }

    // Format: "1822-10-02" (ISO format)
    if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $date_string, $matches)) {
      return [
        'year' => $matches[1],
        'month' => str_pad($matches[2], 2, '0', STR_PAD_LEFT),
        'day' => str_pad($matches[3], 2, '0', STR_PAD_LEFT)
      ];
    }

    // Format: "10/02/1822" or "2/10/1822" (US format)
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date_string, $matches)) {
      // Assume MM/DD/YYYY format
      return [
        'year' => $matches[3],
        'month' => str_pad($matches[1], 2, '0', STR_PAD_LEFT),
        'day' => str_pad($matches[2], 2, '0', STR_PAD_LEFT)
      ];
    }

    return null;
  }

  /**
   * Create a sortable date from parsed components
   *
   * @param array $parsed
   * @return string|null
   */
  private static function create_sortable_date($parsed)
  {
    if (!$parsed['year']) {
      return null;
    }

    $year = $parsed['year'];
    $month = $parsed['month'] ?? '01';
    $day = $parsed['day'] ?? '01';

    // Validate the date
    if (checkdate($month, $day, $year)) {
      return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    // If invalid, try with day 1
    if (checkdate($month, 1, $year)) {
      return sprintf('%04d-%02d-01', $year, $month);
    }

    // If still invalid, use January 1
    return sprintf('%04d-01-01', $year);
  }

  /**
   * Format a date for display
   *
   * @param array $parsed_date
   * @return string
   */
  public static function format_for_display($parsed_date)
  {
    if (!$parsed_date['is_valid']) {
      return $parsed_date['original'];
    }

    $display = '';

    if ($parsed_date['qualifier']) {
      $display .= strtoupper($parsed_date['qualifier']) . ' ';
    }

    if ($parsed_date['day']) {
      $display .= (int)$parsed_date['day'] . ' ';
    }

    if ($parsed_date['month']) {
      $month_names = array_flip(self::$months);
      $display .= $month_names[$parsed_date['month']] . ' ';
    }

    $display .= $parsed_date['year'];

    if (isset($parsed_date['end_year'])) {
      $display .= ' AND ' . $parsed_date['end_year'];
    }

    return trim($display);
  }

  /**
   * Validate a date string and return suggestions
   *
   * @param string $date_string
   * @return array
   */
  public static function validate_and_suggest($date_string)
  {
    $parsed = self::parse_date($date_string);

    $result = [
      'is_valid' => $parsed['is_valid'],
      'parsed' => $parsed,
      'suggestions' => [],
      'warnings' => []
    ];

    if (!$parsed['is_valid'] && !empty($date_string)) {
      $result['suggestions'] = self::generate_suggestions($date_string);
    }

    if ($parsed['is_valid']) {
      $result['warnings'] = self::check_date_warnings($parsed);
    }

    return $result;
  }

  /**
   * Generate suggestions for invalid dates
   *
   * @param string $date_string
   * @return array
   */
  private static function generate_suggestions($date_string)
  {
    $suggestions = [];

    // Common format suggestions
    $suggestions[] = "Try formats like: '2 OCT 1822', 'OCT 1822', '1822', 'ABT 1820'";

    // Check for common mistakes
    if (preg_match('/\d/', $date_string)) {
      if (strpos($date_string, '/') !== false) {
        $suggestions[] = "For dates with slashes, use MM/DD/YYYY format";
      }

      if (preg_match('/\d{4}/', $date_string)) {
        $suggestions[] = "Year detected. Try adding month abbreviation (JAN, FEB, etc.)";
      }
    }

    return $suggestions;
  }

  /**
   * Check for date warnings
   *
   * @param array $parsed
   * @return array
   */
  private static function check_date_warnings($parsed)
  {
    $warnings = [];
    $current_year = date('Y');

    if ($parsed['year'] > $current_year) {
      $warnings[] = "Future date detected. Please verify the year.";
    }

    if ($parsed['year'] < 1000) {
      $warnings[] = "Very early date. Please verify the year.";
    }

    return $warnings;
  }

  /**
   * Convert a display date to a sortable date
   *
   * @param string $date_string
   * @return string|null
   */
  public static function to_sortable($date_string)
  {
    $parsed = self::parse_date($date_string);
    return $parsed['sortable'];
  }

  /**
   * Get date precision level
   *
   * @param array $parsed_date
   * @return string
   */
  public static function get_precision($parsed_date)
  {
    if (!$parsed_date['is_valid']) {
      return 'invalid';
    }

    if ($parsed_date['day']) {
      return 'day';
    } elseif ($parsed_date['month']) {
      return 'month';
    } else {
      return 'year';
    }
  }
}
