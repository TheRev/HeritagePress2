<?php

/**
 * Test Import Options Implementation
 *
 * This script tests all the import options to ensure they work correctly.
 */

// WordPress bootstrap
$wp_root = dirname(dirname(dirname(dirname(__FILE__))));
require_once($wp_root . '/wp-config.php');
require_once($wp_root . '/wp-includes/wp-db.php');

// Load our classes
require_once(__DIR__ . '/includes/gedcom/class-hp-gedcom-importer.php');

echo "<h1>Testing Import Options</h1>\n";

// Test 1: Test "Do not replace" option
echo "<h2>Test 1: Do not replace option</h2>\n";
test_import_option('test_simple.ged', 'main', array(
  'del' => 'no'
));

// Test 2: Test "Matching records only" option
echo "<h2>Test 2: Matching records only option</h2>\n";
test_import_option('test_simple.ged', 'main', array(
  'del' => 'match'
));

// Test 3: Test "All current data" option
echo "<h2>Test 3: All current data option</h2>\n";
test_import_option('test_simple.ged', 'main', array(
  'del' => 'yes'
));

// Test 4: Test "Append all" option
echo "<h2>Test 4: Append all option</h2>\n";
test_import_option('test_simple.ged', 'main', array(
  'del' => 'append'
));

// Test 5: Test uppercase surnames
echo "<h2>Test 5: Uppercase surnames option</h2>\n";
test_import_option('test_simple.ged', 'main', array(
  'del' => 'match',
  'ucaselast' => 1
));

// Test 6: Test newer data only
echo "<h2>Test 6: Newer data only option</h2>\n";
test_import_option('test_simple.ged', 'main', array(
  'del' => 'match',
  'neweronly' => 1
));

// Test 7: Test import media
echo "<h2>Test 7: Import media option</h2>\n";
test_import_option('test_simple.ged', 'main', array(
  'del' => 'match',
  'importmedia' => 1
));

// Test 8: Test latitude/longitude import
echo "<h2>Test 8: Import latitude/longitude option</h2>\n";
test_import_option('test_simple.ged', 'main', array(
  'del' => 'match',
  'importlatlong' => 1
));

// Test 9: Test events only mode
echo "<h2>Test 9: Events only mode</h2>\n";
test_import_option('test_simple.ged', 'main', array(
  'eventsonly' => 'yes'
));

function test_import_option($filename, $tree_id, $options)
{
  global $wpdb;

  echo "<p><strong>Testing options:</strong> " . json_encode($options) . "</p>\n";

  $file_path = __DIR__ . '/' . $filename;

  if (!file_exists($file_path)) {
    echo "<p style='color: red;'>Error: Test file {$filename} not found</p>\n";
    return;
  }

  try {
    // Count records before import
    $before_people = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_people WHERE gedcom = '{$tree_id}'");
    $before_families = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_families WHERE gedcom = '{$tree_id}'");

    echo "<p>Records before import: People: {$before_people}, Families: {$before_families}</p>\n";

    // Create importer with options
    $importer = new HP_GEDCOM_Importer_Controller($file_path, $tree_id, $options);

    // Run import
    $result = $importer->import();

    if ($result && $result['success']) {
      // Count records after import
      $after_people = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_people WHERE gedcom = '{$tree_id}'");
      $after_families = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_families WHERE gedcom = '{$tree_id}'");

      echo "<p>Records after import: People: {$after_people}, Families: {$after_families}</p>\n";

      $stats = $importer->get_stats();
      echo "<p><strong>Import stats:</strong> " . json_encode($stats) . "</p>\n";

      // Test specific option behavior
      test_option_behavior($options, $tree_id);

      echo "<p style='color: green;'>✓ Import completed successfully</p>\n";
    } else {
      echo "<p style='color: red;'>✗ Import failed: " . ($result['error'] ?? 'Unknown error') . "</p>\n";
      $errors = $importer->get_errors();
      if (!empty($errors)) {
        echo "<p>Errors: " . implode(', ', $errors) . "</p>\n";
      }
    }
  } catch (Exception $e) {
    echo "<p style='color: red;'>✗ Exception: " . $e->getMessage() . "</p>\n";
  }

  echo "<hr>\n";
}

function test_option_behavior($options, $tree_id)
{
  global $wpdb;

  // Test uppercase surnames
  if (isset($options['ucaselast']) && $options['ucaselast']) {
    $uppercase_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_people WHERE gedcom = '{$tree_id}' AND lastname = UPPER(lastname) AND lastname != ''");
    echo "<p>Records with uppercase surnames: {$uppercase_count}</p>\n";
  }

  // Test append mode ID offsets
  if (isset($options['del']) && $options['del'] === 'append') {
    $max_person_id = $wpdb->get_var("SELECT MAX(CAST(SUBSTRING(personID, 2) AS UNSIGNED)) FROM {$wpdb->prefix}hp_people WHERE gedcom = '{$tree_id}' AND personID REGEXP '^I[0-9]+$'");
    echo "<p>Max person ID after append: {$max_person_id}</p>\n";
  }

  // Test events only mode (should not add new people/families)
  if (isset($options['eventsonly']) && $options['eventsonly'] === 'yes') {
    echo "<p>Events only mode - records should not be added, only events updated</p>\n";
  }
}

echo "<p><strong>All import option tests completed!</strong></p>\n";
