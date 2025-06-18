<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../../wp-config.php';
require_once 'includes/gedcom/class-hp-enhanced-gedcom-parser.php';

global $wpdb;

echo "FTM Import Test with BOM Handling\n";
echo "================================\n\n";

$gedcom_file = 'C:\MAMP\htdocs\HeritagePress2\gedcom_test_files\FTM_lyle_2025-06-17.ged';
$tree_id = 'ftm_bom_test';

// Clean test data
echo "Cleaning test data...\n";
$wpdb->query("DELETE FROM {$wpdb->prefix}hp_people WHERE gedcom = '$tree_id'");
$wpdb->query("DELETE FROM {$wpdb->prefix}hp_families WHERE gedcom = '$tree_id'");
$wpdb->query("DELETE FROM {$wpdb->prefix}hp_sources WHERE gedcom = '$tree_id'");
$wpdb->query("DELETE FROM {$wpdb->prefix}hp_repositories WHERE gedcom = '$tree_id'");
$wpdb->query("DELETE FROM {$wpdb->prefix}hp_xnotes WHERE gedcom = '$tree_id'");
$wpdb->query("DELETE FROM {$wpdb->prefix}hp_media WHERE gedcom = '$tree_id'");

// Expected counts from analysis
echo "Expected from GEDCOM file:\n";
echo "- Individuals: 1\n";
echo "- Families: 1\n";
echo "- Sources: 14\n";
echo "- Repositories: 3\n";
echo "- Media: 27\n\n";

// Create a cleaned version without BOM
$content = file_get_contents($gedcom_file);

// Remove BOM if present
if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
  $content = substr($content, 3);
  echo "✓ Removed UTF-8 BOM from file\n";
}

$clean_file = sys_get_temp_dir() . '/ftm_clean.ged';
file_put_contents($clean_file, $content);
echo "✓ Created clean temporary file: $clean_file\n\n";

// Test import with clean file
echo "Starting import...\n";

try {
  $parser = new HP_Enhanced_GEDCOM_Parser($clean_file, $tree_id);
  $result = $parser->parse();

  if ($result) {
    echo "✓ Import completed\n\n";

    // Check what was imported
    $imported_people = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_people WHERE gedcom = '$tree_id'");
    $imported_families = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_families WHERE gedcom = '$tree_id'");
    $imported_sources = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_sources WHERE gedcom = '$tree_id'");
    $imported_repos = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_repositories WHERE gedcom = '$tree_id'");
    $imported_media = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_media WHERE gedcom = '$tree_id'");

    echo "Import Results:\n";
    echo "- People: $imported_people (expected: 1)\n";
    echo "- Families: $imported_families (expected: 1)\n";
    echo "- Sources: $imported_sources (expected: 14)\n";
    echo "- Repositories: $imported_repos (expected: 3)\n";
    echo "- Media: $imported_media (expected: 27)\n\n";

    // Calculate success rate
    $success_rate = 0;
    $total_expected = 46; // 1+1+14+3+27
    $total_imported = $imported_people + $imported_families + $imported_sources + $imported_repos + $imported_media;

    if ($total_expected > 0) {
      $success_rate = ($total_imported / $total_expected) * 100;
    }

    echo "Overall Success Rate: " . number_format($success_rate, 1) . "% ($total_imported / $total_expected)\n\n";

    // Show parser stats
    $stats = $parser->get_stats();
    echo "Parser Statistics:\n";
    foreach ($stats as $key => $value) {
      if ($key !== 'header_info') {
        echo "- " . ucfirst($key) . ": $value\n";
      }
    }

    // Show any issues
    $warnings = $parser->get_warnings();
    $errors = $parser->get_errors();

    if (!empty($warnings)) {
      echo "\n⚠ Warnings:\n";
      foreach ($warnings as $warning) {
        echo "  - $warning\n";
      }
    }

    if (!empty($errors)) {
      echo "\n✗ Errors:\n";
      foreach ($errors as $error) {
        echo "  - $error\n";
      }
    }

    if (empty($errors) && $success_rate > 90) {
      echo "\n✅ IMPORT SUCCESSFUL!\n";
    } else if ($total_imported > 0) {
      echo "\n⚠ PARTIAL IMPORT - Some data imported but with issues\n";
    } else {
      echo "\n✗ IMPORT FAILED - No data was imported\n";
    }
  } else {
    echo "✗ Import failed\n";
    $errors = $parser->get_errors();
    if (!empty($errors)) {
      foreach ($errors as $error) {
        echo "Error: $error\n";
      }
    }
  }
} catch (Exception $e) {
  echo "✗ Exception: " . $e->getMessage() . "\n";
  echo "  File: " . $e->getFile() . "\n";
  echo "  Line: " . $e->getLine() . "\n";
}

// Clean up temp file
if (file_exists($clean_file)) {
  unlink($clean_file);
  echo "\n✓ Cleaned up temporary file\n";
}

echo "\nTest completed.\n";
