<?php

/**
 * Create sample trees for testing
 */

// WordPress environment
define('ABSPATH', 'C:/MAMP/htdocs/HeritagePress2/');
require_once ABSPATH . 'wp-config.php';

global $wpdb;

echo "=== Creating Sample Trees ===\n";

$trees_table = $wpdb->prefix . 'hp_trees';

// Create some sample trees
$sample_trees = array(
  array(
    'gedcom' => 'test_tree',
    'treename' => 'Test Family Tree',
    'owner' => 'admin',
    'date_created' => current_time('mysql')
  ),
  array(
    'gedcom' => '2000',
    'treename' => 'Cox Family Tree',
    'owner' => 'admin',
    'date_created' => current_time('mysql')
  ),
  array(
    'gedcom' => 'smith_tree',
    'treename' => 'Smith Family Tree',
    'owner' => 'admin',
    'date_created' => current_time('mysql')
  ),
);

foreach ($sample_trees as $tree) {
  $result = $wpdb->insert(
    $trees_table,
    $tree,
    array('%s', '%s', '%s', '%s')
  );

  if ($result) {
    echo "✓ Created tree: {$tree['treename']} (ID: {$tree['treeID']})\n";
  } else {
    echo "✗ Failed to create tree: {$tree['treename']} - " . $wpdb->last_error . "\n";
  }
}

echo "\nTrees now in database:\n";
$trees = $wpdb->get_results("SELECT treeID, treename FROM $trees_table");
foreach ($trees as $tree) {
  echo "- {$tree->treeID}: {$tree->treename}\n";
}
