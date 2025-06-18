<?php
require_once('c:/MAMP/htdocs/HeritagePress2/wp-config.php');

global $wpdb;

echo "Checking families table structure...\n";

$table = $wpdb->prefix . 'hp_families';
$structure = $wpdb->get_results("DESCRIBE $table");

echo "Families table structure:\n";
foreach ($structure as $column) {
  echo "- {$column->Field}: {$column->Type}\n";
}

echo "\nCurrent families in database:\n";
$families = $wpdb->get_results("SELECT * FROM $table WHERE gedcom = 'main'");
echo "Count: " . count($families) . "\n";

if (!empty($families)) {
  foreach ($families as $family) {
    print_r($family);
  }
}
