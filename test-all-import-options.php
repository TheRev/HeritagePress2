<?php

/**
 * Comprehensive Test for All GEDCOM Import Options
 * Tests each import option and verifies correct data placement and relationships
 */

// Include WordPress
require_once('c:/MAMP/htdocs/HeritagePress2/wp-config.php');

// Plugin constants
define('HERITAGEPRESS_PLUGIN_DIR', 'c:/MAMP/htdocs/HeritagePress2/wp-content/plugins/heritagepress/');

// Load the importer
require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/class-hp-gedcom-importer.php';

echo "=== COMPREHENSIVE IMPORT OPTIONS TEST ===\n";
echo "Testing file: sample-from-5.5.1-standard.ged\n\n";

// Database connection
global $wpdb;

// Available import options to test
$import_options = [
  'import_all_events' => [true, false],
  'events_only' => [true, false],
  'all_current_data' => [true, false],
  'matching_records_only' => [true, false],
  'do_not_replace' => [true, false],
  'append_all' => [true, false],
  'uppercase_surnames' => [true, false],
  'skip_living_flag_recalculation' => [true, false],
  'import_newer_data_only' => [true, false],
  'import_media_links' => [true, false],
  'import_lat_long' => [true, false]
];

// Clean data function
function clean_test_data()
{
  global $wpdb;

  $tables = [
    'hp_people',
    'hp_families',
    'hp_sources',
    'hp_repositories',
    'hp_citations',
    'hp_events',
    'hp_xnotes',
    'hp_media',
    'hp_trees',
    'hp_tree_people',
    'hp_tree_families'
  ];

  foreach ($tables as $table) {
    $wpdb->query("DELETE FROM {$wpdb->prefix}{$table} WHERE 1=1");
  }

  // Reset auto-increment
  foreach ($tables as $table) {
    $wpdb->query("ALTER TABLE {$wpdb->prefix}{$table} AUTO_INCREMENT = 1");
  }
}

// Get data counts function
function get_data_counts()
{
  global $wpdb;

  return [
    'individuals' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_people"),
    'families' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_families"),
    'sources' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_sources"),
    'repositories' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_repositories"),
    'citations' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_citations"),
    'events' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_events"),
    'notes' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_xnotes"),
    'media' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_media"),
    'trees' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_trees"),
    'tree_people' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_tree_people"),
    'tree_families' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_tree_families")
  ];
}

