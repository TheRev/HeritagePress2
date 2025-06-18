<?php
// Test append mode functionality - ensure no overwrites
require_once('c:/MAMP/htdocs/HeritagePress2/wp-config.php');

// Plugin constants
define('HERITAGEPRESS_PLUGIN_DIR', 'c:/MAMP/htdocs/HeritagePress2/wp-content/plugins/heritagepress/');

// Load the importer
require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/class-hp-gedcom-importer.php';

global $wpdb;

echo "=== TESTING APPEND MODE FUNCTIONALITY ===\n";
echo "Testing that append mode doesn't overwrite existing data\n\n";

// First, check current count
$initial_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_people WHERE gedcom = 'standard'");
echo "Initial count of people in 'standard' gedcom: $initial_count\n\n";

// Run the same import again to test append mode
$gedcom_file = 'c:/MAMP/htdocs/HeritagePress2/gedcom_test_files/sample-from-5.5.1-standard.ged';

try {
  echo "Running second import (append mode)...\n";

  // Create importer instance - default is append mode
  $importer = new HP_GEDCOM_Importer_Controller($gedcom_file, 'standard');

  // Run the import
  $result = $importer->import();

  if ($result['success']) {
    echo "SUCCESS: Second import completed!\n\n";

    $stats = $importer->get_stats();
    echo "=== SECOND IMPORT STATISTICS ===\n";
    echo "Individuals processed: " . ($stats['individuals'] ?? 0) . "\n";
    echo "Families processed: " . ($stats['families'] ?? 0) . "\n";
    echo "Sources processed: " . ($stats['sources'] ?? 0) . "\n\n";

    // Check final count
    $final_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_people WHERE gedcom = 'standard'");
    echo "Final count of people in 'standard' gedcom: $final_count\n";

    if ($final_count == $initial_count) {
      echo "✓ APPEND MODE WORKING CORRECTLY: No duplicates created\n";
    } else {
      echo "⚠ WARNING: Count changed from $initial_count to $final_count\n";
      echo "This may indicate duplicates were created or records were overwritten\n";
    }

    // Verify no duplicate individuals
    echo "\nChecking for duplicate individuals...\n";
    $duplicates = $wpdb->get_results("
            SELECT personID, firstname, lastname, COUNT(*) as count
            FROM {$wpdb->prefix}hp_people
            WHERE gedcom = 'standard'
            GROUP BY firstname, lastname, birthdate, birthplace
            HAVING COUNT(*) > 1
        ");

    if (empty($duplicates)) {
      echo "✓ No duplicate individuals found\n";
    } else {
      echo "⚠ Found duplicate individuals:\n";
      foreach ($duplicates as $dup) {
        echo "- {$dup->firstname} {$dup->lastname}: {$dup->count} records\n";
      }
    }

    // Check families
    $family_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_families WHERE gedcom = 'standard'");
    echo "\nFamily count after second import: $family_count\n";
  } else {
    echo "ERROR: Second import failed!\n";
    echo "Error: " . ($result['message'] ?? 'Unknown error') . "\n";
  }
} catch (Exception $e) {
  echo "EXCEPTION: " . $e->getMessage() . "\n";
}

echo "\n=== APPEND MODE TEST COMPLETED ===\n";
