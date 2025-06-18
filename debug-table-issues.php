<?php
define('ABSPATH', 'c:/MAMP/htdocs/HeritagePress2/');
require_once 'c:/MAMP/htdocs/HeritagePress2/wp-config.php';

global $wpdb;

echo "=== Debug Database Issues ===\n";

// First clear the test data
$tree_id = 'test';
$tables = ['hp_sources', 'hp_media', 'hp_repositories'];
foreach ($tables as $table) {
  $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}$table WHERE gedcom = '$tree_id'");
  echo "Before cleanup: $table has $count records\n";

  $wpdb->delete($wpdb->prefix . $table, array('gedcom' => $tree_id));

  $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}$table WHERE gedcom = '$tree_id'");
  echo "After cleanup: $table has $count records\n";
}

// Test a simple source insertion
echo "\n=== Testing Source Insertion ===\n";
$source_data = array(
  'gedcom' => $tree_id,
  'sourceID' => 'TEST123',
  'callnum' => '',
  'type' => '',
  'title' => 'Test Source',
  'author' => 'Test Author',
  'publisher' => 'Test Publisher',
  'other' => '',
  'shorttitle' => '',
  'comments' => '',
  'actualtext' => '',
  'repoID' => '',
  'changedate' => date('Y-m-d H:i:s'),
  'changedby' => 'Test'
);

$table_name = $wpdb->prefix . 'hp_sources';
echo "Inserting into table: $table_name\n";

$result = $wpdb->insert($table_name, $source_data);

if ($result === false) {
  echo "ERROR: " . $wpdb->last_error . "\n";
  echo "Query: " . $wpdb->last_query . "\n";
} else {
  echo "Success! Inserted with ID: " . $wpdb->insert_id . "\n";
}

// Check table structure
echo "\n=== Table Structure ===\n";
$columns = $wpdb->get_results("DESCRIBE $table_name");
foreach ($columns as $column) {
  echo "- {$column->Field} ({$column->Type}) {$column->Null} {$column->Key} {$column->Default}\n";
}
