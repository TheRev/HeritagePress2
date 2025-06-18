<?php
// Test with real-world GEDCOM file - FTM_lyle_2025-06-17.ged
require_once('c:/MAMP/htdocs/HeritagePress2/wp-config.php');

// Plugin constants
define('HERITAGEPRESS_PLUGIN_DIR', 'c:/MAMP/htdocs/HeritagePress2/wp-content/plugins/heritagepress/');

// Load the importer
require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/class-hp-gedcom-importer.php';

global $wpdb;

echo "=== TESTING WITH REAL-WORLD GEDCOM FILE ===\n";
echo "Testing with: FTM_lyle_2025-06-17.ged\n\n";

// Clean existing test data first
echo "Cleaning existing 'lyle' test data...\n";
$tables = [
  'hp_people',
  'hp_families',
  'hp_sources',
  'hp_media',
  'hp_xnotes',
  'hp_events',
  'hp_repositories'
];

foreach ($tables as $table) {
  $full_table = $wpdb->prefix . $table;
  $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table WHERE gedcom = 'lyle'");
  if ($count > 0) {
    $wpdb->query("DELETE FROM $full_table WHERE gedcom = 'lyle'");
    echo "Cleaned $count records from $table\n";
  }
}

// Test with the FTM GEDCOM file
$gedcom_file = 'c:/MAMP/htdocs/HeritagePress2/gedcom_test_files/FTM_lyle_2025-06-17.ged';

if (!file_exists($gedcom_file)) {
  echo "ERROR: GEDCOM file not found: $gedcom_file\n";
  exit;
}

echo "\nFile: $gedcom_file\n";
echo "File size: " . filesize($gedcom_file) . " bytes\n";
echo "File exists: YES\n\n";

try {
  // Create importer instance
  $importer = new HP_GEDCOM_Importer_Controller($gedcom_file, 'lyle');

  echo "Starting real-world GEDCOM import...\n";

  // Run the import
  $result = $importer->import();

  // Display results
  if ($result['success']) {
    echo "SUCCESS: Real-world GEDCOM import completed!\n\n";

    $stats = $importer->get_stats();
    echo "=== PARSING STATISTICS ===\n";
    echo "Individuals: " . ($stats['individuals'] ?? 0) . "\n";
    echo "Families: " . ($stats['families'] ?? 0) . "\n";
    echo "Sources: " . ($stats['sources'] ?? 0) . "\n";
    echo "Media: " . ($stats['media'] ?? 0) . "\n";
    echo "Notes: " . ($stats['notes'] ?? 0) . "\n";
    echo "Events: " . ($stats['events'] ?? 0) . "\n";
    echo "Repositories: " . ($stats['repositories'] ?? 0) . "\n\n";

    echo "=== DATABASE VERIFICATION ===\n";

    // Verify each table
    $verification_tables = [
      'hp_people' => 'people',
      'hp_families' => 'families',
      'hp_sources' => 'sources',
      'hp_media' => 'media',
      'hp_xnotes' => 'notes',
      'hp_events' => 'events',
      'hp_repositories' => 'repositories'
    ];

    foreach ($verification_tables as $table => $label) {
      $full_table = $wpdb->prefix . $table;
      $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table WHERE gedcom = 'lyle'");
      echo "$label in database: $count\n";
    }

    echo "\n=== SAMPLE DATA FROM REAL GEDCOM ===\n";

    // Check a few sample individuals
    echo "Sample individuals:\n";
    $people = $wpdb->get_results("SELECT personID, lastname, firstname, birthdate, birthplace, sex FROM {$wpdb->prefix}hp_people WHERE gedcom = 'lyle' ORDER BY personID LIMIT 10");
    foreach ($people as $person) {
      echo "- {$person->firstname} {$person->lastname} ({$person->sex}) - ID: {$person->personID}\n";
      echo "  Born: {$person->birthdate} in {$person->birthplace}\n";
    }

    // Check a few sample families
    echo "\nSample families:\n";
    $families = $wpdb->get_results("SELECT familyID, husband, wife, marrdate, marrplace FROM {$wpdb->prefix}hp_families WHERE gedcom = 'lyle' ORDER BY familyID LIMIT 5");
    foreach ($families as $family) {
      echo "- Family {$family->familyID}: Husband={$family->husband}, Wife={$family->wife}\n";
      echo "  Married: {$family->marrdate} in {$family->marrplace}\n";
    }

    // Check sources
    echo "\nSample sources:\n";
    $sources = $wpdb->get_results("SELECT sourceID, title, author, pubinfo FROM {$wpdb->prefix}hp_sources WHERE gedcom = 'lyle' ORDER BY sourceID LIMIT 5");
    foreach ($sources as $source) {
      echo "- {$source->title}\n";
      echo "  Author: {$source->author}\n";
      echo "  Published: {$source->pubinfo}\n";
    }

    // Detailed relationship verification
    echo "\n=== RELATIONSHIP VERIFICATION ===\n";
    echo "Checking family relationships...\n";

    $family_relationships = $wpdb->get_results("
            SELECT f.familyID, f.husband, f.wife,
                   h.firstname as husband_name, h.lastname as husband_surname,
                   w.firstname as wife_name, w.lastname as wife_surname
            FROM {$wpdb->prefix}hp_families f
            LEFT JOIN {$wpdb->prefix}hp_people h ON f.husband = h.personID
            LEFT JOIN {$wpdb->prefix}hp_people w ON f.wife = w.personID
            WHERE f.gedcom = 'lyle'
            ORDER BY f.familyID
            LIMIT 10
        ");

    foreach ($family_relationships as $rel) {
      echo "Family {$rel->familyID}:\n";
      echo "  Husband: {$rel->husband_name} {$rel->husband_surname} (ID: {$rel->husband})\n";
      echo "  Wife: {$rel->wife_name} {$rel->wife_surname} (ID: {$rel->wife})\n";
    }

    // Check data quality
    echo "\n=== DATA QUALITY CHECK ===\n";

    $missing_names = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_people WHERE gedcom = 'lyle' AND (firstname IS NULL OR firstname = '')");
    echo "People missing first names: $missing_names\n";

    $missing_sex = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_people WHERE gedcom = 'lyle' AND (sex IS NULL OR sex = '')");
    echo "People missing sex: $missing_sex\n";

    $with_birth_dates = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_people WHERE gedcom = 'lyle' AND birthdate IS NOT NULL AND birthdate != ''");
    echo "People with birth dates: $with_birth_dates\n";
  } else {
    echo "ERROR: Import failed!\n";
    echo "Error: " . ($result['message'] ?? 'Unknown error') . "\n";

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
}

echo "\n=== REAL-WORLD GEDCOM TEST COMPLETED ===\n";
echo "This test validates that the system can handle complex, real-world GEDCOM files.\n";
