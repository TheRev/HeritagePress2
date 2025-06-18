<?php
require_once '../../../wp-config.php';
require_once 'includes/gedcom/class-hp-enhanced-gedcom-parser.php';

global $wpdb;

echo "Testing GEDCOM notes import...\n\n";

// Clean any existing test data
$wpdb->query("DELETE FROM {$wpdb->prefix}hp_xnotes WHERE gedcom = 'test_notes'");
$wpdb->query("DELETE FROM {$wpdb->prefix}hp_notelinks WHERE gedcom = 'test_notes'");
$wpdb->query("DELETE FROM {$wpdb->prefix}hp_people WHERE gedcom = 'test_notes'");

echo "Cleaned existing test data\n";

// Parse the test GEDCOM
$gedcom_file = __DIR__ . '/test-notes.ged';
if (!file_exists($gedcom_file)) {
  echo "✗ GEDCOM file not found\n";
  exit(1);
}

echo "Parsing GEDCOM file: $gedcom_file\n";

// Initialize parser with required parameters
$parser = new HP_Enhanced_GEDCOM_Parser($gedcom_file, 'test_notes');
$result = $parser->parse();

if ($result) {
  echo "✓ GEDCOM parsed successfully\n\n";

  // Check results
  $notes_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_xnotes WHERE gedcom = 'test_notes'");
  $people_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_people WHERE gedcom = 'test_notes'");
  $notelinks_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_notelinks WHERE gedcom = 'test_notes'");

  echo "Import results:\n";
  echo "- Notes: $notes_count\n";
  echo "- People: $people_count\n";
  echo "- Note links: $notelinks_count\n\n";

  // Show imported notes
  if ($notes_count > 0) {
    echo "Imported notes:\n";
    $notes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}hp_xnotes WHERE gedcom = 'test_notes'");
    foreach ($notes as $note) {
      echo "- Note ID: {$note->noteID}\n";
      echo "  Content: " . substr($note->note, 0, 100) . "...\n\n";
    }
  }

  // Show any warnings or errors
  $errors = $parser->get_errors();
  $warnings = $parser->get_warnings();

  if (!empty($warnings)) {
    echo "Warnings:\n";
    foreach ($warnings as $warning) {
      echo "- $warning\n";
    }
  }

  if (!empty($errors)) {
    echo "Errors:\n";
    foreach ($errors as $error) {
      echo "- $error\n";
    }
  }
} else {
  echo "✗ GEDCOM parsing failed\n";
  $errors = $parser->get_errors();
  if (!empty($errors)) {
    echo "Errors:\n";
    foreach ($errors as $error) {
      echo "- $error\n";
    }
  }
}

echo "\nTest completed.\n";
