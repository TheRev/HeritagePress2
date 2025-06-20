<?php
// Quick test to check what's in the trees table
require_once dirname(__FILE__) . '/../../../wp-config.php';

global $wpdb;
$trees_table = $wpdb->prefix . 'hp_trees';

echo "Checking table: $trees_table\n";

// Get all trees
$trees = $wpdb->get_results("SELECT * FROM $trees_table ORDER BY gedcom", ARRAY_A);

echo "Found " . count($trees) . " trees:\n";
foreach ($trees as $tree) {
  echo "ID: " . $tree['gedcom'] . ", Name: " . $tree['treename'] . ", Owner: " . $tree['owner'] . "\n";
  echo "Description: " . $tree['description'] . "\n";
  echo "Last import: " . $tree['lastimportdate'] . "\n";
  echo "---\n";
}

// Check if there were any recent inserts
$recent = $wpdb->get_results("SELECT * FROM $trees_table WHERE lastimportdate >= '1970-01-01 00:00:00' ORDER BY gedcom", ARRAY_A);
echo "\nRecent trees (since 1970-01-01):\n";
foreach ($recent as $tree) {
  echo "ID: " . $tree['gedcom'] . ", Name: " . $tree['treename'] . "\n";
}
