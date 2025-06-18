<?php
define('ABSPATH', 'c:/MAMP/htdocs/HeritagePress2/');
require_once 'c:/MAMP/htdocs/HeritagePress2/wp-config.php';
require_once 'includes/database/class-hp-database-manager.php';

global $wpdb;

echo "=== Creating Missing Database Tables ===\n";

$db_manager = new HP_Database_Manager();

// First check which tables exist
echo "Checking existing tables...\n";
$tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'", ARRAY_N);
$existing_tables = array();
foreach ($tables as $table) {
  $table_name = str_replace($wpdb->prefix . 'hp_', '', $table[0]);
  $existing_tables[] = $table_name;
}

echo "Existing HP tables: " . implode(', ', $existing_tables) . "\n\n";

// Check if sources, media, citations tables exist
$needed_tables = ['sources', 'media', 'repositories', 'citations', 'xnotes', 'medialinks'];
$missing_tables = array();

foreach ($needed_tables as $needed_table) {
  if (!in_array($needed_table, $existing_tables)) {
    $missing_tables[] = $needed_table;
  }
}

if (empty($missing_tables)) {
  echo "All needed tables exist!\n";
} else {
  echo "Missing tables: " . implode(', ', $missing_tables) . "\n";
  echo "Creating missing tables...\n";

  // Create tables
  $result = $db_manager->create_tables();
  if ($result) {
    echo "Tables created successfully!\n";
  } else {
    echo "Error creating tables\n";
  }
}

// Check again after creation
echo "\nFinal check...\n";
$tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'", ARRAY_N);
$final_tables = array();
foreach ($tables as $table) {
  $table_name = str_replace($wpdb->prefix . 'hp_', '', $table[0]);
  $final_tables[] = $table_name;
}

echo "Final HP tables: " . implode(', ', $final_tables) . "\n";

// Check specifically for the needed tables
echo "\nChecking needed tables:\n";
foreach ($needed_tables as $needed_table) {
  $exists = in_array($needed_table, $final_tables);
  echo "- $needed_table: " . ($exists ? "EXISTS" : "MISSING") . "\n";
}
