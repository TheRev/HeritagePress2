<?php
// Test the simplified query from admin_trees.php
require_once dirname(__FILE__) . '/../../../wp-config.php';

global $wpdb;
$trees_table = $wpdb->prefix . 'hp_trees';

echo "Testing simplified query...\n";

$per_page = 20;
$offset = 0;
$searchstring = '';

$where = '';
if ($searchstring) {
  $like = '%' . $wpdb->esc_like($searchstring) . '%';
  $where = $wpdb->prepare("WHERE gedcom LIKE %s OR treename LIKE %s OR description LIKE %s OR owner LIKE %s", $like, $like, $like, $like);
}

// Get total count first
$count_sql = "SELECT COUNT(*) FROM $trees_table $where";
$total = $wpdb->get_var($count_sql);

echo "Count SQL: $count_sql\n";
echo "Total count: $total\n";

// Get the trees for this page
$sql = "SELECT gedcom, treename, description, owner, lastimportdate, importfilename FROM $trees_table $where ORDER BY treename LIMIT $per_page OFFSET $offset";
$trees = $wpdb->get_results($sql);

echo "Trees SQL: $sql\n";
echo "Trees found: " . count($trees) . "\n";

if ($trees) {
  foreach ($trees as $tree) {
    echo "ID: {$tree->gedcom}, Name: {$tree->treename}, Owner: {$tree->owner}\n";
    echo "Description: {$tree->description}\n";
    echo "Last import: {$tree->lastimportdate}\n";
    echo "---\n";
  }
}
