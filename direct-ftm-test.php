<?php

/**
 * Direct test of FTM GEDCOM with Enhanced Parser
 */

echo "=== Direct FTM GEDCOM Parser Test ===\n";

// Load required files
require_once 'includes/gedcom/class-hp-enhanced-gedcom-parser.php';

$gedcom_file = '../../../gedcom_test_files/FTM_lyle_2025-06-17.ged';

if (!file_exists($gedcom_file)) {
  echo "ERROR: File not found: $gedcom_file\n";
  exit(1);
}

echo "Testing file: $gedcom_file\n";
echo "File size: " . filesize($gedcom_file) . " bytes\n";

try {
  // Create parser instance
  $parser = new HP_Enhanced_GEDCOM_Parser($gedcom_file, 'ftm_test');

  echo "Parser created successfully\n";

  // Get header info directly
  $header_info = $parser->get_header_info();

  echo "\n=== GEDCOM HEADER INFORMATION ===\n";
  if ($header_info) {
    foreach ($header_info as $key => $value) {
      if (!empty($value)) {
        echo sprintf("%-15s: %s\n", ucwords(str_replace('_', ' ', $key)), $value);
      }
    }
  } else {
    echo "No header information found\n";
  }

  // Parse first few lines to see structure
  echo "\n=== FIRST 20 LINES OF FILE ===\n";
  $lines = file($gedcom_file);
  for ($i = 0; $i < min(20, count($lines)); $i++) {
    echo sprintf("%3d: %s", $i + 1, rtrim($lines[$i]) . "\n");
  }
} catch (Exception $e) {
  echo "ERROR: " . $e->getMessage() . "\n";
  echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nTest complete.\n";
