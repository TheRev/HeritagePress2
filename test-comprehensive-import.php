<?php
require_once 'heritagepress.php';

echo "=== Testing Enhanced Parser with Comprehensive Test File ===\n";

$gedcom_file = 'comprehensive_test.ged';

echo "File: $gedcom_file\n";
if (!file_exists($gedcom_file)) {
  echo "ERROR: File not found!\n";
  exit(1);
}

echo "File size: " . filesize($gedcom_file) . " bytes\n";
echo "Starting enhanced import...\n";

$import_options = array(
  'import_mode' => 'overwrite',
  'tree_id' => 'test_tree'
);

$parser = new HP_Enhanced_GEDCOM_Parser($gedcom_file, 'test_tree', $import_options);
$result = $parser->parse();

if ($result) {
  echo "SUCCESS: Enhanced GEDCOM import completed!\n";

  $stats = $parser->get_stats();
  echo "\n=== PARSING STATISTICS ===\n";
  echo "Individuals: " . $stats['individuals'] . "\n";
  echo "Families: " . $stats['families'] . "\n";
  echo "Sources: " . $stats['sources'] . "\n";
  echo "Media: " . $stats['media'] . "\n";
  echo "Notes: " . $stats['notes'] . "\n";
  echo "Events: " . $stats['events'] . "\n";

  // Database verification
  global $wpdb;
  echo "\n=== DATABASE VERIFICATION ===\n";

  $individuals = $wpdb->get_results("SELECT personID, firstname, lastname, sex, birthdate, birthplace FROM {$wpdb->prefix}hp_people ORDER BY personID");
  echo "Individuals in database: " . count($individuals) . "\n";
  echo "Individuals details:\n";
  foreach ($individuals as $person) {
    echo "- {$person->personID}: {$person->firstname} {$person->lastname} ({$person->sex}) b. {$person->birthdate} in {$person->birthplace}\n";
  }

  $families = $wpdb->get_results("SELECT familyID, husband, wife, marrdate, marrplace FROM {$wpdb->prefix}hp_families ORDER BY familyID");
  echo "\nFamilies in database: " . count($families) . "\n";
  echo "Family details:\n";
  foreach ($families as $family) {
    echo "- {$family->familyID}: Husband={$family->husband}, Wife={$family->wife} m. {$family->marrdate} in {$family->marrplace}\n";
  }

  $sources = $wpdb->get_results("SELECT sourceID, title, author, publisher FROM {$wpdb->prefix}hp_sources ORDER BY sourceID");
  echo "\nSources in database: " . count($sources) . "\n";
  echo "Sources details:\n";
  foreach ($sources as $source) {
    echo "- {$source->sourceID}: {$source->title} by {$source->author} ({$source->publisher})\n";
  }

  echo "\n=== TEST COMPLETE ===\n";
} else {
  echo "FAILED: Enhanced GEDCOM import failed!\n";
  exit(1);
}
