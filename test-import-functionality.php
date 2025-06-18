<?php

/**
 * Test GEDCOM Import Functionality
 *
 * This script tests the GEDCOM import functionality directly
 */

// WordPress setup for command line
define('WP_USE_THEMES', false);
require_once('c:/MAMP/htdocs/HeritagePress2/wp-config.php');

// Load WordPress
global $wpdb;

echo "<h2>GEDCOM Import Test</h2>\n";

// Load HeritagePress
require_once __DIR__ . '/heritagepress.php';

// Test file path
$test_file = "c:/MAMP/htdocs/HeritagePress2/wp-content/uploads/heritagepress/gedcom/test.ged";

echo "<h3>Testing File: $test_file</h3>\n";

if (!file_exists($test_file)) {
  echo "❌ Test file does not exist!\n";
  exit(1);
}

echo "✅ Test file exists\n";
echo "File size: " . filesize($test_file) . " bytes\n";

// Load the GEDCOM importer
require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/class-hp-gedcom-importer.php';

try {
  echo "<h3>Creating Importer Instance</h3>\n";
  $importer = new HP_GEDCOM_Importer_Controller($test_file, 'test_tree');
  echo "✅ Importer created successfully\n";

  echo "<h3>Running Import</h3>\n";
  $result = $importer->import();

  echo "<h4>Import Result:</h4>\n";
  if (is_array($result)) {
    echo "<pre>" . print_r($result, true) . "</pre>\n";
  } else {
    echo "Result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
  }

  echo "<h4>Import Statistics:</h4>\n";
  $stats = $importer->get_stats();
  echo "<pre>" . print_r($stats, true) . "</pre>\n";

  echo "<h4>Warnings:</h4>\n";
  $warnings = $importer->get_warnings();
  if (!empty($warnings)) {
    foreach ($warnings as $warning) {
      echo "⚠️ " . $warning . "\n";
    }
  } else {
    echo "No warnings\n";
  }

  echo "<h4>Errors:</h4>\n";
  $errors = $importer->get_errors();
  if (!empty($errors)) {
    foreach ($errors as $error) {
      echo "❌ " . $error . "\n";
    }
  } else {
    echo "No errors\n";
  }
} catch (Exception $e) {
  echo "❌ Exception: " . $e->getMessage() . "\n";
  echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n<h3>Test Complete</h3>\n";
