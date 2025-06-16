<?php
// Check the actual structure and data
global $wpdb;
require_once 'c:/MAMP/htdocs/HeritagePress2/wp-config.php';

echo "=== HP_PEOPLE TABLE STRUCTURE ===\n";
$columns = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}hp_people");
foreach ($columns as $column) {
  echo $column->Field . ' - ' . $column->Type . "\n";
}

echo "\n=== SAMPLE PEOPLE DATA ===\n";
$people = $wpdb->get_results("SELECT personID, firstname, lastname, gedcom FROM {$wpdb->prefix}hp_people LIMIT 5");
foreach ($people as $person) {
  echo "ID: {$person->personID}, Name: {$person->firstname} {$person->lastname}, Tree: {$person->gedcom}\n";
}

echo "\n=== HP_TREES TABLE STRUCTURE ===\n";
$columns = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}hp_trees");
foreach ($columns as $column) {
  echo $column->Field . ' - ' . $column->Type . "\n";
}

echo "\n=== EXISTING TREES ===\n";
$trees = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}hp_trees");
echo "Trees in database:\n";
foreach ($trees as $tree) {
  echo "gedcom: {$tree->gedcom}, name: {$tree->treename}\n";
}
