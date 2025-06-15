<?php

/**
 * HeritagePress Date System Test
 *
 * Test script to verify date parsing and validation functionality
 * Run this after implementing the date system to ensure everything works
 *
 * @package HeritagePress
 * @since 1.0.0
 */

// Only run this in admin or CLI context
if (!defined('ABSPATH') && !defined('WP_CLI')) {
  exit('Direct access not allowed');
}

// Include the date parser (adjust path as needed)
require_once __DIR__ . '/includes/class-hp-date-parser.php';

/**
 * Test the date parsing functionality
 */
function test_hp_date_parsing()
{
  echo "<h2>HeritagePress Date System Test</h2>\n";

  // Test cases
  $test_dates = [
    '2 OCT 1822',
    'OCT 1822',
    '1822',
    'ABT 1820',
    'BEF 1828',
    'AFT 1825',
    'BET 1820 AND 1825',
    '1822-10-02',
    '10/02/1822',
    'INVALID DATE',
    '30 FEB 1822', // Invalid date
    '15 DECEMBER 1850',
    'EST 1822',
    'CALC 1820'
  ];

  echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
  echo "<tr><th>Input</th><th>Valid</th><th>Sortable</th><th>Year</th><th>Month</th><th>Day</th><th>Qualifier</th><th>Formatted</th></tr>\n";

  foreach ($test_dates as $test_date) {
    $result = HP_Date_Parser::parse_date($test_date);

    echo "<tr>";
    echo "<td>" . htmlspecialchars($test_date) . "</td>";
    echo "<td>" . ($result['is_valid'] ? 'Yes' : 'No') . "</td>";
    echo "<td>" . htmlspecialchars($result['sortable'] ?: 'N/A') . "</td>";
    echo "<td>" . htmlspecialchars($result['year'] ?: 'N/A') . "</td>";
    echo "<td>" . htmlspecialchars($result['month'] ?: 'N/A') . "</td>";
    echo "<td>" . htmlspecialchars($result['day'] ?: 'N/A') . "</td>";
    echo "<td>" . htmlspecialchars($result['qualifier'] ?: 'N/A') . "</td>";
    echo "<td>" . htmlspecialchars(HP_Date_Parser::format_for_display($result)) . "</td>";
    echo "</tr>\n";
  }

  echo "</table>\n";

  // Test validation
  echo "<h3>Validation Test</h3>\n";

  $validation_test = HP_Date_Parser::validate_and_suggest('30 FEB 1822');
  echo "<p><strong>Testing invalid date '30 FEB 1822':</strong></p>\n";
  echo "<ul>\n";
  echo "<li>Valid: " . ($validation_test['is_valid'] ? 'Yes' : 'No') . "</li>\n";
  if (!empty($validation_test['suggestions'])) {
    echo "<li>Suggestions: " . implode(', ', $validation_test['suggestions']) . "</li>\n";
  }
  if (!empty($validation_test['warnings'])) {
    echo "<li>Warnings: " . implode(', ', $validation_test['warnings']) . "</li>\n";
  }
  echo "</ul>\n";

  echo "<h3>Precision Test</h3>\n";

  $precision_tests = [
    '2 OCT 1822' => 'day',
    'OCT 1822' => 'month',
    '1822' => 'year'
  ];

  foreach ($precision_tests as $date => $expected) {
    $parsed = HP_Date_Parser::parse_date($date);
    $precision = HP_Date_Parser::get_precision($parsed);
    $match = $precision === $expected ? 'PASS' : 'FAIL';
    echo "<p>{$date} → {$precision} (expected {$expected}) <strong>{$match}</strong></p>\n";
  }

  echo "<h3>Test Complete</h3>\n";
  echo "<p>If all tests show expected results, the date system is working correctly.</p>\n";
}

// Run the test if this file is accessed directly
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
  echo "<!DOCTYPE html><html><head><title>Date System Test</title></head><body>\n";
  test_hp_date_parsing();
  echo "</body></html>\n";
}
