<?php
define('ABSPATH', 'c:/MAMP/htdocs/HeritagePress2/');
require_once 'c:/MAMP/htdocs/HeritagePress2/wp-config.php';

global $wpdb;

echo "=== Checking Table Indexes ===\n";

$tables = ['hp_sources', 'hp_media', 'hp_repositories'];

foreach ($tables as $table) {
  echo "\n$table indexes:\n";
  $result = $wpdb->get_results("SHOW INDEX FROM {$wpdb->prefix}$table");
  foreach ($result as $index) {
    echo "- {$index->Column_name} - {$index->Key_name} - Unique: " . ($index->Non_unique ? 'No' : 'Yes') . "\n";
  }
}