// Verify data relationships function
function verify_relationships()
{
  global $wpdb;

  $issues = [];

  // Check family-people relationships
  $orphan_families = $wpdb->get_var("
        SELECT COUNT(*) FROM {$wpdb->prefix}hp_families f
        LEFT JOIN {$wpdb->prefix}hp_people p1 ON f.husband = p1.personID
        LEFT JOIN {$wpdb->prefix}hp_people p2 ON f.wife = p2.personID
        WHERE (f.husband IS NOT NULL AND p1.personID IS NULL)
           OR (f.wife IS NOT NULL AND p2.personID IS NULL)
    ");

  if ($orphan_families > 0) {
    $issues[] = "Found {$orphan_families} families with missing spouse references";
  }

  // Check source-repository relationships
  $orphan_sources = $wpdb->get_var("
        SELECT COUNT(*) FROM {$wpdb->prefix}hp_sources s
        LEFT JOIN {$wpdb->prefix}hp_repositories r ON s.repository = r.repositoryID
        WHERE s.repository IS NOT NULL AND r.repositoryID IS NULL
    ");

  if ($orphan_sources > 0) {
    $issues[] = "Found {$orphan_sources} sources with missing repository references";
  }

  // Check tree assignments
  $people_without_trees = $wpdb->get_var("
        SELECT COUNT(*) FROM {$wpdb->prefix}hp_people p
        LEFT JOIN {$wpdb->prefix}hp_tree_people tp ON p.personID = tp.personID
        WHERE tp.personID IS NULL
    ");

  if ($people_without_trees > 0) {
    $issues[] = "Found {$people_without_trees} people not assigned to any tree";
  }

  return $issues;
}

// Check for uppercase surnames
function check_uppercase_surnames()
{
  global $wpdb;

  $uppercase_count = $wpdb->get_var("
        SELECT COUNT(*) FROM {$wpdb->prefix}hp_people
        WHERE lastname = UPPER(lastname) AND lastname != ''
    ");

  return $uppercase_count;
}

// Test a specific configuration
function test_import_option($option_name, $option_value, $test_number)
{
  echo "\n--- TEST {$test_number}: {$option_name} = " . ($option_value ? 'TRUE' : 'FALSE') . " ---\n";

  // Clean data
  clean_test_data();

  // Setup import options - mapping our test options to the actual parser options
  $import_options = [
    'del' => 'match',
    'allevents' => '',
    'eventsonly' => '',
    'ucaselast' => 0,
    'norecalc' => 0,
    'neweronly' => 0,
    'importmedia' => 0,
    'importlatlong' => 0,
    'offsetchoice' => 'auto',
    'useroffset' => 0,
    'branch' => ''
  ];

  // Map our test option names to actual parser option names
  switch ($option_name) {
    case 'import_all_events':
      $import_options['allevents'] = $option_value ? 'yes' : '';
      break;
    case 'events_only':
      $import_options['eventsonly'] = $option_value ? 'yes' : '';
      break;
    case 'all_current_data':
      $import_options['del'] = $option_value ? 'yes' : 'match';
      break;
    case 'matching_records_only':
      $import_options['del'] = $option_value ? 'match' : 'no';
      break;
    case 'do_not_replace':
      $import_options['del'] = $option_value ? 'no' : 'match';
      break;
    case 'append_all':
      $import_options['del'] = $option_value ? 'append' : 'match';
      break;
    case 'uppercase_surnames':
      $import_options['ucaselast'] = $option_value ? 1 : 0;
      break;
    case 'skip_living_flag_recalculation':
      $import_options['norecalc'] = $option_value ? 1 : 0;
      break;
    case 'import_newer_data_only':
      $import_options['neweronly'] = $option_value ? 1 : 0;
      break;
    case 'import_media_links':
      $import_options['importmedia'] = $option_value ? 1 : 0;
      break;
    case 'import_lat_long':
      $import_options['importlatlong'] = $option_value ? 1 : 0;
      break;
  }

  // Get counts before import
  $before_counts = get_data_counts();

  try {
    // Import GEDCOM using the HP_GEDCOM_Importer_Controller
    $gedcom_file = 'c:/MAMP/htdocs/HeritagePress2/gedcom_test_files/sample-from-5.5.1-standard.ged';

    if (!file_exists($gedcom_file)) {
      echo "ERROR: GEDCOM file not found!\n";
      return false;
    }

    $importer = new HP_GEDCOM_Importer_Controller($gedcom_file, 'main', $import_options);
    $result = $importer->import();

    if (!$result['success']) {
      echo "ERROR: Import failed! " . $result['error'] . "\n";
      return false;
    }

    // Get counts after import
    $after_counts = get_data_counts();

    // Display results
    echo "Data imported:\n";
    foreach ($after_counts as $type => $count) {
      $change = $count - $before_counts[$type];
      echo "  {$type}: {$count} (+{$change})\n";
    }

    // Verify relationships
    $relationship_issues = verify_relationships();
    if (empty($relationship_issues)) {
      echo "✓ All relationships verified correctly\n";
    } else {
      echo "⚠ Relationship issues found:\n";
      foreach ($relationship_issues as $issue) {
        echo "  - {$issue}\n";
      }
    }

    // Special checks for specific options
    if ($option_name === 'uppercase_surnames' && $option_value) {
      $uppercase_count = check_uppercase_surnames();
      echo "Uppercase surnames: {$uppercase_count}\n";
      if ($uppercase_count > 0) {
        echo "✓ Uppercase surnames option working\n";
      } else {
        echo "⚠ Uppercase surnames option may not be working\n";
      }
    }

    if ($option_name === 'events_only' && $option_value) {
      if ($after_counts['individuals'] == 0 && $after_counts['families'] == 0) {
        echo "✓ Events only option working (no individuals/families imported)\n";
      } else {
        echo "⚠ Events only option may not be working\n";
      }
    }

    echo "✓ Test completed successfully\n";
    return true;
  } catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    return false;
  }
}

// Test baseline (all options false)
echo "=== BASELINE TEST (All Options FALSE) ===\n";
test_import_option('baseline', false, 0);

// Test each option individually
$test_number = 1;
foreach ($import_options as $option_name => $values) {
  foreach ($values as $value) {
    test_import_option($option_name, $value, $test_number);
    $test_number++;
  }
}

// Test combination scenarios
echo "\n=== COMBINATION TESTS ===\n";

// Test 1: Import all events + Import media links
echo "\n--- COMBINATION TEST 1: import_all_events + import_media_links ---\n";
clean_test_data();

$combination_options = [
  'del' => 'match',
  'allevents' => 'yes',
  'eventsonly' => '',
  'ucaselast' => 0,
  'norecalc' => 0,
  'neweronly' => 0,
  'importmedia' => 1,
  'importlatlong' => 0,
  'offsetchoice' => 'auto',
  'useroffset' => 0,
  'branch' => ''
];

try {
  $gedcom_file = 'c:/MAMP/htdocs/HeritagePress2/gedcom_test_files/sample-from-5.5.1-standard.ged';
  $importer = new HP_GEDCOM_Importer_Controller($gedcom_file, 'main', $combination_options);
  $result = $importer->import();

  if ($result['success']) {
    $counts = get_data_counts();
    echo "Combined import results:\n";
    foreach ($counts as $type => $count) {
      echo "  {$type}: {$count}\n";
    }

    $relationship_issues = verify_relationships();
    if (empty($relationship_issues)) {
      echo "✓ All relationships verified correctly\n";
    } else {
      echo "⚠ Relationship issues found:\n";
      foreach ($relationship_issues as $issue) {
        echo "  - {$issue}\n";
      }
    }
    echo "✓ Combination test completed successfully\n";
  } else {
    echo "ERROR: Combination import failed!\n";
  }
} catch (Exception $e) {
  echo "ERROR: " . $e->getMessage() . "\n";
}

// Test 2: Uppercase surnames + Append all
echo "\n--- COMBINATION TEST 2: uppercase_surnames + append_all ---\n";
clean_test_data();

// First import
$first_options = [
  'del' => 'match',
  'allevents' => '',
  'eventsonly' => '',
  'ucaselast' => 0,
  'norecalc' => 0,
  'neweronly' => 0,
  'importmedia' => 0,
  'importlatlong' => 0,
  'offsetchoice' => 'auto',
  'useroffset' => 0,
  'branch' => ''
];

try {
  $gedcom_file = 'c:/MAMP/htdocs/HeritagePress2/gedcom_test_files/sample-from-5.5.1-standard.ged';
  $importer = new HP_GEDCOM_Importer_Controller($gedcom_file, 'main', $first_options);
  $result = $importer->import();

  if ($result['success']) {
    $first_counts = get_data_counts();
    echo "First import completed. Counts:\n";
    foreach ($first_counts as $type => $count) {
      echo "  {$type}: {$count}\n";
    }

    // Second import with uppercase surnames + append all
    $second_options = [
      'del' => 'append',
      'allevents' => '',
      'eventsonly' => '',
      'ucaselast' => 1,
      'norecalc' => 0,
      'neweronly' => 0,
      'importmedia' => 0,
      'importlatlong' => 0,
      'offsetchoice' => 'auto',
      'useroffset' => 0,
      'branch' => ''
    ];

    $importer2 = new HP_GEDCOM_Importer_Controller($gedcom_file, 'main', $second_options);
    $result2 = $importer2->import();

    if ($result2['success']) {
      $second_counts = get_data_counts();
      echo "Second import completed. Counts:\n";
      foreach ($second_counts as $type => $count) {
        $change = $count - $first_counts[$type];
        echo "  {$type}: {$count} (+{$change})\n";
      }

      $uppercase_count = check_uppercase_surnames();
      echo "Uppercase surnames: {$uppercase_count}\n";

      if ($second_counts['individuals'] > $first_counts['individuals']) {
        echo "✓ Append all option working (more individuals after second import)\n";
      }

      if ($uppercase_count > 0) {
        echo "✓ Uppercase surnames option working\n";
      }

      echo "✓ Combination test 2 completed successfully\n";
    } else {
      echo "ERROR: Second import failed!\n";
    }
  } else {
    echo "ERROR: First import failed!\n";
  }
} catch (Exception $e) {
  echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== ALL IMPORT OPTIONS TESTS COMPLETED ===\n";
echo "Review the results above to ensure all options work correctly.\n";
echo "Check that:\n";
echo "1. Each option produces expected behavior\n";
echo "2. All relationships are maintained\n";
echo "3. No data is lost or corrupted\n";
echo "4. Combination options work together properly\n";
