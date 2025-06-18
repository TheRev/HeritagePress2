<?php

/**
 * Test Classic GEDCOM Parser
 */

// WordPress environment
require_once('c:/MAMP/htdocs/HeritagePress2/wp-config.php');

// Plugin constants
define('HERITAGEPRESS_PLUGIN_DIR', 'c:/MAMP/htdocs/HeritagePress2/wp-content/plugins/heritagepress/');

// Load the importer
require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/class-hp-gedcom-importer.php';

echo "Testing Classic GEDCOM Import...\n\n";

// Test file path
$test_file = HERITAGEPRESS_PLUGIN_DIR . 'test_simple.ged';

try {
  // Create importer instance
  $importer = new HP_GEDCOM_Importer_Controller($test_file, 'main');

  echo "Starting import of: $test_file\n";

  // Run the import
  $result = $importer->import();

  // Display results
  if ($result['success']) {
    echo "SUCCESS: GEDCOM import completed!\n\n";

    $stats = $importer->get_stats();
    echo "Statistics:\n";
    echo "- Individuals: " . ($stats['individuals'] ?? 0) . "\n";
    echo "- Families: " . ($stats['families'] ?? 0) . "\n";
    echo "- Sources: " . ($stats['sources'] ?? 0) . "\n";
    echo "- Media: " . ($stats['media'] ?? 0) . "\n";
    echo "- Notes: " . ($stats['notes'] ?? 0) . "\n";
    echo "- Events: " . ($stats['events'] ?? 0) . "\n";

    $warnings = $importer->get_warnings();
    if (!empty($warnings)) {
      echo "\nWarnings:\n";
      foreach ($warnings as $warning) {
        echo "- $warning\n";
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
}

echo "\nDone.\n";
