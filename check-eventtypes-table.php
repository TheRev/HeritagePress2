<?php
require_once('c:/MAMP/htdocs/HeritagePress2/wp-config.php');

global $wpdb;

echo "=== HP_EVENTTYPES TABLE STRUCTURE ===\n";

$table_name = $wpdb->prefix . 'hp_eventtypes';
$result = $wpdb->get_results("DESCRIBE $table_name");

foreach ($result as $column) {
  echo "{$column->Field}: {$column->Type} - {$column->Null} - {$column->Key} - {$column->Default}\n";
}

echo "\n=== CURRENT DATA ===\n";
$data = $wpdb->get_results("SELECT * FROM $table_name");
foreach ($data as $row) {
  print_r($row);
}
