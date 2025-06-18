<?php

/**
 * Check Database for Imported Records
 */

// WordPress setup for command line
define('WP_USE_THEMES', false);
require_once('c:/MAMP/htdocs/HeritagePress2/wp-config.php');

// Load WordPress
global $wpdb;

echo "<h2>Database Records Check</h2>\n";

// Check individuals table
$individuals_table = $wpdb->prefix . 'hp_people';
$individuals_count = $wpdb->get_var("SELECT COUNT(*) FROM $individuals_table WHERE gedcom = 'main'");
echo "<h3>Individuals in main tree: $individuals_count</h3>\n";

if ($individuals_count > 0) {
  $individuals = $wpdb->get_results("SELECT * FROM $individuals_table WHERE gedcom = 'main' LIMIT 5");
  echo "<pre>" . print_r($individuals, true) . "</pre>\n";
}

// Check families table
$families_table = $wpdb->prefix . 'hp_families';
$families_count = $wpdb->get_var("SELECT COUNT(*) FROM $families_table WHERE gedcom = 'main'");
echo "<h3>Families in main tree: $families_count</h3>\n";

if ($families_count > 0) {
  $families = $wpdb->get_results("SELECT * FROM $families_table WHERE gedcom = 'main' LIMIT 5");
  echo "<pre>" . print_r($families, true) . "</pre>\n";
}

// Check if tables exist
echo "<h3>Table Existence Check:</h3>\n";
$tables_to_check = ['hp_people', 'hp_families', 'hp_sources', 'hp_trees'];

foreach ($tables_to_check as $table) {
  $full_table_name = $wpdb->prefix . $table;
  $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
  echo "Table $full_table_name: " . ($exists ? "✅ EXISTS" : "❌ MISSING") . "\n";
}

echo "\n<h3>Check Complete</h3>\n";
