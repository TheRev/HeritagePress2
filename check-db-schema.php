<?php

/**
 * Check current database schema for HeritagePress tables
 */

// WordPress database connection
define('DB_NAME', 'wordpress');
define('DB_USER', 'root');
define('DB_PASSWORD', 'root');
define('DB_HOST', 'localhost');

try {
  $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  echo "=== HERITAGEPRESS TABLES ===\n\n";

  // Check what HeritagePress tables exist
  $stmt = $pdo->query("SHOW TABLES LIKE 'wp_hp_%'");
  $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

  if (empty($tables)) {
    echo "No HeritagePress tables found.\n";
  } else {
    echo "Found tables:\n";
    foreach ($tables as $table) {
      echo "- $table\n";
    }

    echo "\n=== TABLE STRUCTURES ===\n\n";

    // Show structure of each table
    foreach ($tables as $table) {
      echo "TABLE: $table\n";
      echo str_repeat("-", 50) . "\n";

      $stmt = $pdo->query("DESCRIBE $table");
      $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

      foreach ($columns as $column) {
        echo sprintf(
          "%-20s %-20s %-10s %-10s %-10s %s\n",
          $column['Field'],
          $column['Type'],
          $column['Null'],
          $column['Key'],
          $column['Default'],
          $column['Extra']
        );
      }
      echo "\n";
    }
  }
} catch (PDOException $e) {
  echo "Database connection failed: " . $e->getMessage() . "\n";
}
