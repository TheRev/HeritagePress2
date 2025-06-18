<?php

/**
 * Simple test for FTM Lyle GEDCOM with focus on header extraction
 */

echo "Starting FTM Lyle GEDCOM test...\n";

// Check if file exists
$gedcom_file = '../../../gedcom_test_files/FTM_lyle_2025-06-17.ged';
if (!file_exists($gedcom_file)) {
  echo "ERROR: GEDCOM file not found: $gedcom_file\n";
  exit(1);
}

echo "File found: $gedcom_file\n";
echo "File size: " . filesize($gedcom_file) . " bytes\n";

// Try to load WordPress
try {
  require_once 'heritagepress.php';
  echo "WordPress loaded successfully\n";
} catch (Exception $e) {
  echo "WordPress load error: " . $e->getMessage() . "\n";
}

// Try to load the importer
try {
  require_once 'includes/gedcom/class-hp-gedcom-importer.php';
  echo "Importer loaded successfully\n";
} catch (Exception $e) {
  echo "Importer load error: " . $e->getMessage() . "\n";
}

// Create importer instance
try {
  $importer = new HP_GEDCOM_Importer_Controller($gedcom_file, 'ftm_test');
  echo "Importer instance created successfully\n";

  // Run import
  $result = $importer->import();

  if ($result['success']) {
    echo "Import successful!\n";
    echo "Stats: " . print_r($result['stats'], true) . "\n";

    // Check for header info
    if (isset($result['stats']['header_info'])) {
      echo "\nHEADER INFORMATION:\n";
      foreach ($result['stats']['header_info'] as $key => $value) {
        echo "  $key: $value\n";
      }
    }
  } else {
    echo "Import failed: " . ($result['message'] ?? 'Unknown error') . "\n";
  }
} catch (Exception $e) {
  echo "Import error: " . $e->getMessage() . "\n";
}

echo "Test complete.\n";
