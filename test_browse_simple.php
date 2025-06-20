<?php
// Test simplified browse query
require_once dirname(__FILE__) . '/../../../wp-config.php';

global $wpdb;
$trees_table = $wpdb->prefix . 'hp_trees';
$people_table = $wpdb->prefix . 'hp_people';

echo "Testing simplified browse query...\n";

// Test basic query first
$sql = "SELECT gedcom, treename, description, owner, lastimportdate, importfilename FROM $trees_table ORDER BY treename";
$trees = $wpdb->get_results($sql);

echo "Query: " . $wpdb->last_query . "\n";
echo "Found " . count($trees) . " trees:\n";

if ($trees) {
  foreach ($trees as $tree) {
    echo "ID: {$tree->gedcom}, Name: {$tree->treename}, Owner: {$tree->owner}\n";
    echo "Description: {$tree->description}\n";
    echo "Last import: {$tree->lastimportdate}\n";
    echo "Import filename: {$tree->importfilename}\n";
    echo "---\n";
  }
} else {
  echo "No trees returned by query.\n";
}

// Test with prepare
echo "\nTesting with prepare()...\n";
$per_page = 20;
$offset = 0;
$sql2 = "SELECT gedcom, treename, description, owner, DATE_FORMAT(lastimportdate,'%d %b %Y %H:%i:%s') as lastimportdate, importfilename FROM $trees_table ORDER BY treename LIMIT %d OFFSET %d";
$trees2 = $wpdb->get_results($wpdb->prepare($sql2, $per_page, $offset));
echo "Query: " . $wpdb->last_query . "\n";
echo "Found " . count($trees2) . " trees.\n";
