<?php

/**
 * Comprehensive Import Options Test
 * Tests all GEDCOM import options systematically based on TNG reference implementation
 */

// WordPress setup (minimal for testing)
if (!defined('ABSPATH')) {
  define('ABSPATH', dirname(__FILE__) . '/../../../');
}

require_once(ABSPATH . 'wp-config.php');
require_once(dirname(__FILE__) . '/includes/gedcom/class-hp-gedcom-importer.php');

// Set up test variables
$test_gedcom_file = dirname(__FILE__) . '/../gedcom_test_files/sample-from-5.5.1-standard.ged';
$tree_id = 'test';

function test_import_option($option_name, $options = array())
{
  global $wpdb;

  echo "\n=== TESTING: $option_name ===\n";

  // Clear existing data
  $tables = array(
    $wpdb->prefix . 'hp_people',
    $wpdb->prefix . 'hp_families',
    $wpdb->prefix . 'hp_children',
    $wpdb->prefix . 'hp_sources',
    $wpdb->prefix . 'hp_media',
    $wpdb->prefix . 'hp_events',
    $wpdb->prefix . 'hp_citations',
    $wpdb->prefix . 'hp_xnotes',
    $wpdb->prefix . 'hp_repositories'
  );

  foreach ($tables as $table) {
    $wpdb->delete($table, array('gedcom' => 'default'));
  }

  // Run import with specified options
  $gedcom_file = 'c:\\MAMP\\htdocs\\HeritagePress2\\gedcom_test_files\\sample-from-5.5.1-standard.ged';

  try {
    $parser = new HP_Enhanced_GEDCOM_Parser($gedcom_file, 'default', $options);
    $parser->parse();
    $stats = $parser->get_stats();

    echo "Import completed successfully:\n";
    echo "- Individuals: {$stats['individuals']}\n";
    echo "- Families: {$stats['families']}\n";
    echo "- Sources: {$stats['sources']}\n";
    echo "- Events: {$stats['events']}\n";

    // Check database counts
    $people_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_people WHERE gedcom = 'default'");
    $families_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_families WHERE gedcom = 'default'");
    $events_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_events WHERE gedcom = 'default'");
    $citations_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_citations WHERE gedcom = 'default'");

    echo "Database verification:\n";
    echo "- People: $people_count\n";
    echo "- Families: $families_count\n";
    echo "- Events: $events_count\n";
    echo "- Citations: $citations_count\n";

    // Check for uppercase surnames if that option was set
    if (isset($options['ucaselast']) && $options['ucaselast']) {
      $uppercase_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_people WHERE gedcom = 'default' AND lastname = UPPER(lastname)");
      echo "- Uppercase surnames: $uppercase_count\n";
    }

    return true;
  } catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    return false;
  }
}

function test_append_mode()
{
  global $wpdb;

  echo "\n=== TESTING: APPEND MODE (Multiple Imports) ===\n";

  // Clear existing data
  $tables = array(
    $wpdb->prefix . 'hp_people',
    $wpdb->prefix . 'hp_families',
    $wpdb->prefix . 'hp_children',
    $wpdb->prefix . 'hp_sources',
    $wpdb->prefix . 'hp_media',
    $wpdb->prefix . 'hp_events',
    $wpdb->prefix . 'hp_citations',
    $wpdb->prefix . 'hp_xnotes',
    $wpdb->prefix . 'hp_repositories'
  );

  foreach ($tables as $table) {
    $wpdb->delete($table, array('gedcom' => 'default'));
  }

  $gedcom_file = 'c:\\MAMP\\htdocs\\HeritagePress2\\gedcom_test_files\\sample-from-5.5.1-standard.ged';

  // First import (normal)
  echo "First import (normal mode):\n";
  $parser1 = new HP_Enhanced_GEDCOM_Parser($gedcom_file, 'default', array('del' => 'yes'));
  $parser1->parse();

  $people_after_first = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_people WHERE gedcom = 'default'");
  echo "- People after first import: $people_after_first\n";

  // Second import (append mode)
  echo "Second import (append mode):\n";
  $parser2 = new HP_Enhanced_GEDCOM_Parser($gedcom_file, 'default', array('del' => 'append'));
  $parser2->parse();

  $people_after_second = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_people WHERE gedcom = 'default'");
  echo "- People after second import: $people_after_second\n";

  // Check if IDs were properly offset
  $max_id = $wpdb->get_var("SELECT MAX(CAST(SUBSTRING(personID, 2) AS UNSIGNED)) FROM {$wpdb->prefix}hp_people WHERE gedcom = 'default' AND personID REGEXP '^I[0-9]+$'");
  echo "- Highest person ID number: $max_id\n";

  if ($people_after_second == $people_after_first * 2) {
    echo "✅ Append mode working correctly\n";
  } else {
    echo "❌ Append mode not working as expected\n";
  }
}

echo "=== COMPREHENSIVE IMPORT OPTIONS TEST ===\n";

// Test 1: Default import (all current data)
test_import_option("Default Import", array('del' => 'yes'));

// Test 2: Import all events
test_import_option("Import All Events", array('allevents' => '1', 'del' => 'yes'));

// Test 3: Events only
test_import_option("Events Only", array('eventsonly' => '1', 'del' => 'yes'));

// Test 4: Matching records only
test_import_option("Matching Records Only", array('del' => 'match'));

// Test 5: Do not replace
test_import_option("Do Not Replace", array('del' => 'no'));

// Test 6: Uppercase surnames
test_import_option("Uppercase Surnames", array('ucaselast' => 1, 'del' => 'yes'));

// Test 7: Skip living flag recalculation
test_import_option("Skip Living Flag Recalc", array('norecalc' => 1, 'del' => 'yes'));

// Test 8: Import newer data only
test_import_option("Import Newer Data Only", array('neweronly' => 1, 'del' => 'yes'));

// Test 9: Import media links
test_import_option("Import Media Links", array('importmedia' => 1, 'del' => 'yes'));

// Test 10: Import latitude/longitude
test_import_option("Import Lat/Long", array('importlatlong' => 1, 'del' => 'yes'));

// Test 11: Append mode (special test)
test_append_mode();

echo "\n=== COMPREHENSIVE TEST COMPLETED ===\n";
