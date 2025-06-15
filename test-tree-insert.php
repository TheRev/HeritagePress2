<?php
require_once '../../../wp-config.php';

global $wpdb;

echo "=== Testing Tree Insertion with Correct Structure ===\n\n";

$trees_table = $wpdb->prefix . 'hp_trees';

// Try to insert with the correct structure
$tree_data = array(
  'gedcom' => 'test_tree',
  'treename' => 'Test Family Tree',
  'description' => 'Sample tree for testing HeritagePress People section',
  'owner' => 'test_admin',
  'email' => 'test@example.com',
  'address' => '',
  'city' => '',
  'state' => '',
  'country' => '',
  'zip' => '',
  'phone' => '',
  'secret' => 0,
  'disallowgedcreate' => 0,
  'disallowpdf' => 0,
  'lastimportdate' => date('Y-m-d H:i:s'),
  'importfilename' => 'test_data',
  'date_created' => date('Y-m-d H:i:s')
);

// Check if it already exists
$existing = $wpdb->get_var($wpdb->prepare("SELECT gedcom FROM $trees_table WHERE gedcom = %s", 'test_tree'));
echo "Existing tree check: " . ($existing ? "EXISTS" : "NOT FOUND") . "\n";

if ($existing) {
  echo "Deleting existing tree first...\n";
  $wpdb->delete($trees_table, array('gedcom' => 'test_tree'));
}

$result = $wpdb->insert($trees_table, $tree_data);
echo "Insert result: " . ($result !== false ? "SUCCESS" : "FAILED") . "\n";

if ($wpdb->last_error) {
  echo "Database error: " . $wpdb->last_error . "\n";
}

// Check if it was created
echo "\nChecking trees after insertion:\n";
$trees = $wpdb->get_results("SELECT gedcom, treename FROM $trees_table");
if ($trees) {
  foreach ($trees as $tree) {
    echo "- {$tree->gedcom}: {$tree->treename}\n";
  }
} else {
  echo "No trees found\n";
}
