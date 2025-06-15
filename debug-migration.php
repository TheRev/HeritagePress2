<?php

/**
 * Debug Migration Script - Enhanced Error Reporting
 */

// Maximum error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Start output buffering to catch any issues
ob_start();

echo "<!DOCTYPE html><html><head><title>Debug Migration</title>";
echo "<style>body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }</style>";
echo "</head><body>";

echo "<h2>Debug Migration Script</h2>";

try {
  echo "<p>✅ PHP script started</p>";

  // Test basic PHP functionality
  echo "<p>✅ PHP version: " . phpversion() . "</p>";

  // Check if date parser file exists
  $parser_file = __DIR__ . '/includes/class-hp-date-parser.php';
  if (file_exists($parser_file)) {
    echo "<p>✅ Date parser file found: {$parser_file}</p>";
  } else {
    echo "<p>❌ Date parser file NOT found: {$parser_file}</p>";
    throw new Exception("Date parser file missing");
  }

  // Try to include the date parser
  echo "<p>🔄 Including date parser...</p>";
  require_once $parser_file;
  echo "<p>✅ Date parser included successfully</p>";

  // Test database connection
  echo "<p>🔄 Testing database connection...</p>";

  $config = [
    'host' => 'localhost',
    'database' => 'wordpress',
    'username' => 'root',
    'password' => 'root',
    'prefix' => 'wp_'
  ];

  $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
  $pdo = new PDO($dsn, $config['username'], $config['password']);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  echo "<p>✅ Database connected successfully</p>";

  // Test table existence
  $table_name = $config['prefix'] . 'hp_people';
  $stmt = $pdo->query("SHOW TABLES LIKE '{$table_name}'");
  if ($stmt->fetch()) {
    echo "<p>✅ Table {$table_name} exists</p>";
  } else {
    throw new Exception("Table {$table_name} does not exist");
  }

  // Test date parser
  echo "<p>🔄 Testing date parser...</p>";
  $test_date = '1/16/1964';
  $parsed = HP_Date_Parser::parse_date($test_date);

  if ($parsed) {
    echo "<p>✅ Date parser working: '{$test_date}' → '{$parsed['sortable']}'</p>";
  } else {
    echo "<p>❌ Date parser returned null for: {$test_date}</p>";
  }

  // Test query
  echo "<p>🔄 Testing migration query...</p>";

  $date_fields = [
    'birthdate' => 'birthdatetr',
    'deathdate' => 'deathdatetr',
    'altbirthdate' => 'altbirthdatetr',
    'burialdate' => 'burialdatetr',
    'baptdate' => 'baptdatetr'
  ];

  $where_conditions = [];
  foreach ($date_fields as $display => $sortable) {
    $where_conditions[] = "({$display} IS NOT NULL AND {$display} != '' AND ({$sortable} IS NULL OR {$sortable} = '' OR {$sortable} = '0000-00-00'))";
  }

  $query = "SELECT personID, " . implode(', ', array_keys($date_fields)) . ", " . implode(', ', array_values($date_fields)) .
    " FROM {$table_name} WHERE " . implode(' OR ', $where_conditions) . " LIMIT 5";

  echo "<p><strong>Query:</strong> <code>" . htmlspecialchars($query) . "</code></p>";

  $stmt = $pdo->query($query);
  $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo "<p>✅ Query executed successfully. Found " . count($records) . " records</p>";

  if (!empty($records)) {
    echo "<h3>Sample Records Found:</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Birth Date</th><th>Birth TR</th></tr>";

    foreach (array_slice($records, 0, 3) as $record) {
      echo "<tr>";
      echo "<td>{$record['personID']}</td>";
      echo "<td>{$record['birthdate']}</td>";
      echo "<td>{$record['birthdatetr']}</td>";
      echo "</tr>";
    }
    echo "</table>";

    echo "<p><strong>🎉 Everything looks good! The migration should work.</strong></p>";
    echo "<p><a href='quick-migration.php' style='background: #0073aa; color: white; padding: 8px 15px; text-decoration: none; border-radius: 3px;'>Try Migration Script Again</a></p>";
  } else {
    echo "<p>⚠️ No records found that need migration</p>";
  }
} catch (PDOException $e) {
  echo "<p>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
  echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Throwable $e) {
  echo "<p>❌ Fatal Error: " . htmlspecialchars($e->getMessage()) . "</p>";
  echo "<p>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
}

echo "</body></html>";

// Flush output
ob_end_flush();
