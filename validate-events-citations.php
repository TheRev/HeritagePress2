<?php

/**
 * Simple validation test for events and citations
 */

// WordPress setup
if (!defined('ABSPATH')) {
  define('ABSPATH', dirname(__FILE__) . '/../../../');
}

require_once(ABSPATH . 'wp-config.php');
require_once(dirname(__FILE__) . '/includes/gedcom/class-hp-enhanced-gedcom-parser.php');

// Test GEDCOM file
$test_gedcom_file = 'c:\\MAMP\\htdocs\\HeritagePress2\\gedcom_test_files\\sample-from-5.5.1-standard.ged';
$tree_id = 'test_validation';

echo "=== EVENTS AND CITATIONS VALIDATION TEST ===\n";
echo "GEDCOM file: $test_gedcom_file\n";
echo "Tree ID: $tree_id\n\n";

// Clean database
global $wpdb;
$tables = array(
  $wpdb->prefix . 'hp_people',
  $wpdb->prefix . 'hp_families',
  $wpdb->prefix . 'hp_children',
  $wpdb->prefix . 'hp_sources',
  $wpdb->prefix . 'hp_repositories',
  $wpdb->prefix . 'hp_media',
  $wpdb->prefix . 'hp_events',
  $wpdb->prefix . 'hp_citations',
  $wpdb->prefix . 'hp_xnotes'
);

foreach ($tables as $table) {
  $wpdb->delete($table, array('gedcom' => $tree_id));
}

// Run import
try {
  $parser = new HP_Enhanced_GEDCOM_Parser($test_gedcom_file, $tree_id, array(
    'del' => 'match',
    'allevents' => '1'
  ));
  $parser->parse();
  $stats = $parser->get_stats();

  echo "Import completed successfully!\n";
  echo "Statistics:\n";
  foreach ($stats as $key => $value) {
    echo "  $key: $value\n";
  }
  echo "\n";

  // Check database counts
  echo "Database record counts:\n";
  foreach ($tables as $table) {
    $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE gedcom = %s", $tree_id));
    $table_name = str_replace($wpdb->prefix . 'hp_', '', $table);
    echo "  $table_name: $count\n";
  }
  echo "\n";

  // Check events in detail
  $events = $wpdb->get_results($wpdb->prepare(
    "SELECT persfamID, eventtypeID, eventdate, eventplace, parenttag FROM {$wpdb->prefix}hp_events WHERE gedcom = %s ORDER BY persfamID, eventtypeID",
    $tree_id
  ));

  echo "Events detail (" . count($events) . " total):\n";
  foreach ($events as $event) {
    echo "  {$event->persfamID}: Type {$event->eventtypeID} ({$event->parenttag}) on '{$event->eventdate}' at '{$event->eventplace}'\n";
  }
  echo "\n";

  // Check citations in detail
  $citations = $wpdb->get_results($wpdb->prepare(
    "SELECT persfamID, eventID, sourceID, page, citetext FROM {$wpdb->prefix}hp_citations WHERE gedcom = %s",
    $tree_id
  ));

  echo "Citations detail (" . count($citations) . " total):\n";
  foreach ($citations as $citation) {
    echo "  {$citation->persfamID} (Event {$citation->eventID}) -> Source {$citation->sourceID}: Page '{$citation->page}'\n";
    if ($citation->citetext) {
      echo "    Text: '{$citation->citetext}'\n";
    }
  }
  echo "\n";

  // Validation summary
  $expected_individuals = 3;
  $expected_families = 2;
  $expected_sources = 1;
  $expected_events = 7; // Based on our earlier tests
  $expected_citations = 1;

  $actual_individuals = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}hp_people WHERE gedcom = %s", $tree_id));
  $actual_families = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}hp_families WHERE gedcom = %s", $tree_id));
  $actual_sources = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}hp_sources WHERE gedcom = %s", $tree_id));
  $actual_events = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}hp_events WHERE gedcom = %s", $tree_id));
  $actual_citations = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}hp_citations WHERE gedcom = %s", $tree_id));

  echo "=== VALIDATION RESULTS ===\n";
  echo "Individuals: $actual_individuals / $expected_individuals " . ($actual_individuals >= $expected_individuals ? "✓" : "✗") . "\n";
  echo "Families: $actual_families / $expected_families " . ($actual_families >= $expected_families ? "✓" : "✗") . "\n";
  echo "Sources: $actual_sources / $expected_sources " . ($actual_sources >= $expected_sources ? "✓" : "✗") . "\n";
  echo "Events: $actual_events / $expected_events " . ($actual_events >= $expected_events ? "✓" : "✗") . "\n";
  echo "Citations: $actual_citations / $expected_citations " . ($actual_citations >= $expected_citations ? "✓" : "✗") . "\n";

  if (
    $actual_individuals >= $expected_individuals &&
    $actual_families >= $expected_families &&
    $actual_sources >= $expected_sources &&
    $actual_events >= $expected_events &&
    $actual_citations >= $expected_citations
  ) {
    echo "\n✓ ALL VALIDATIONS PASSED! Events and citations are being saved correctly.\n";
  } else {
    echo "\n✗ Some validations failed. Check the implementation.\n";
  }
} catch (Exception $e) {
  echo "✗ Error during import: " . $e->getMessage() . "\n";
}
