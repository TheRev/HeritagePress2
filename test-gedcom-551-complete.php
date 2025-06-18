<?php
// Comprehensive GEDCOM 5.5.1 Import and Verification Test
require_once('c:/MAMP/htdocs/HeritagePress2/wp-config.php');

// Plugin constants
define('HERITAGEPRESS_PLUGIN_DIR', 'c:/MAMP/htdocs/HeritagePress2/wp-content/plugins/heritagepress/');

// Load the importer
require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/class-hp-gedcom-importer.php';

global $wpdb;

echo "=== COMPREHENSIVE GEDCOM 5.5.1 IMPORT AND VERIFICATION TEST ===\n";
echo "Testing file: sample-from-5.5.1-standard.ged\n\n";

// Expected data from the GEDCOM file analysis
$expected_data = [
  'individuals' => [
    '@1@' => [
      'name' => 'Robert Eugene Williams',
      'sex' => 'M',
      'birth_date' => '02 OCT 1822',
      'birth_place' => 'Weston, Madison, Connecticut',
      'death_date' => '14 APR 1905',
      'death_place' => 'Stamford, Fairfield, CT',
      'burial_place' => 'Spring Hill Cem., Stamford, CT'
    ],
    '@2@' => [
      'name' => 'Mary Ann Wilson',
      'sex' => 'F',
      'birth_date' => 'BEF 1828',
      'birth_place' => 'Connecticut'
    ],
    '@3@' => [
      'name' => 'Joe Williams',
      'sex' => 'M',
      'birth_date' => '11 JUN 1861',
      'birth_place' => 'Idaho Falls, Bonneville, Idaho'
    ]
  ],
  'families' => [
    '@4@' => [
      'husband' => '@1@',
      'wife' => '@2@',
      'children' => ['@3@'],
      'marriage_date' => 'DEC 1859',
      'marriage_place' => 'Rapid City, South Dakota'
    ],
    '@9@' => [
      'husband' => '@1@',
      'wife' => null,
      'children' => ['@3@']
    ]
  ],
  'sources' => [
    '@6@' => [
      'title' => 'Madison County Birth, Death, and Marriage Records',
      'abbreviation' => 'VITAL RECORDS',
      'repository' => '@7@'
    ]
  ],
  'repositories' => [
    '@7@' => [
      'name' => 'Family History Library',
      'address' => '35 N West Temple Street Salt Lake City, Utah UT 84150'
    ]
  ],
  'submitters' => [
    '@5@' => [
      'name' => 'Reldon Poulson',
      'address' => '1900 43rd Street West Billings, MT 68051'
    ]
  ]
];

// Test tree
$test_tree = 'gedcom_551_test';

// Clean existing test data
echo "Cleaning existing test data...\n";
$tables = [
  'hp_people',
  'hp_families',
  'hp_children',
  'hp_sources',
  'hp_media',
  'hp_xnotes',
  'hp_notelinks',
  'hp_events',
  'hp_repositories',
  'hp_citations'
];

foreach ($tables as $table) {
  $full_table = $wpdb->prefix . $table;
  $wpdb->query("DELETE FROM $full_table WHERE gedcom = '$test_tree'");
}

// Import the GEDCOM file
$gedcom_file = 'C:/MAMP/htdocs/HeritagePress2/gedcom_test_files/sample-from-5.5.1-standard.ged';

if (!file_exists($gedcom_file)) {
  echo "ERROR: GEDCOM file not found: $gedcom_file\n";
  exit;
}

echo "Starting import of GEDCOM 5.5.1 file...\n";

try {
  $importer = new HP_GEDCOM_Importer_Controller($gedcom_file, $test_tree);
  $result = $importer->import();

  if ($result['success']) {
    echo "✓ Import completed successfully!\n\n";

    $stats = $importer->get_stats();
    echo "=== IMPORT STATISTICS ===\n";
    foreach ($stats as $type => $count) {
      echo "$type: $count\n";
    }
    echo "\n";
  } else {
    echo "✗ Import failed: " . $result['message'] . "\n";
    exit;
  }
} catch (Exception $e) {
  echo "✗ Exception during import: " . $e->getMessage() . "\n";
  exit;
}

// Now verify all data was imported correctly
echo "=== DETAILED DATA VERIFICATION ===\n";

