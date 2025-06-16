<?php

/**
 * Check trees table structure
 */

// WordPress environment
define('ABSPATH', 'C:/MAMP/htdocs/HeritagePress2/');
require_once ABSPATH . 'wp-config.php';

global $wpdb;

echo "=== Trees Table Structure ===\n";

$trees_table = $wpdb->prefix . 'hp_trees';

// Get table structure
$columns = $wpdb->get_results("DESCRIBE $trees_table");

echo "Columns in $trees_table:\n";
foreach ($columns as $column) {
  echo "- {$column->Field} ({$column->Type})\n";
}

// Also check what columns the people table is using for tree reference
echo "\n=== People Table Tree References ===\n";
$people_table = $wpdb->prefix . 'hp_people';
$tree_refs = $wpdb->get_results("SELECT DISTINCT gedcom FROM $people_table WHERE gedcom IS NOT NULL AND gedcom != ''");

echo "Tree references in people table:\n";
foreach ($tree_refs as $ref) {
  echo "- {$ref->gedcom}\n";
}
