<?php

/**
 * Test Date Parser with Actual Data
 */

require_once __DIR__ . '/includes/class-hp-date-parser.php';

echo "<!DOCTYPE html><html><head><title>Date Parser Test</title>";
echo "<style>body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }</style>";
echo "</head><body>";

echo "<h2>Date Parser Test with Your Actual Data</h2>";

// Test the specific dates from your database
$test_dates = [
  '1/16/1964',
  '2 OCT 1822',
  'BEF 1828',
  '11 JUN 1861',
  '15 JUL 1850',
  '14 APR 1905',
  '3 MAR 1920'
];

echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'><th>Original Date</th><th>Parsed Result</th><th>Sortable Date</th><th>Status</th></tr>";

foreach ($test_dates as $date) {
  echo "<tr>";
  echo "<td><strong>{$date}</strong></td>";

  try {
    $parsed = HP_Date_Parser::parse_date($date);

    if ($parsed) {
      echo "<td><pre>" . print_r($parsed, true) . "</pre></td>";
      echo "<td style='background: #eeffee; font-weight: bold;'>{$parsed['sortable']}</td>";
      echo "<td style='color: #46b450;'>✅ SUCCESS</td>";
    } else {
      echo "<td>NULL</td>";
      echo "<td style='background: #ffeeee;'>Failed to parse</td>";
      echo "<td style='color: #d63638;'>❌ FAILED</td>";
    }
  } catch (Exception $e) {
    echo "<td>Exception: " . htmlspecialchars($e->getMessage()) . "</td>";
    echo "<td style='background: #ffeeee;'>ERROR</td>";
    echo "<td style='color: #d63638;'>❌ ERROR</td>";
  }

  echo "</tr>";
}

echo "</table>";

echo "<h3>Migration Preview</h3>";
echo "<p>If the date parser is working correctly, here's what should happen in the migration:</p>";

echo "<ul>";
foreach ($test_dates as $date) {
  try {
    $parsed = HP_Date_Parser::parse_date($date);
    if ($parsed && !empty($parsed['sortable'])) {
      echo "<li><code>{$date}</code> → <strong>{$parsed['sortable']}</strong></li>";
    } else {
      echo "<li><code>{$date}</code> → <span style='color: red;'>FAILED TO CONVERT</span></li>";
    }
  } catch (Exception $e) {
    echo "<li><code>{$date}</code> → <span style='color: red;'>ERROR: " . htmlspecialchars($e->getMessage()) . "</span></li>";
  }
}
echo "</ul>";

echo "</body></html>";
