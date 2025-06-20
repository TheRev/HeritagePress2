<?php
// Test the exact query from admin_trees.php
require_once dirname(__FILE__) . '/../../../wp-config.php';

global $wpdb;
$trees_table = $wpdb->prefix . 'hp_trees';

echo "Testing exact query from admin_trees.php...\n";

$per_page = 20;
$offset = 0;

// This is the exact query from admin_trees.php line 133
$sql = "SELECT SQL_CALC_FOUND_ROWS gedcom, treename, description, owner, DATE_FORMAT(lastimportdate,'%d %b %Y %H:%i:%s') as lastimportdate, importfilename FROM $trees_table ORDER BY treename LIMIT %d OFFSET %d";

echo "SQL: $sql\n";
echo "Parameters: per_page=$per_page, offset=$offset\n";

try {
  $trees = $wpdb->get_results($wpdb->prepare($sql, $per_page, $offset));
  echo "Prepared query: " . $wpdb->last_query . "\n";

  if ($wpdb->last_error) {
    echo "DB Error: " . $wpdb->last_error . "\n";
  }

  echo "Trees found: " . count($trees) . "\n";

  if ($trees) {
    foreach ($trees as $tree) {
      echo "ID: {$tree->gedcom}, Name: {$tree->treename}\n";
    }
  }

  // Get total count
  $total = $wpdb->get_var('SELECT FOUND_ROWS()');
  echo "Total count from FOUND_ROWS(): $total\n";
} catch (Exception $e) {
  echo "Exception: " . $e->getMessage() . "\n";
}
