<?php

/**
 * Comprehensive Real GEDCOM File Test for Import Options
 *
 * This script tests all import options with a real GEDCOM file containing:
 * - 7 individuals with full genealogy data
 * - 4 families with relationships
 * - 2 sources with citations
 * - 2 repositories
 * - 3 notes
 * - 1 media object
 * - Birth, death, burial, marriage, residence events
 * - Living flags
 * - Complete dates and places
 */

// WordPress bootstrap
$wp_root = dirname(dirname(dirname(dirname(__FILE__))));
require_once($wp_root . '/wp-config.php');
require_once($wp_root . '/wp-includes/wp-db.php');

// Load our classes
require_once(__DIR__ . '/includes/gedcom/class-hp-gedcom-importer.php');

echo "<h1>Comprehensive Import Options Test with Real GEDCOM Data</h1>\n";
echo "<p>Testing with comprehensive_test.ged containing 7 individuals, 4 families, sources, and events</p>\n";

$file_path = __DIR__ . '/comprehensive_test.ged';
$tree_id = 'comprehensive_test';

// Clear any existing data first
clear_test_data($tree_id);

echo "<h2>Test Suite: All Import Options with Real Data</h2>\n";

// Test 1: Replace All Current Data + Uppercase Surnames
echo "<h3>Test 1: Replace All + Uppercase Surnames</h3>\n";
comprehensive_test($file_path, $tree_id, array(
  'del' => 'yes',
  'ucaselast' => 1
), "Clear all data and import with uppercase surnames");

// Test 2: Append Mode
echo "<h3>Test 2: Append Mode</h3>\n";
comprehensive_test($file_path, $tree_id, array(
  'del' => 'append'
), "Add records with offset IDs");

// Test 3: Do Not Replace
echo "<h3>Test 3: Do Not Replace</h3>\n";
comprehensive_test($file_path, $tree_id, array(
  'del' => 'no'
), "Skip existing records");

// Test 4: Matching Records Only
echo "<h3>Test 4: Matching Records Only</h3>\n";
comprehensive_test($file_path, $tree_id, array(
  'del' => 'match'
), "Update only existing records");

// Test 5: Import Media + Latitude/Longitude
echo "<h3>Test 5: Import Media + Lat/Long</h3>\n";
comprehensive_test($file_path, $tree_id, array(
  'del' => 'match',
  'importmedia' => 1,
  'importlatlong' => 1
), "Import with media and geographic data");

// Test 6: Newer Data Only
echo "<h3>Test 6: Newer Data Only</h3>\n";
comprehensive_test($file_path, $tree_id, array(
  'del' => 'match',
  'neweronly' => 1
), "Only import newer records");

// Test 7: Events Only Mode
echo "<h3>Test 7: Events Only Mode</h3>\n";
comprehensive_test($file_path, $tree_id, array(
  'eventsonly' => 'yes'
), "Process only events, skip individual/family records");

// Test 8: Import All Events
echo "<h3>Test 8: Import All Events</h3>\n";
comprehensive_test($file_path, $tree_id, array(
  'del' => 'match',
  'allevents' => 'yes'
), "Import all event types");

// Test 9: Skip Living Flag Recalculation
echo "<h3>Test 9: Skip Living Flag Recalculation</h3>\n";
comprehensive_test($file_path, $tree_id, array(
  'del' => 'match',
  'norecalc' => 1
), "Preserve existing living flags");

function clear_test_data($tree_id)
{
  global $wpdb;

  echo "<p>Clearing existing test data for tree: {$tree_id}</p>\n";

  // Delete all records for this tree
  $tables = array(
    $wpdb->prefix . 'hp_people',
    $wpdb->prefix . 'hp_families',
    $wpdb->prefix . 'hp_children',
    $wpdb->prefix . 'hp_sources',
    $wpdb->prefix . 'hp_media',
    $wpdb->prefix . 'hp_xnotes',
    $wpdb->prefix . 'hp_events'
  );

  foreach ($tables as $table) {
    $wpdb->delete($table, array('gedcom' => $tree_id));
  }
}

