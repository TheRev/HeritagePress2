<?php
// Check what tables exist for sources, media, notes
define('ABSPATH', 'c:/MAMP/htdocs/HeritagePress2/');
require_once 'c:/MAMP/htdocs/HeritagePress2/wp-config.php';

global $wpdb;

echo "=== Checking database tables for sources, media, notes ===\n";

// Check for source-related tables
$tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}heritagepress_%'", ARRAY_N);
foreach ($tables as $table) {
  $table_name = $table[0];
  if (
    strpos($table_name, 'source') !== false ||
    strpos($table_name, 'media') !== false ||
    strpos($table_name, 'note') !== false ||
    strpos($table_name, 'citation') !== false ||
    strpos($table_name, 'repo') !== false
  ) {
    echo "Found table: $table_name\n";

    // Get table structure
    $columns = $wpdb->get_results("DESCRIBE $table_name", ARRAY_A);
    foreach ($columns as $column) {
      echo "  - {$column['Field']} ({$column['Type']})\n";
    }
    echo "\n";
  }
}

// Also list all HeritagePress tables
echo "=== All HeritagePress Tables ===\n";
foreach ($tables as $table) {
  echo $table[0] . "\n";
}