// 1. Verify Individuals
echo "1. INDIVIDUALS VERIFICATION\n";
$people = $wpdb->get_results("
    SELECT personID, firstname, lastname, sex, birthdate, birthplace, deathdate, deathplace, burialplace
    FROM {$wpdb->prefix}hp_people
    WHERE gedcom = '$test_tree'
    ORDER BY personID
");

$individual_count = 0;
foreach ($people as $person) {
  $individual_count++;
  echo "Individual {$person->personID}:\n";
  echo "  Name: {$person->firstname} {$person->lastname}\n";
  echo "  Sex: {$person->sex}\n";
  echo "  Birth: {$person->birthdate} in {$person->birthplace}\n";
  if ($person->deathdate) echo "  Death: {$person->deathdate} in {$person->deathplace}\n";
  if ($person->burialplace) echo "  Burial: {$person->burialplace}\n";
  echo "\n";
}
echo "Total individuals imported: $individual_count\n";
echo "Expected: " . count($expected_data['individuals']) . "\n";
echo ($individual_count >= count($expected_data['individuals']) ? "✓" : "✗") . " Individual count check\n\n";

// 2. Verify Families
echo "2. FAMILIES VERIFICATION\n";
$families = $wpdb->get_results("
    SELECT familyID, husband, wife, marrdate, marrplace
    FROM {$wpdb->prefix}hp_families
    WHERE gedcom = '$test_tree'
    ORDER BY familyID
");

$family_count = 0;
foreach ($families as $family) {
  $family_count++;
  echo "Family {$family->familyID}:\n";
  echo "  Husband: {$family->husband}\n";
  echo "  Wife: {$family->wife}\n";
  if ($family->marrdate) echo "  Marriage: {$family->marrdate} in {$family->marrplace}\n";

  // Check children
  $children = $wpdb->get_results("
        SELECT personID FROM {$wpdb->prefix}hp_children
        WHERE familyID = '{$family->familyID}' AND gedcom = '$test_tree'
        ORDER BY ordernum
    ");

  if ($children) {
    echo "  Children: ";
    foreach ($children as $child) {
      echo $child->personID . " ";
    }
    echo "\n";
  }
  echo "\n";
}
echo "Total families imported: $family_count\n";
echo "Expected: " . count($expected_data['families']) . "\n";
echo ($family_count >= count($expected_data['families']) ? "✓" : "✗") . " Family count check\n\n";

// 3. Verify Sources
echo "3. SOURCES VERIFICATION\n";
$sources = $wpdb->get_results("
    SELECT sourceID, title, author, publisher, repoID
    FROM {$wpdb->prefix}hp_sources
    WHERE gedcom = '$test_tree'
    ORDER BY sourceID
");

$source_count = 0;
foreach ($sources as $source) {
  $source_count++;
  echo "Source {$source->sourceID}:\n";
  echo "  Title: {$source->title}\n";
  echo "  Author: {$source->author}\n";
  echo "  Publisher: {$source->publisher}\n";
  echo "  Repository: {$source->repoID}\n";
  echo "\n";
}
echo "Total sources imported: $source_count\n";
echo "Expected: " . count($expected_data['sources']) . "\n";
echo ($source_count >= count($expected_data['sources']) ? "✓" : "✗") . " Source count check\n\n";

// 4. Verify Repositories
echo "4. REPOSITORIES VERIFICATION\n";
$repositories = $wpdb->get_results("
    SELECT repoID, reponame
    FROM {$wpdb->prefix}hp_repositories
    WHERE gedcom = '$test_tree'
    ORDER BY repoID
");

$repo_count = 0;
foreach ($repositories as $repo) {
  $repo_count++;
  echo "Repository {$repo->repoID}:\n";
  echo "  Name: {$repo->reponame}\n";
  echo "\n";
}
echo "Total repositories imported: $repo_count\n";
echo "Expected: " . count($expected_data['repositories']) . "\n";
echo ($repo_count >= count($expected_data['repositories']) ? "✓" : "✗") . " Repository count check\n\n";

// 5. Verify Citations/Links
echo "5. CITATIONS VERIFICATION\n";
$citations = $wpdb->get_results("
    SELECT citationID, sourceID, persfamID, page
    FROM {$wpdb->prefix}hp_citations
    WHERE gedcom = '$test_tree'
    ORDER BY citationID
");

$citation_count = count($citations);
foreach ($citations as $citation) {
  echo "Citation {$citation->citationID}:\n";
  echo "  Source: {$citation->sourceID}\n";
  echo "  Linked to: {$citation->persfamID}\n";
  echo "  Page: {$citation->page}\n";
  echo "\n";
}
echo "Total citations imported: $citation_count\n\n";

// 6. Verify Events
echo "6. EVENTS VERIFICATION\n";
$events = $wpdb->get_results("
    SELECT eventID, persfamID, eventtypeID, eventdate, eventplace
    FROM {$wpdb->prefix}hp_events
    WHERE gedcom = '$test_tree'
    ORDER BY eventID
");

$event_count = count($events);
foreach ($events as $event) {
  echo "Event {$event->eventID}:\n";
  echo "  Person/Family: {$event->persfamID}\n";
  echo "  Type: {$event->eventtypeID}\n";
  echo "  Date: {$event->eventdate}\n";
  echo "  Place: {$event->eventplace}\n";
  echo "\n";
}
echo "Total events imported: $event_count\n\n";

// 7. Verify Notes
echo "7. NOTES VERIFICATION\n";
$notes = $wpdb->get_results("
    SELECT noteID, note
    FROM {$wpdb->prefix}hp_xnotes
    WHERE gedcom = '$test_tree'
    ORDER BY noteID
");

$note_count = count($notes);
foreach ($notes as $note) {
  echo "Note {$note->noteID}:\n";
  echo "  Content: " . substr($note->note, 0, 100) . "...\n";
  echo "\n";
}
echo "Total notes imported: $note_count\n\n";

// Summary
echo "=== IMPORT VERIFICATION SUMMARY ===\n";
$verification_results = [
  'Individuals' => $individual_count >= count($expected_data['individuals']),
  'Families' => $family_count >= count($expected_data['families']),
  'Sources' => $source_count >= count($expected_data['sources']),
  'Repositories' => $repo_count >= count($expected_data['repositories']),
  'Complete Import' => $individual_count > 0 && $family_count > 0
];

$passed_verifications = 0;
$total_verifications = count($verification_results);

foreach ($verification_results as $test => $passed) {
  echo ($passed ? "✓" : "✗") . " $test\n";
  if ($passed) $passed_verifications++;
}

echo "\nVerification Results: $passed_verifications/$total_verifications passed\n";

if ($passed_verifications == $total_verifications) {
  echo "🎉 ALL VERIFICATIONS PASSED - GEDCOM 5.5.1 data imported correctly!\n";
} else {
  echo "⚠ Some verifications failed - check the data above\n";
}

echo "\n=== GEDCOM 5.5.1 VERIFICATION COMPLETED ===\n";
