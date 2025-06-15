<?php

/**
 * HeritagePress GEDCOM Utilities
 *
 * Common utility functions for GEDCOM processing
 *
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_GEDCOM_Utils
{
  /**
   * Convert GEDCOM date to standard format
   *
   * @param string $gedcom_date GEDCOM date string
   * @return array Parsed date information
   */
  public function convert_gedcom_date($gedcom_date)
  {
    $result = array(
      'original' => $gedcom_date,
      'date' => '',
      'year' => '',
      'is_range' => false,
      'is_approximate' => false,
      'display_date' => '',
    );

    if (empty($gedcom_date)) {
      return $result;
    }

    // Check for modifiers
    $is_approximate = false;
    if (preg_match('/(ABT|CAL|EST)\s+(.+)/i', $gedcom_date, $matches)) {
      $is_approximate = true;
      $result['is_approximate'] = true;
      $gedcom_date = $matches[2];
    }

    // Check for date ranges
    $is_range = false;
    if (preg_match('/(BET|FROM)\s+(.+)\s+(AND|TO)\s+(.+)/i', $gedcom_date, $matches)) {
      $is_range = true;
      $result['is_range'] = true;

      $date1 = $this->parse_gedcom_date_part($matches[2]);
      $date2 = $this->parse_gedcom_date_part($matches[4]);

      $result['date'] = $date1['date'];
      $result['year'] = $date1['year'];
      $result['date_end'] = $date2['date'];
      $result['year_end'] = $date2['year'];

      $result['display_date'] = $is_approximate ? 'About ' : '';
      $result['display_date'] .= $date1['display'] . ' to ' . $date2['display'];

      return $result;
    }

    // Regular date
    $parsed = $this->parse_gedcom_date_part($gedcom_date);

    $result['date'] = $parsed['date'];
    $result['year'] = $parsed['year'];
    $result['display_date'] = $is_approximate ? 'About ' . $parsed['display'] : $parsed['display'];

    return $result;
  }

  /**
   * Parse a part of a GEDCOM date
   *
   * @param string $date_part Date string
   * @return array Parsed date part
   */
  private function parse_gedcom_date_part($date_part)
  {
    $result = array(
      'date' => '',
      'year' => '',
      'display' => $date_part,
    );

    // Try to extract standard components
    if (preg_match('/(\d{1,2})\s+([A-Z]{3})\s+(\d{4})/i', $date_part, $matches)) {
      $day = $matches[1];
      $month = $this->convert_month_to_number($matches[2]);
      $year = $matches[3];

      $result['date'] = sprintf('%04d-%02d-%02d', $year, $month, $day);
      $result['year'] = $year;
      $result['display'] = $day . ' ' . $matches[2] . ' ' . $year;
    } elseif (preg_match('/([A-Z]{3})\s+(\d{4})/i', $date_part, $matches)) {
      $month = $this->convert_month_to_number($matches[1]);
      $year = $matches[2];

      $result['date'] = sprintf('%04d-%02d-00', $year, $month);
      $result['year'] = $year;
      $result['display'] = $matches[1] . ' ' . $year;
    } elseif (preg_match('/(\d{4})/i', $date_part, $matches)) {
      $year = $matches[1];

      $result['date'] = $year . '-00-00';
      $result['year'] = $year;
      $result['display'] = $year;
    }

    return $result;
  }

  /**
   * Convert month abbreviation to number
   *
   * @param string $month Month abbreviation
   * @return int Month number (1-12)
   */
  public function convert_month_to_number($month)
  {
    $month = strtoupper($month);
    $months = array(
      'JAN' => 1,
      'FEB' => 2,
      'MAR' => 3,
      'APR' => 4,
      'MAY' => 5,
      'JUN' => 6,
      'JUL' => 7,
      'AUG' => 8,
      'SEP' => 9,
      'OCT' => 10,
      'NOV' => 11,
      'DEC' => 12
    );

    return isset($months[$month]) ? $months[$month] : 0;
  }

  /**
   * Extract year from a date string
   *
   * @param string $date_string Date string
   * @return string Year or empty string
   */
  public function extract_year_from_date($date_string)
  {
    if (empty($date_string)) {
      return '';
    }

    if (preg_match('/\b(\d{4})\b/', $date_string, $matches)) {
      return $matches[1];
    }

    return '';
  }

  /**
   * Process a GEDCOM name into components
   *
   * @param string $name_string GEDCOM name string
   * @return array Name components
   */
  public function process_gedcom_name($name_string)
  {
    $result = array(
      'original' => $name_string,
      'given' => '',
      'surname' => '',
      'prefix' => '',
      'suffix' => '',
      'full' => '',
    );

    if (empty($name_string)) {
      return $result;
    }

    // Handle surname in slashes format: Given /Surname/ Suffix
    if (preg_match('/^(.*)\s*\/([^\/]*)\/\s*(.*)$/', $name_string, $matches)) {
      $given = trim($matches[1]);
      $surname = trim($matches[2]);
      $suffix = trim($matches[3]);

      $result['given'] = $given;
      $result['surname'] = $surname;

      // Check for name prefix (titles)
      if (preg_match('/^((?:Mr|Mrs|Dr|Rev|Sir|Lady|Col|Capt|Prof|Jr|Sr)[\.|\s])\s+(.*)$/i', $given, $prefix_matches)) {
        $result['prefix'] = trim($prefix_matches[1]);
        $result['given'] = trim($prefix_matches[2]);
      }

      // Check for suffix in the suffix part
      if (!empty($suffix)) {
        $result['suffix'] = $suffix;
      }

      // Build full name
      $full = $result['given'];
      if (!empty($result['surname'])) {
        $full .= ' ' . $result['surname'];
      }
      if (!empty($result['suffix'])) {
        $full .= ' ' . $result['suffix'];
      }
      $result['full'] = $full;
    } else {
      // No slashes, assume entire string is the full name
      $result['full'] = $name_string;

      // Try to break it up by spaces
      $parts = preg_split('/\s+/', $name_string);
      if (count($parts) > 1) {
        // Assume last part is surname, everything else is given
        $result['surname'] = array_pop($parts);
        $result['given'] = implode(' ', $parts);
      } else {
        // Just one word
        $result['given'] = $name_string;
      }
    }

    return $result;
  }

  /**
   * Process a GEDCOM place into components
   *
   * @param string $place_string GEDCOM place string
   * @return array Place components
   */
  public function process_gedcom_place($place_string)
  {
    $result = array(
      'original' => $place_string,
      'parts' => array(),
      'city' => '',
      'county' => '',
      'state' => '',
      'country' => '',
      'normalized' => '',
    );

    if (empty($place_string)) {
      return $result;
    }

    // Split by commas
    $parts = array_map('trim', explode(',', $place_string));
    $result['parts'] = $parts;

    // Assign parts based on position
    $count = count($parts);
    if ($count >= 1) {
      $result['city'] = $parts[0];
    }
    if ($count >= 2) {
      $result['county'] = $parts[1];
    }
    if ($count >= 3) {
      $result['state'] = $parts[2];
    }
    if ($count >= 4) {
      $result['country'] = $parts[3];
    }

    // Generate normalized version
    $result['normalized'] = $place_string;

    return $result;
  }

  /**
   * Determine if an event tag is a custom event
   *
   * @param string $tag Event tag
   * @return bool True if custom
   */
  public function is_custom_event($tag)
  {
    // Standard GEDCOM 5.5.1 event tags
    $standard_events = array(
      'BIRT',
      'CHR',
      'DEAT',
      'BURI',
      'CREM',
      'ADOP',
      'BAPM',
      'BARM',
      'BASM',
      'BLES',
      'CHRA',
      'CONF',
      'FCOM',
      'ORDN',
      'NATU',
      'EMIG',
      'IMMI',
      'CENS',
      'PROB',
      'WILL',
      'GRAD',
      'RETI',
      'EVEN',
      'MARR',
      'MARB',
      'MARC',
      'MARL',
      'MARS',
      'ANUL',
      'CENS',
      'DIV',
      'DIVF',
      'ENGA',
      'MARR',
      'MARB',
      'MARC',
      'MARL',
      'MARS'
    );

    return !in_array($tag, $standard_events);
  }

  /**
   * Format bytes to human readable format
   *
   * @param int $bytes Bytes
   * @return string Formatted size
   */
  public function format_bytes($bytes)
  {
    if ($bytes >= 1073741824) {
      return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
      return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
      return number_format($bytes / 1024, 2) . ' KB';
    } else {
      return number_format($bytes) . ' bytes';
    }
  }
}
