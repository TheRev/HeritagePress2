<?php

/**
 * Test Smart Tooltip Logic
 *
 * Test script to verify tooltips only show when providing useful information
 */

// WordPress environment setup
require_once '../../../wp-config.php';
require_once 'includes/class-hp-date-utils.php';

echo "<h1>Smart Tooltip Logic Test</h1>\n";

// Test scenarios for tooltip display
$test_cases = array(
  array(
    'name' => 'Same format - no tooltip needed',
    'birthdate' => '10 May 1990',
    'birthdatetr' => '1990-05-10',
    'expected_tooltip' => false
  ),
  array(
    'name' => 'Different format - tooltip helpful',
    'birthdate' => '1/16/1964',
    'birthdatetr' => '1964-01-16',
    'expected_tooltip' => true
  ),
  array(
    'name' => 'Approximate date - tooltip shows sortable',
    'birthdate' => 'Abt 1920',
    'birthdatetr' => '1920-00-00',
    'expected_tooltip' => true
  ),
  array(
    'name' => 'Year only - no tooltip if same',
    'birthdate' => '1950',
    'birthdatetr' => '1950-00-00',
    'expected_tooltip' => false
  ),
  array(
    'name' => 'Christening date - shows original',
    'birthdate' => '',
    'altbirthdate' => '15 Mar 1885',
    'altbirthdatetr' => '1885-03-15',
    'expected_tooltip' => false
  )
);

echo "<table border='1' cellpadding='8'>\n";
echo "<tr><th>Test Case</th><th>Original Date</th><th>Standardized Display</th><th>Tooltip</th><th>Result</th></tr>\n";

foreach ($test_cases as $test) {
  // Mock person record
  $person = array(
    'birthdate' => $test['birthdate'] ?? '',
    'altbirthdate' => $test['altbirthdate'] ?? '',
    'birthdatetr' => $test['birthdatetr'] ?? '',
    'altbirthdatetr' => $test['altbirthdatetr'] ?? ''
  );

  // Get display format
  $birth_display = HP_Date_Utils::format_display_date($person, 'birth');
  $sortable_date = HP_Date_Utils::get_sortable_date($person, 'birth');

  // Replicate the tooltip logic from browse-people.php
  $original_date = !empty($person['birthdate']) ? $person['birthdate'] : $person['altbirthdate'];
  $display_clean = strip_tags($birth_display);

  $tooltip_text = '';
  $show_original = ($original_date !== $display_clean);
  $show_sortable = ($sortable_date && $sortable_date !== '0000-00-00' && $display_clean !== $sortable_date);

  if ($show_original || $show_sortable) {
    $tooltip_parts = array();
    if ($show_original) {
      $date_type = !empty($person['birthdate']) ? 'Original' : 'Original (chr.)';
      $tooltip_parts[] = $date_type . ': ' . $original_date;
    }
    if ($show_sortable) {
      $tooltip_parts[] = 'Sortable: ' . $sortable_date;
    }
    $tooltip_text = implode(' | ', $tooltip_parts);
  }

  $has_tooltip = !empty($tooltip_text);
  $result = ($has_tooltip === $test['expected_tooltip']) ? '✅ PASS' : '❌ FAIL';

  echo "<tr>";
  echo "<td><strong>" . htmlspecialchars($test['name']) . "</strong></td>";
  echo "<td>" . htmlspecialchars($original_date) . "</td>";
  echo "<td>" . htmlspecialchars($birth_display) . "</td>";
  echo "<td>" . ($has_tooltip ? htmlspecialchars($tooltip_text) : '<em>No tooltip</em>') . "</td>";
  echo "<td>" . $result . "</td>";
  echo "</tr>\n";
}

echo "</table>\n";

echo "<h2>Summary</h2>\n";
echo "<p><strong>Smart Tooltip Rules:</strong></p>\n";
echo "<ul>\n";
echo "<li>✅ <strong>No tooltip</strong> when standardized display matches original date</li>\n";
echo "<li>✅ <strong>Show tooltip</strong> when original format was different (e.g., '1/16/1964' → '16 Jan 1964')</li>\n";
echo "<li>✅ <strong>Show tooltip</strong> when sortable date provides useful context (e.g., 'Abt 1920' sorts as '1920-00-00')</li>\n";
echo "<li>✅ <strong>Show tooltip</strong> for christening/burial dates that differ from display</li>\n";
echo "</ul>\n";

echo "<p><strong>Benefits:</strong></p>\n";
echo "<ul>\n";
echo "<li>🚫 <strong>Eliminates redundant tooltips</strong> that just repeat the same information</li>\n";
echo "<li>💡 <strong>Shows helpful context</strong> only when there's additional value</li>\n";
echo "<li>🎯 <strong>Better UX</strong> - users aren't distracted by unnecessary tooltips</li>\n";
echo "<li>📊 <strong>Transparency</strong> - still shows sorting/original data when relevant</li>\n";
echo "</ul>\n";
