<?php

/**
 * Simple Database Connection Test
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Database Connection Test</title></head><body>";
echo "<h2>Database Connection Test</h2>";

// Database configuration
$config = [
  'host' => 'localhost',
  'database' => 'wordpress',
  'username' => 'root',
  'password' => 'root',
  'prefix' => 'wp_'
];

try {
  echo "<p>Attempting to connect to database...</p>";

  $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
  $pdo = new PDO($dsn, $config['username'], $config['password']);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  echo "<p>✅ Database connection successful!</p>";

  // Check if hp_people table exists
  $table_name = $config['prefix'] . 'hp_people';
  $stmt = $pdo->query("SHOW TABLES LIKE '{$table_name}'");
  $table_exists = $stmt->fetch();

  if ($table_exists) {
    echo "<p>✅ Table {$table_name} exists</p>";

    // Get record count
    $stmt = $pdo->query("SELECT COUNT(*) FROM {$table_name}");
    $count = $stmt->fetchColumn();
    echo "<p>📊 Found {$count} records in the table</p>";

    // Check a few sample records
    $stmt = $pdo->query("SELECT personID, firstname, lastname, birthdate, birthdatetr FROM {$table_name} LIMIT 5");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($records) {
      echo "<h3>Sample Records:</h3>";
      echo "<table border='1' cellpadding='5'>";
      echo "<tr><th>ID</th><th>Name</th><th>Birth Date</th><th>Birth Date TR</th></tr>";
      foreach ($records as $record) {
        echo "<tr>";
        echo "<td>{$record['personID']}</td>";
        echo "<td>{$record['firstname']} {$record['lastname']}</td>";
        echo "<td>{$record['birthdate']}</td>";
        echo "<td>{$record['birthdatetr']}</td>";
        echo "</tr>";
      }
      echo "</table>";
    }
  } else {
    echo "<p>❌ Table {$table_name} does not exist</p>";
  }
} catch (PDOException $e) {
  echo "<p>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
  echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
