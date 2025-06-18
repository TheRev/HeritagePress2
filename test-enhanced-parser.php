<?php

/**
 * Test Enhanced GEDCOM Parser with Complex File
 */

// WordPress environment
require_once('c:/MAMP/htdocs/HeritagePress2/wp-config.php');

// Plugin constants
define('HERITAGEPRESS_PLUGIN_DIR', 'c:/MAMP/htdocs/HeritagePress2/wp-content/plugins/heritagepress/');

// Load the importer
require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/class-hp-gedcom-importer.php';

echo "Testing Enhanced GEDCOM Parser with Complex File...\n\n";

// Test file path - the comprehensive GEDCOM file
$test_file = 'C:/MAMP/htdocs/HeritagePress2/gedcom_test_files/sample-from-5.5.1-standard.ged';

echo "File: $test_file\n";

if (!file_exists($test_file)) {
  echo "ERROR: Test file does not exist!\n";
  exit(1);
}

echo "File size: " . filesize($test_file) . " bytes\n";
echo "File exists: YES\n\n";

try {
  // Create importer instance
  $importer = new HP_GEDCOM_Importer_Controller($test_file, 'main');

  echo "Starting enhanced import...\n";

  // Run the import
  $result = $importer->import();

  // Display results
  if ($result['success']) {
    echo "SUCCESS: Enhanced GEDCOM import completed!\n\n";

    $stats = $importer->get_stats();
    echo "=== PARSING STATISTICS ===\n";
    echo "Individuals: " . ($stats['individuals'] ?? 0) . "\n";
    echo "Families: " . ($stats['families'] ?? 0) . "\n";
    echo "Sources: " . ($stats['sources'] ?? 0) . "\n";
    echo "Media: " . ($stats['media'] ?? 0) . "\n";
    echo "Notes: " . ($stats['notes'] ?? 0) . "\n";
    echo "Events: " . ($stats['events'] ?? 0) . "\n";

    $warnings = $importer->get_warnings();
    if (!empty($warnings)) {
      echo "\n=== WARNINGS ===\n";
      foreach ($warnings as $warning) {
        echo "- $warning\n";
      }
    }

    // Now check what was actually saved to the database
    echo "\n=== DATABASE VERIFICATION ===\n";
    global $wpdb;

    // Check individuals
    $people_table = $wpdb->prefix . 'hp_people';
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $people_table WHERE gedcom = 'main'");
    echo "Individuals in database: $count\n";

    if ($count > 0) {
      $people = $wpdb->get_results("SELECT personID, firstname, lastname, sex, birthdate, birthplace, deathdate, famc FROM $people_table WHERE gedcom = 'main' ORDER BY personID");
      echo "\nIndividuals details:\n";
      foreach ($people as $person) {
        echo "- {$person->personID}: {$person->firstname} {$person->lastname} ({$person->sex})";
        if ($person->birthdate) echo " b. {$person->birthdate}";
        if ($person->birthplace) echo " in {$person->birthplace}";
        if ($person->deathdate) echo " d. {$person->deathdate}";
        if ($person->famc) echo " [child of family {$person->famc}]";
        echo "\n";
      }
    }

    // Check families
    $families_table = $wpdb->prefix . 'hp_families';
    $family_count = $wpdb->get_var("SELECT COUNT(*) FROM $families_table WHERE gedcom = 'main'");
    echo "\nFamilies in database: $family_count\n";

    if ($family_count > 0) {
      $families = $wpdb->get_results("SELECT familyID, husband, wife, marrdate, marrplace FROM $families_table WHERE gedcom = 'main' ORDER BY familyID");
      echo "\nFamily details:\n";
      foreach ($families as $family) {
        echo "- {$family->familyID}: Husband={$family->husband}, Wife={$family->wife}";
        if ($family->marrdate) echo " m. {$family->marrdate}";
        if ($family->marrplace) echo " in {$family->marrplace}";
        echo "\n";
      }

      // Check children relationships
      $children_table = $wpdb->prefix . 'hp_children';
      $children = $wpdb->get_results("SELECT familyID, personID, ordernum FROM $children_table WHERE gedcom = 'main' ORDER BY familyID, ordernum");
      if (!empty($children)) {
        echo "\nChildren relationships:\n";
        foreach ($children as $child) {
          echo "- Family {$child->familyID}: Child {$child->personID} (order {$child->ordernum})\n";
        }
      }
    }
  } else {
    echo "FAILED: Import failed!\n";
    echo "Error: " . ($result['error'] ?? 'Unknown error') . "\n";

    $errors = $importer->get_errors();
    if (!empty($errors)) {
      echo "\nErrors:\n";
      foreach ($errors as $error) {
        echo "- $error\n";
      }
    }
  }
} catch (Exception $e) {
  echo "EXCEPTION: " . $e->getMessage() . "\n";
  echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
