<?php

/**
 * Test Uppercase Surnames Option Specifically
 */

// WordPress bootstrap
$wp_root = dirname(dirname(dirname(dirname(__FILE__))));
require_once($wp_root . '/wp-config.php');
require_once($wp_root . '/wp-includes/wp-db.php');

// Load our classes
require_once(__DIR__ . '/includes/gedcom/class-hp-gedcom-importer.php');

echo "Testing Uppercase Surnames Option (Replace All Mode)\n";

$file_path = __DIR__ . '/test_simple.ged';
$tree_id = 'main';
$options = array(
  'del' => 'yes',      // Replace all current data
  'ucaselast' => 1     // Uppercase surnames
);

global $wpdb;

echo "Testing options: " . json_encode($options) . "\n";

try {
  // Create importer with options
  $importer = new HP_GEDCOM_Importer_Controller($file_path, $tree_id, $options);

  // Run import
  $result = $importer->import();

  if ($result && $result['success']) {
    echo "Import completed successfully!\n";

    // Check the surnames
    $results = $wpdb->get_results("SELECT personID, lastname, firstname FROM {$wpdb->prefix}hp_people WHERE gedcom = '{$tree_id}' ORDER BY personID");

    echo "\nSurnames after import with uppercase option:\n";
    foreach ($results as $row) {
      echo $row->personID . ': ' . $row->firstname . ' [' . $row->lastname . "]\n";
    }

    // Check if all surnames are uppercase
    $lowercase_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_people WHERE gedcom = '{$tree_id}' AND lastname != UPPER(lastname) AND lastname != ''");

    if ($lowercase_count == 0) {
      echo "\n✓ All surnames are uppercase!\n";
    } else {
      echo "\n✗ Found {$lowercase_count} surnames that are not uppercase\n";
    }
  } else {
    echo "Import failed: " . ($result['error'] ?? 'Unknown error') . "\n";
  }
} catch (Exception $e) {
  echo "Exception: " . $e->getMessage() . "\n";
}
