<?php
require_once '../../../wp-config.php';
require_once 'includes/gedcom/class-hp-enhanced-gedcom-parser.php';

global $wpdb;

echo "FTM Lyle GEDCOM Import Test\n";
echo "==========================\n\n";

$gedcom_file = "C:/MAMP/htdocs/HeritagePress2/gedcom_test_files/FTM_lyle_2025-06-17.ged";
$tree_id = "ftm_lyle_debug";

echo "Testing file: $gedcom_file\n";
if (!file_exists($gedcom_file)) {
  echo "ERROR: File not found!\n";
  exit(1);
}

echo "File size: " . filesize($gedcom_file) . " bytes\n";
echo "Total lines: " . count(file($gedcom_file)) . "\n\n";

// Clean test data
echo "Cleaning test data...\n";
$wpdb->query("DELETE FROM {$wpdb->prefix}hp_people WHERE gedcom = '$tree_id'");
$wpdb->query("DELETE FROM {$wpdb->prefix}hp_families WHERE gedcom = '$tree_id'");
$wpdb->query("DELETE FROM {$wpdb->prefix}hp_sources WHERE gedcom = '$tree_id'");
$wpdb->query("DELETE FROM {$wpdb->prefix}hp_repositories WHERE gedcom = '$tree_id'");
$wpdb->query("DELETE FROM {$wpdb->prefix}hp_xnotes WHERE gedcom = '$tree_id'");
echo "✓ Cleaned\n\n";

try {
  $parser = new HP_Enhanced_GEDCOM_Parser($gedcom_file, $tree_id);
  echo "Parser initialized successfully\n";

  $result = $parser->parse();

  if ($result) {
    echo "✓ Parsing completed\n\n";

    // Check results
    $people = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_people WHERE gedcom = '$tree_id'");
    $families = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_families WHERE gedcom = '$tree_id'");
    $sources = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_sources WHERE gedcom = '$tree_id'");
    $repos = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_repositories WHERE gedcom = '$tree_id'");
    $notes = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_xnotes WHERE gedcom = '$tree_id'");

    echo "Import Results:\n";
    echo "- People: $people\n";
    echo "- Families: $families\n";
    echo "- Sources: $sources\n";
    echo "- Repositories: $repos\n";
    echo "- Notes: $notes\n\n";

    // Get parser stats
    $stats = $parser->get_stats();
    echo "Parser Stats:\n";
    foreach ($stats as $key => $value) {
      echo "- $key: $value\n";
    }

    // Show errors/warnings
    $errors = $parser->get_errors();
    $warnings = $parser->get_warnings();

    if (!empty($errors)) {
      echo "\nErrors (" . count($errors) . "):\n";
      foreach (array_slice($errors, 0, 5) as $error) {
        echo "- $error\n";
      }
    }

    if (!empty($warnings)) {
      echo "\nWarnings (" . count($warnings) . "):\n";
      foreach (array_slice($warnings, 0, 5) as $warning) {
        echo "- $warning\n";
      }
    }
  } else {
    echo "✗ Parsing failed\n";
    $errors = $parser->get_errors();
    foreach ($errors as $error) {
      echo "Error: $error\n";
    }
  }
} catch (Exception $e) {
  echo "Exception: " . $e->getMessage() . "\n";
}

echo "\nTest completed.\n";
