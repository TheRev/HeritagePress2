<?php
require_once '../../../wp-config.php';

global $wpdb;

echo "=== HeritagePress Tree Creation Debug ===\n\n";

$trees_table = $wpdb->prefix . 'hp_trees';

// Show the current table structure
echo "Trees table structure:\n";
$structure = $wpdb->get_results("DESCRIBE $trees_table");
if ($structure) {
  foreach ($structure as $field) {
    echo "- {$field->Field}: {$field->Type} " . ($field->Null == 'YES' ? 'NULL' : 'NOT NULL') . " " . ($field->Key ? "KEY: {$field->Key}" : '') . "\n";
  }
} else {
  echo "Could not get table structure: " . $wpdb->last_error . "\n";
}

echo "\n";

// Try to manually insert a tree record
echo "Attempting to insert test tree:\n";

$tree_data = array(
  'gedcom' => 'test_tree',
  'treename' => 'Test Family Tree',
  'description' => 'Sample tree for testing HeritagePress People section',
  'owner' => 'test_admin',
  'email' => 'test@example.com',
  'rootpersonID' => 'I1',
  'living_prefix' => '',
  'allow_living' => 'yes',
  'people_count' => 5,
  'family_count' => 0,
  'changedate' => date('Y-m-d H:i:s'),
  'changedby' => 'test_import'
);

// Check if it already exists
$existing = $wpdb->get_var($wpdb->prepare("SELECT gedcom FROM $trees_table WHERE gedcom = %s", 'test_tree'));
echo "Existing tree check: " . ($existing ? "EXISTS" : "NOT FOUND") . "\n";

if ($existing) {
  $result = $wpdb->update($trees_table, $tree_data, array('gedcom' => 'test_tree'));
  echo "Update result: " . ($result !== false ? "SUCCESS (affected rows: $result)" : "FAILED") . "\n";
} else {
  $result = $wpdb->insert($trees_table, $tree_data);
  echo "Insert result: " . ($result !== false ? "SUCCESS" : "FAILED") . "\n";
}

if ($wpdb->last_error) {
  echo "Database error: " . $wpdb->last_error . "\n";
}

echo "\n";

// Check if it was created
echo "Checking if tree was created:\n";
$trees = $wpdb->get_results("SELECT * FROM $trees_table WHERE gedcom = 'test_tree'");
if ($trees) {
  foreach ($trees as $tree) {
    echo "Found tree: {$tree->gedcom} - {$tree->treename}\n";
    foreach ($tree as $key => $value) {
      echo "  $key: $value\n";
    }
  }
} else {
  echo "Tree not found after insertion attempt\n";
}
