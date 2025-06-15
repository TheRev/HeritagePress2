<?php
require_once '../../../wp-config.php';

global $wpdb;

echo "=== HeritagePress Test Data Check ===\n\n";

// Check trees
echo "Trees table records:\n";
$trees = $wpdb->get_results("SELECT gedcom, treename FROM {$wpdb->prefix}hp_trees");
if ($trees) {
  foreach ($trees as $tree) {
    echo "- {$tree->gedcom}: {$tree->treename}\n";
  }
} else {
  echo "No trees found\n";
}

echo "\n";

// Check people in test_tree
echo "People in test_tree:\n";
$people = $wpdb->get_results("SELECT personID, firstname, lastname, gedcom FROM {$wpdb->prefix}hp_people WHERE gedcom = 'test_tree'");
if ($people) {
  foreach ($people as $person) {
    echo "- {$person->personID}: {$person->firstname} {$person->lastname} (tree: {$person->gedcom})\n";
  }
} else {
  echo "No people found in test_tree\n";
}

echo "\n";

// Check all people
echo "All people (any tree):\n";
$all_people = $wpdb->get_results("SELECT personID, firstname, lastname, gedcom FROM {$wpdb->prefix}hp_people LIMIT 10");
if ($all_people) {
  foreach ($all_people as $person) {
    echo "- {$person->personID}: {$person->firstname} {$person->lastname} (tree: {$person->gedcom})\n";
  }
} else {
  echo "No people found in any tree\n";
}
