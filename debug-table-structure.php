<?php

/**
 * Debug script to check wp_hp_trees table structure
 */

// WordPress database connection
$wpdb_config = array(
  'host' => 'localhost',
  'username' => 'root',
  'password' => 'root',
  'database' => 'wordpress'
);

try {
  $pdo = new PDO(
    "mysql:host={$wpdb_config['host']};dbname={$wpdb_config['database']}",
    $wpdb_config['username'],
    $wpdb_config['password']
  );

  echo "Database connection successful!\n\n";

  // Check if table exists
  $stmt = $pdo->query("SHOW TABLES LIKE 'wp_hp_trees'");
  if ($stmt->rowCount() > 0) {
    echo "Table wp_hp_trees exists.\n\n";

    // Get table structure
    echo "Table structure:\n";
    echo "================\n";
    $stmt = $pdo->query("DESCRIBE wp_hp_trees");
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

    echo "\n\nSample data (first 5 rows):\n";
    echo "===========================\n";
    $stmt = $pdo->query("SELECT * FROM wp_hp_trees LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($rows) > 0) {
      // Print column headers
      $columns = array_keys($rows[0]);
      echo implode("\t", $columns) . "\n";
      echo str_repeat("-", 80) . "\n";

      // Print data
      foreach ($rows as $row) {
        echo implode("\t", $row) . "\n";
      }
    } else {
      echo "No data found in table.\n";
    }
  } else {
    echo "Table wp_hp_trees does not exist!\n";

    // Show all tables that start with wp_hp
    echo "\nTables starting with wp_hp:\n";
    echo "===========================\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'wp_hp%'");
    $tables = $stmt->fetchAll(PDO::FETCH_NUM);

    foreach ($tables as $table) {
      echo $table[0] . "\n";
    }
  }
} catch (PDOException $e) {
  echo "Database connection failed: " . $e->getMessage() . "\n";
}
