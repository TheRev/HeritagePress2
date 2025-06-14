<?php
// Check existing HeritagePress tables before reactivation
require_once '../../../wp-config.php';

global $wpdb;

echo "Checking existing HeritagePress tables...\n";

$tables = $wpdb->get_results("SHOW TABLES LIKE 'wp_hp_%'");

echo "HeritagePress tables found: " . count($tables) . "\n";

if (count($tables) > 0) {
  echo "\nExisting tables:\n";
  foreach ($tables as $table) {
    $table_name = array_values((array)$table)[0];
    echo "- $table_name\n";
  }
} else {
  echo "No HeritagePress tables found.\n";
}

echo "\nReady for plugin reactivation test.\n";
