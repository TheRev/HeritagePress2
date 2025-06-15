<?php

/**
 * Test Date Formatting Standardization
 *
 * Test script to verify the new standardized date formatting
 */

// WordPress environment setup
require_once '../../../wp-config.php';
require_once 'includes/class-hp-date-utils.php';

echo "<h1>Date Formatting Standardization Test</h1>\n";

// Test various date formats
$test_dates = array(
  '1/16/1964' => 'American format MM/DD/YYYY',
  '16/1/1964' => 'European format DD/MM/YYYY',
  '1964-01-16' => 'ISO format YYYY-MM-DD',
  '16 Jan 1964' => 'Day Month Year',
  '16 January 1964' => 'Day Full Month Year',
  'Jan 16, 1964' => 'Month Day, Year',
  'January 16, 1964' => 'Full Month Day, Year',
  'Abt 1964' => 'Approximate year only',
  'About May 1964' => 'Approximate month year',
  'C. 16 Jan 1964' => 'Circa with full date',
  'Before 1964' => 'Before with year',
  'Between 1963 and 1965' => 'Date range',
  '10 May 1990' => 'Standard genealogy format (should remain)',
  '1990' => 'Year only',
  'May 1990' => 'Month and year',
  '' => 'Empty date',
  '0000-00-00' => 'Invalid sortable date'
);

echo "<table border='1' cellpadding='5'>\n";
echo "<tr><th>Original</th><th>Format Description</th><th>Standardized</th></tr>\n";

foreach ($test_dates as $original => $description) {
  $standardized = HP_Date_Utils::standardize_date_format($original);
  echo "<tr>";
  echo "<td>" . htmlspecialchars($original) . "</td>";
  echo "<td>" . htmlspecialchars($description) . "</td>";
  echo "<td><strong>" . htmlspecialchars($standardized) . "</strong></td>";
  echo "</tr>\n";
}

echo "</table>\n";

// Test with some real database records
global $wpdb;
$people_table = $wpdb->prefix . 'hp_people';

echo "<h2>Real Database Records Test</h2>\n";

$query = "SELECT personID, firstname, lastname, birthdate, birthdatetr, altbirthdate, altbirthdatetr,
                 deathdate, deathdatetr, burialdate, burialdatetr
          FROM $people_table
          WHERE (birthdate IS NOT NULL AND birthdate != '')
             OR (deathdate IS NOT NULL AND deathdate != '')
          LIMIT 10";

$people = $wpdb->get_results($query, ARRAY_A);

if ($people) {
  echo "<table border='1' cellpadding='5'>\n";
  echo "<tr><th>Person</th><th>Birth (Original)</th><th>Birth (Standardized)</th><th>Death (Original)</th><th>Death (Standardized)</th></tr>\n";

  foreach ($people as $person) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($person['firstname'] . ' ' . $person['lastname']) . "</td>";

    // Birth date
    $birth_original = HP_Date_Utils::format_display_date($person, 'birth');
    $birth_std = '';
    if (!empty($person['birthdate'])) {
      $birth_std = HP_Date_Utils::standardize_date_format($person['birthdate'], $person['birthdatetr']);
    } elseif (!empty($person['altbirthdate'])) {
      $birth_std = HP_Date_Utils::standardize_date_format($person['altbirthdate'], $person['altbirthdatetr']) . ' (chr.)';
    }

    echo "<td>" . $birth_original . "</td>";
    echo "<td><strong>" . $birth_std . "</strong></td>";

    // Death date
    $death_original = HP_Date_Utils::format_display_date($person, 'death');
    $death_std = '';
    if (!empty($person['deathdate'])) {
      $death_std = HP_Date_Utils::standardize_date_format($person['deathdate'], $person['deathdatetr']);
    } elseif (!empty($person['burialdate'])) {
      $death_std = HP_Date_Utils::standardize_date_format($person['burialdate'], $person['burialdatetr']) . ' (bur.)';
    }

    echo "<td>" . $death_original . "</td>";
    echo "<td><strong>" . $death_std . "</strong></td>";
    echo "</tr>\n";
  }

  echo "</table>\n";
} else {
  echo "<p>No records found with dates.</p>\n";
}

echo "<h3>Test Complete</h3>\n";
echo "<p>The standardized format should consistently show dates as 'Day Month Year' (e.g., '16 Jan 1964', '10 May 1990')</p>\n";
