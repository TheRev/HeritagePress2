<?php
// Direct database connection test
$host = 'localhost';
$username = 'root';
$password = 'root';
$database = 'wordpress';

try {
  $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  echo "Database connection successful!\n\n";

  // Check if hp_trees table exists
  $stmt = $pdo->query("SHOW TABLES LIKE 'wp_hp_trees'");
  if ($stmt->rowCount() > 0) {
    echo "Table wp_hp_trees exists\n\n";

    // Get table structure
    $stmt = $pdo->query("DESCRIBE wp_hp_trees");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Table structure:\n";
    foreach ($columns as $column) {
      $null = $column['Null'] == 'NO' ? ' (NOT NULL)' : ' (NULLABLE)';
      $default = $column['Default'] ? " DEFAULT: {$column['Default']}" : '';
      echo "  - {$column['Field']} ({$column['Type']})$null$default\n";
    }

    echo "\n";

    // Check current data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM wp_hp_trees");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Current records in table: {$count['count']}\n\n";

    // Test insert with minimal data
    echo "Testing insert...\n";
    try {
      $stmt = $pdo->prepare("INSERT INTO wp_hp_trees (gedcom, treename, description, owner, email, address, city, state, country, zip, phone, secret, disallowgedcreate, disallowpdf, lastimportdate, importfilename) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $result = $stmt->execute([
        'TEST123',
        'Test Tree',
        'Test Description',
        'Test Owner',
        'test@example.com',
        'Test Address',
        'Test City',
        'Test State',
        'Test Country',
        '12345',
        '555-1234',
        0,
        0,
        0,
        '0000-00-00 00:00:00',
        ''
      ]);

      if ($result) {
        echo "Insert successful!\n";
        // Clean up test record
        $pdo->exec("DELETE FROM wp_hp_trees WHERE gedcom = 'TEST123'");
        echo "Test record cleaned up.\n";
      } else {
        echo "Insert failed!\n";
      }
    } catch (Exception $e) {
      echo "Insert error: " . $e->getMessage() . "\n";
    }
  } else {
    echo "Table wp_hp_trees does not exist!\n";

    // Show all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Available tables:\n";
    foreach ($tables as $table) {
      if (strpos($table, 'hp_') !== false) {
        echo "  - $table\n";
      }
    }
  }
} catch (Exception $e) {
  echo "Database error: " . $e->getMessage() . "\n";
}
