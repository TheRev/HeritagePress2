<?php

/**
 * Database Diagnostic - Check Date Fields
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Date Fields Diagnostic</title>";
echo "<style>body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }</style>";
echo "</head><body>";

echo "<h2>Date Fields Diagnostic</h2>";

// Database configuration
$config = [
  'host' => 'localhost',
  'database' => 'wordpress',
  'username' => 'root',
  'password' => 'root',
  'prefix' => 'wp_'
];

try {
  $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
  $pdo = new PDO($dsn, $config['username'], $config['password']);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $table_name = $config['prefix'] . 'hp_people';

  echo "<h3>Table Structure Check</h3>";

  // Check table structure
  $stmt = $pdo->query("DESCRIBE {$table_name}");
  $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $date_columns = [];
  foreach ($columns as $col) {
    if (strpos($col['Field'], 'date') !== false) {
      $date_columns[] = $col['Field'];
    }
  }

  echo "<p><strong>Date-related columns found:</strong> " . implode(', ', $date_columns) . "</p>";

  echo "<h3>Data Sample</h3>";

  // Get all records to see what's actually there
  $stmt = $pdo->query("SELECT personID, firstname, lastname, birthdate, birthdatetr, deathdate, deathdatetr, altbirthdate, altbirthdatetr, burialdate, burialdatetr, baptdate, baptdatetr FROM {$table_name} LIMIT 10");
  $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

  if (!empty($records)) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Name</th><th>Birth Date</th><th>Birth Date TR</th><th>Death Date</th><th>Death Date TR</th>";
    echo "</tr>";

    foreach ($records as $record) {
      echo "<tr>";
      echo "<td>{$record['personID']}</td>";
      echo "<td>{$record['firstname']} {$record['lastname']}</td>";
      echo "<td style='background: " . (empty($record['birthdate']) ? '#ffeeee' : '#eeffee') . ";'>{$record['birthdate']}</td>";
      echo "<td style='background: " . (empty($record['birthdatetr']) || $record['birthdatetr'] == '0' ? '#ffeeee' : '#eeffee') . ";'>{$record['birthdatetr']}</td>";
      echo "<td style='background: " . (empty($record['deathdate']) ? '#ffeeee' : '#eeffee') . ";'>{$record['deathdate']}</td>";
      echo "<td style='background: " . (empty($record['deathdatetr']) || $record['deathdatetr'] == '0' ? '#ffeeee' : '#eeffee') . ";'>{$record['deathdatetr']}</td>";
      echo "</tr>";
    }
    echo "</table>";
  }

  echo "<h3>Count Analysis</h3>";

  $date_fields = [
    'birthdate' => 'birthdatetr',
    'deathdate' => 'deathdatetr',
    'altbirthdate' => 'altbirthdatetr',
    'burialdate' => 'burialdatetr',
    'baptdate' => 'baptdatetr'
  ];

  echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
  echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Has Display Date</th><th>Has Sortable Date</th><th>Needs Migration</th></tr>";

  foreach ($date_fields as $display => $sortable) {
    $has_display = $pdo->query("SELECT COUNT(*) FROM {$table_name} WHERE {$display} IS NOT NULL AND {$display} != ''")->fetchColumn();
    $has_sortable = $pdo->query("SELECT COUNT(*) FROM {$table_name} WHERE {$sortable} IS NOT NULL AND {$sortable} != '' AND {$sortable} != '0'")->fetchColumn();
    $needs_migration = $pdo->query("SELECT COUNT(*) FROM {$table_name} WHERE ({$display} IS NOT NULL AND {$display} != '') AND ({$sortable} IS NULL OR {$sortable} = '' OR {$sortable} = '0')")->fetchColumn();

    echo "<tr>";
    echo "<td>{$display} → {$sortable}</td>";
    echo "<td>{$has_display}</td>";
    echo "<td>{$has_sortable}</td>";
    echo "<td style='font-weight: bold; color: " . ($needs_migration > 0 ? '#d63638' : '#46b450') . ";'>{$needs_migration}</td>";
    echo "</tr>";
  }
  echo "</table>";

  echo "<h3>Records That Need Migration</h3>";

  // Find specific records that need migration
  $where_conditions = [];
  foreach ($date_fields as $display => $sortable) {
    $where_conditions[] = "({$display} IS NOT NULL AND {$display} != '' AND ({$sortable} IS NULL OR {$sortable} = '' OR {$sortable} = '0'))";
  }

  $query = "SELECT personID, firstname, lastname, " . implode(', ', array_keys($date_fields)) . ", " . implode(', ', array_values($date_fields)) .
    " FROM {$table_name} WHERE " . implode(' OR ', $where_conditions) . " LIMIT 5";

  echo "<p><strong>Query:</strong> <code>" . htmlspecialchars($query) . "</code></p>";

  $stmt = $pdo->query($query);
  $migration_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

  if (!empty($migration_records)) {
    echo "<p><strong>Found " . count($migration_records) . " records that need migration:</strong></p>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Name</th><th>Birth Date</th><th>Birth TR</th><th>Death Date</th><th>Death TR</th>";
    echo "</tr>";

    foreach ($migration_records as $record) {
      echo "<tr>";
      echo "<td>{$record['personID']}</td>";
      echo "<td>{$record['firstname']} {$record['lastname']}</td>";
      echo "<td>" . ($record['birthdate'] ?: '(empty)') . "</td>";
      echo "<td>" . ($record['birthdatetr'] ?: '(empty)') . "</td>";
      echo "<td>" . ($record['deathdate'] ?: '(empty)') . "</td>";
      echo "<td>" . ($record['deathdatetr'] ?: '(empty)') . "</td>";
      echo "</tr>";
    }
    echo "</table>";
  } else {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
    echo "<strong>✅ No records found that need migration!</strong>";
    echo "</div>";
  }
} catch (Exception $e) {
  echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
  echo "<strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage());
  echo "</div>";
}

echo "</body></html>";
