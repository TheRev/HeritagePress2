<?php

/**
 * Quick Database Test for HeritagePress Date Migration
 */

// Database configuration
$db_config = [
  'host' => 'localhost',
  'database' => 'wordpress',
  'username' => 'root',
  'password' => 'root',
  'prefix' => 'wp_'
];

echo "<!DOCTYPE html><html><head><title>Database Test</title></head><body>";
echo "<h2>HeritagePress Database Connection Test</h2>";

try {
  // Test database connection
  $dsn = "mysql:host={$db_config['host']};dbname={$db_config['database']};charset=utf8mb4";
  $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  echo "<p>✅ <strong>Database connection successful!</strong></p>";

  // Check if people table exists
  $table_name = $db_config['prefix'] . 'hp_people';
  $stmt = $pdo->query("SHOW TABLES LIKE '{$table_name}'");
  $table_exists = $stmt->fetchColumn();

  if ($table_exists) {
    echo "<p>✅ <strong>People table found:</strong> {$table_name}</p>";

    // Count records
    $stmt = $pdo->query("SELECT COUNT(*) FROM `{$table_name}`");
    $count = $stmt->fetchColumn();
    echo "<p>📊 <strong>Total people records:</strong> {$count}</p>";

    if ($count > 0) {
      // Show sample dates
      $stmt = $pdo->query("SELECT personID, firstname, lastname, birthdate, birthdatetr, deathdate, deathdatetr FROM `{$table_name}` LIMIT 5");
      $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);

      echo "<h3>Sample Date Data</h3>";
      echo "<table border='1' cellpadding='5' cellspacing='0'>";
      echo "<tr><th>Person ID</th><th>Name</th><th>Birth Date</th><th>Birth Date (Sortable)</th><th>Death Date</th><th>Death Date (Sortable)</th></tr>";

      foreach ($samples as $person) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($person['personID']) . "</td>";
        echo "<td>" . htmlspecialchars($person['firstname'] . ' ' . $person['lastname']) . "</td>";
        echo "<td>" . htmlspecialchars($person['birthdate']) . "</td>";
        echo "<td>" . htmlspecialchars($person['birthdatetr']) . "</td>";
        echo "<td>" . htmlspecialchars($person['deathdate']) . "</td>";
        echo "<td>" . htmlspecialchars($person['deathdatetr']) . "</td>";
        echo "</tr>";
      }
      echo "</table>";

      // Check which dates need migration
      $stmt = $pdo->query("SELECT COUNT(*) FROM `{$table_name}` WHERE birthdate != '' AND (birthdatetr = '0000-00-00' OR birthdatetr = '' OR birthdatetr IS NULL)");
      $birth_needs_migration = $stmt->fetchColumn();

      $stmt = $pdo->query("SELECT COUNT(*) FROM `{$table_name}` WHERE deathdate != '' AND (deathdatetr = '0000-00-00' OR deathdatetr = '' OR deathdatetr IS NULL)");
      $death_needs_migration = $stmt->fetchColumn();

      echo "<h3>Migration Status</h3>";
      echo "<p>🔄 <strong>Birth dates needing migration:</strong> {$birth_needs_migration}</p>";
      echo "<p>🔄 <strong>Death dates needing migration:</strong> {$death_needs_migration}</p>";

      if ($birth_needs_migration > 0 || $death_needs_migration > 0) {
        echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
        echo "<strong>Migration Needed!</strong> Some dates need to be converted to sortable format.";
        echo "</div>";

        echo "<p><a href='run-date-migration.php' style='background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Run Date Migration</a></p>";
      } else {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
        echo "<strong>✅ All dates are already migrated!</strong> No migration needed.";
        echo "</div>";
      }
    } else {
      echo "<p>ℹ️ No people records found in the database.</p>";
    }
  } else {
    echo "<p>❌ <strong>People table not found:</strong> {$table_name}</p>";
    echo "<p>Make sure your HeritagePress plugin is activated and tables are created.</p>";
  }
} catch (Exception $e) {
  echo "<p>❌ <strong>Database error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
