<?php

/**
 * REAL WordPress GEDCOM Import Test - No Mock BS
 * Actually imports into the database and verifies data
 */

// Load WordPress properly
require_once('c:/MAMP/htdocs/HeritagePress2/wp-config.php');

// Load the enhanced parser
require_once('includes/gedcom/class-hp-enhanced-gedcom-parser.php');

echo "=== REAL GEDCOM IMPORT TEST ===\n";
echo "File: C:\\MAMP\\htdocs\\HeritagePress2\\gedcom_test_files\\FTM_lyle_2025-06-17.ged\n";

$gedcom_file = 'C:\\MAMP\\htdocs\\HeritagePress2\\gedcom_test_files\\FTM_lyle_2025-06-17.ged';

if (!file_exists($gedcom_file)) {
  echo "ERROR: GEDCOM file not found!\n";
  exit(1);
}

echo "File size: " . filesize($gedcom_file) . " bytes\n\n";

// Clear existing data first
echo "Step 1: Clearing existing test data...\n";
global $wpdb;

$tables_to_clear = array(
  'hp_people',
  'hp_families',
  'hp_sources',
  'hp_events',
  'hp_citations',
  'hp_media',
  'hp_xnotes',
  'hp_repositories'
);

foreach ($tables_to_clear as $table) {
  $full_table = $wpdb->prefix . $table;
  $result = $wpdb->delete($full_table, array('gedcom' => 'ftm_test'));
  echo "  Cleared $table: " . ($result !== false ? "OK" : "FAILED") . "\n";
}

echo "\nStep 2: Starting REAL GEDCOM import...\n";

try {
  // Create parser with real WordPress database
  $parser = new HP_Enhanced_GEDCOM_Parser($gedcom_file, 'ftm_test', array(
    'del' => 'yes',
    'ucaselast' => 0,
    'allevents' => 'yes'
  ));

  echo "Parser created successfully\n";

  // Run the actual import
  $result = $parser->parse();

  if ($result['success']) {
    echo "✅ Import completed successfully!\n\n";

    echo "Step 3: Verifying data in actual database tables...\n";

    // Verify people table
    $people_count = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$wpdb->prefix}hp_people WHERE gedcom = %s",
      'ftm_test'
    ));
    echo "People records: $people_count\n";

    if ($people_count > 0) {
      echo "\nDetailed people data:\n";
      $people = $wpdb->get_results($wpdb->prepare(
        "SELECT personID, firstname, lastname, sex, birthdate, birthplace, deathdate, deathplace, burialdate, burialplace FROM {$wpdb->prefix}hp_people WHERE gedcom = %s",
        'ftm_test'
      ));

      foreach ($people as $person) {
        echo "  Person ID: {$person['personID']}\n";
        echo "    Name: {$person['firstname']} {$person['lastname']}\n";
        echo "    Sex: {$person['sex']}\n";
        echo "    Birth: {$person['birthdate']} at {$person['birthplace']}\n";
        echo "    Death: {$person['deathdate']} at {$person['deathplace']}\n";
        echo "    Burial: {$person['burialdate']} at {$person['burialplace']}\n";
        echo "\n";
      }
    }

    // Verify families table
    $families_count = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$wpdb->prefix}hp_families WHERE gedcom = %s",
      'ftm_test'
    ));
    echo "Family records: $families_count\n";

    if ($families_count > 0) {
      echo "\nDetailed family data:\n";
      $families = $wpdb->get_results($wpdb->prepare(
        "SELECT familyID, husband, wife, marrdate, marrplace, divdate, divplace FROM {$wpdb->prefix}hp_families WHERE gedcom = %s",
        'ftm_test'
      ));

      foreach ($families as $family) {
        echo "  Family ID: {$family['familyID']}\n";
        echo "    Husband: {$family['husband']}\n";
        echo "    Wife: {$family['wife']}\n";
        echo "    Marriage: {$family['marrdate']} at {$family['marrplace']}\n";
        echo "    Divorce: {$family['divdate']} at {$family['divplace']}\n";
        echo "\n";
      }
    }

    // Verify events table
    $events_count = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$wpdb->prefix}hp_events WHERE gedcom = %s",
      'ftm_test'
    ));
    echo "Event records: $events_count\n";

    if ($events_count > 0) {
      echo "\nDetailed event data:\n";
      $events = $wpdb->get_results($wpdb->prepare(
        "SELECT persfamID, eventtypeID, eventdate, eventplace, parenttag FROM {$wpdb->prefix}hp_events WHERE gedcom = %s ORDER BY persfamID",
        'ftm_test'
      ));

      foreach ($events as $event) {
        echo "  Person/Family: {$event['persfamID']}\n";
        echo "    Event Type ID: {$event['eventtypeID']}\n";
        echo "    Date: {$event['eventdate']}\n";
        echo "    Place: {$event['eventplace']}\n";
        echo "    Parent Tag: {$event['parenttag']}\n";
        echo "\n";
      }
    }

    // Verify sources table
    $sources_count = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$wpdb->prefix}hp_sources WHERE gedcom = %s",
      'ftm_test'
    ));
    echo "Source records: $sources_count\n";

    // Show import statistics
    echo "\n=== IMPORT STATISTICS ===\n";
    if (isset($result['stats'])) {
      foreach ($result['stats'] as $key => $value) {
        if (!is_array($value)) {
          echo "$key: $value\n";
        }
      }
    }

    // Show warnings if any
    if (!empty($result['warnings'])) {
      echo "\n=== WARNINGS ===\n";
      $warning_count = count($result['warnings']);
      echo "Total warnings: $warning_count\n";
      if ($warning_count <= 20) {
        foreach ($result['warnings'] as $warning) {
          echo "  - $warning\n";
        }
      } else {
        echo "First 10 warnings:\n";
        for ($i = 0; $i < 10; $i++) {
          echo "  - " . $result['warnings'][$i] . "\n";
        }
        echo "  ... and " . ($warning_count - 10) . " more\n";
      }
    }
  } else {
    echo "❌ Import FAILED!\n";
    echo "Error: " . $result['error'] . "\n";
    if (!empty($result['errors'])) {
      foreach ($result['errors'] as $error) {
        echo "  - $error\n";
      }
    }
  }
} catch (Exception $e) {
  echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
  echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== REAL IMPORT TEST COMPLETED ===\n";
