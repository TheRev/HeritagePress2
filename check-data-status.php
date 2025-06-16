<?php

/**
 * Check current people and trees data
 */

// WordPress environment
define('ABSPATH', 'C:/MAMP/htdocs/HeritagePress2/');
require_once ABSPATH . 'wp-config.php';

global $wpdb;

echo "=== Database Content Check ===\n";

// Check people table
$people_table = $wpdb->prefix . 'hp_people';
$people_count = $wpdb->get_var("SELECT COUNT(*) FROM $people_table");
echo "People in database: $people_count\n";

if ($people_count > 0) {
  $sample_people = $wpdb->get_results("SELECT personID, firstname, lastname, gedcom FROM $people_table LIMIT 5");
  echo "\nSample people:\n";
  foreach ($sample_people as $person) {
    echo "- {$person->personID}: {$person->firstname} {$person->lastname} (Tree: " . ($person->gedcom ?: 'None') . ")\n";
  }
}

// Check trees table
$trees_table = $wpdb->prefix . 'hp_trees';
$trees_count = $wpdb->get_var("SELECT COUNT(*) FROM $trees_table");
echo "\nTrees in database: $trees_count\n";

if ($trees_count > 0) {
  $sample_trees = $wpdb->get_results("SELECT treeID, treename FROM $trees_table LIMIT 5");
  echo "\nSample trees:\n";
  foreach ($sample_trees as $tree) {
    echo "- {$tree->treeID}: {$tree->treename}\n";
  }
}

// Check if tables exist
$tables_check = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
echo "\nHeritagePress tables:\n";
foreach ($tables_check as $table) {
  $table_name = array_values((array)$table)[0];
  echo "- $table_name\n";
}
