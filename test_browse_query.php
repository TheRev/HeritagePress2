<?php
// Test the browse query directly
require_once dirname(__FILE__) . '/../../../wp-config.php';

global $wpdb;
$trees_table = $wpdb->prefix . 'hp_trees';
$people_table = $wpdb->prefix . 'hp_people';

echo "Testing browse query...\n";

$per_page = 20;
$offset = 0;

// This is the exact query from admin_trees.php
$sql = "SELECT SQL_CALC_FOUND_ROWS gedcom, treename, description, owner, DATE_FORMAT(lastimportdate,'%d %b %Y %H:%i:%s') as lastimportdate, importfilename FROM $trees_table ORDER BY treename LIMIT %d OFFSET %d";
$trees = $wpdb->get_results($wpdb->prepare($sql, $per_page, $offset));
$total = $wpdb->get_var('SELECT FOUND_ROWS()');

echo "Query: " . $wpdb->last_query . "\n";
echo "Found $total trees:\n";

if ($trees) {
  foreach ($trees as $tree) {
    $people_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(personID) FROM $people_table WHERE gedcom = %s", $tree->gedcom));
    echo "ID: {$tree->gedcom}, Name: {$tree->treename}, Owner: {$tree->owner}, People: $people_count\n";
    echo "Description: {$tree->description}\n";
    echo "Last import: {$tree->lastimportdate}\n";
    echo "Import filename: {$tree->importfilename}\n";
    echo "---\n";
  }
} else {
  echo "No trees returned by query.\n";
}