function comprehensive_test($file_path, $tree_id, $options, $description)
{
  global $wpdb;

  echo "<p><strong>Options:</strong> " . json_encode($options) . "</p>\n";
  echo "<p><strong>Description:</strong> {$description}</p>\n";

  if (!file_exists($file_path)) {
    echo "<p style='color: red;'>Error: GEDCOM file not found: {$file_path}</p>\n";
    return;
  }

  // Count records before import
  $before = get_record_counts($tree_id);
  echo "<p><strong>Before Import:</strong> People: {$before['people']}, Families: {$before['families']}, Sources: {$before['sources']}, Notes: {$before['notes']}</p>\n";

  try {
    // Create importer with options
    $importer = new HP_GEDCOM_Importer_Controller($file_path, $tree_id, $options);

    // Run import
    $start_time = microtime(true);
    $result = $importer->import();
    $end_time = microtime(true);

    if ($result && $result['success']) {
      // Count records after import
      $after = get_record_counts($tree_id);
      echo "<p><strong>After Import:</strong> People: {$after['people']}, Families: {$after['families']}, Sources: {$after['sources']}, Notes: {$after['notes']}</p>\n";

      $stats = $importer->get_stats();
      echo "<p><strong>Import Stats:</strong> Individuals: {$stats['individuals']}, Families: {$stats['families']}, Sources: {$stats['sources']}</p>\n";
      echo "<p><strong>Duration:</strong> " . round(($end_time - $start_time), 3) . " seconds</p>\n";

      // Test specific option results
      test_option_results($options, $tree_id);

      // Show sample data
      show_sample_data($tree_id, $options);

      echo "<p style='color: green;'>✅ Import completed successfully</p>\n";
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

function get_record_counts($tree_id)
{
  global $wpdb;

  return array(
    'people' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_people WHERE gedcom = '{$tree_id}'"),
    'families' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_families WHERE gedcom = '{$tree_id}'"),
    'sources' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_sources WHERE gedcom = '{$tree_id}'"),
    'notes' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_xnotes WHERE gedcom = '{$tree_id}'")
  );
}

function test_option_results($options, $tree_id)
{
  global $wpdb;

  // Test uppercase surnames
  if (isset($options['ucaselast']) && $options['ucaselast']) {
    $uppercase_surnames = $wpdb->get_results("SELECT personID, lastname FROM {$wpdb->prefix}hp_people WHERE gedcom = '{$tree_id}' AND lastname != '' LIMIT 3");
    echo "<p><strong>Uppercase test:</strong> ";
    foreach ($uppercase_surnames as $person) {
      echo "{$person->personID}:{$person->lastname} ";
    }
    echo "</p>\n";
  }

  // Test append mode IDs
  if (isset($options['del']) && $options['del'] === 'append') {
    $max_person_id = $wpdb->get_var("SELECT MAX(CAST(SUBSTRING(personID, 2) AS UNSIGNED)) FROM {$wpdb->prefix}hp_people WHERE gedcom = '{$tree_id}' AND personID REGEXP '^I[0-9]+\$'");
    $max_family_id = $wpdb->get_var("SELECT MAX(CAST(SUBSTRING(familyID, 2) AS UNSIGNED)) FROM {$wpdb->prefix}hp_families WHERE gedcom = '{$tree_id}' AND familyID REGEXP '^F[0-9]+\$'");
    echo "<p><strong>Append mode:</strong> Max Person ID: {$max_person_id}, Max Family ID: {$max_family_id}</p>\n";
  }

  // Test living flags
  $living_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_people WHERE gedcom = '{$tree_id}' AND living = 1");
  echo "<p><strong>Living individuals:</strong> {$living_count}</p>\n";
}

function show_sample_data($tree_id, $options)
{
  global $wpdb;

  // Show sample individuals
  $people = $wpdb->get_results("SELECT personID, firstname, lastname, birthdate, birthplace FROM {$wpdb->prefix}hp_people WHERE gedcom = '{$tree_id}' ORDER BY personID LIMIT 3");

  if (!empty($people)) {
    echo "<p><strong>Sample individuals:</strong></p>\n";
    echo "<ul>\n";
    foreach ($people as $person) {
      echo "<li>{$person->personID}: {$person->firstname} {$person->lastname} (b. {$person->birthdate} in {$person->birthplace})</li>\n";
    }
    echo "</ul>\n";
  }

  // Show sample families
  $families = $wpdb->get_results("SELECT familyID, husband, wife, marrdate, marrplace FROM {$wpdb->prefix}hp_families WHERE gedcom = '{$tree_id}' ORDER BY familyID LIMIT 2");

  if (!empty($families)) {
    echo "<p><strong>Sample families:</strong></p>\n";
    echo "<ul>\n";
    foreach ($families as $family) {
      echo "<li>{$family->familyID}: {$family->husband} + {$family->wife} (m. {$family->marrdate} in {$family->marrplace})</li>\n";
    }
    echo "</ul>\n";
  }
}

echo "<h2>Test Summary</h2>\n";
echo "<p><strong>All import options have been tested with comprehensive real GEDCOM data!</strong></p>\n";
echo "<p>The test file contained:</p>\n";
echo "<ul>\n";
echo "<li>7 individuals with complete genealogy data</li>\n";
echo "<li>4 families with marriage information</li>\n";
echo "<li>Multiple event types (birth, death, burial, marriage, residence)</li>\n";
echo "<li>Sources and citations</li>\n";
echo "<li>Notes and media objects</li>\n";
echo "<li>Living flags and privacy settings</li>\n";
echo "</ul>\n";
