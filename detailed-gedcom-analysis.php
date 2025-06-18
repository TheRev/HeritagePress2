<?php
// Detailed analysis of what should be vs what is imported from GEDCOM 5.5.1
require_once('c:/MAMP/htdocs/HeritagePress2/wp-config.php');

global $wpdb;

echo "=== DETAILED GEDCOM 5.5.1 IMPORT ANALYSIS ===\n\n";

// Clean and re-import with detailed tracking
echo "Cleaning and re-importing data...\n";
$tables = ['hp_people', 'hp_families', 'hp_sources', 'hp_repositories', 'hp_citations', 'hp_events', 'hp_xnotes', 'hp_notelinks', 'hp_children'];
foreach ($tables as $table) {
  $wpdb->query("DELETE FROM {$wpdb->prefix}$table WHERE gedcom = 'detailed_test'");
}

// Import using the GEDCOM importer
require_once('includes/gedcom/class-hp-gedcom-importer.php');

$gedcom_file = 'c:/MAMP/htdocs/HeritagePress2/gedcom_test_files/sample-from-5.5.1-standard.ged';
$importer = new HP_GEDCOM_Importer_Controller($gedcom_file, 'detailed_test');
$result = $importer->import();

if ($result['success']) {
  echo "✓ Import completed\n\n";

  echo "=== EXPECTED vs ACTUAL ANALYSIS ===\n";

  // What should be in the GEDCOM based on manual analysis:
  $expected = [
    'individuals' => 3, // @1@, @2@, @3@
    'families' => 2,    // @4@, @9@
    'sources' => 1,     // @6@
    'repositories' => 1, // @7@
    'events' => [
      'birth_events' => 3,      // Each individual has BIRT
      'death_events' => 1,      // @1@ has DEAT
      'burial_events' => 1,     // @1@ has BURI
      'residence_events' => 1,  // @1@ has RESI
      'marriage_events' => 1,   // @4@ has MARR
      'adoption_events' => 1,   // @3@ has ADOP
      'sealing_events' => 2,    // @3@ has SLGC, @4@ has SLGS
    ],
    'citations' => 1,   // @1@ BIRT has SOUR @6@
    'children' => 2,    // @3@ is child of both @4@ and @9@
  ];

  // Check actual imported data
  $actual = [];

  // Count records in each table
  foreach ($tables as $table) {
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}$table WHERE gedcom = 'detailed_test'");
    $actual[$table] = $count;
    echo "$table: $count records\n";
  }

  echo "\n=== DETAILED VERIFICATION ===\n";

  // Check individuals
  echo "1. INDIVIDUALS:\n";
  $people = $wpdb->get_results("SELECT personID, firstname, lastname, birthdate, deathdate, birthplace FROM {$wpdb->prefix}hp_people WHERE gedcom = 'detailed_test' ORDER BY personID");
  foreach ($people as $person) {
    echo "- {$person->personID}: {$person->firstname} {$person->lastname}\n";
    echo "  Birth: {$person->birthdate} in {$person->birthplace}\n";
    if ($person->deathdate) {
      echo "  Death: {$person->deathdate}\n";
    }
  }

  // Check families and children
  echo "\n2. FAMILIES AND CHILDREN:\n";
  $families = $wpdb->get_results("SELECT familyID, husband, wife, marrdate, marrplace FROM {$wpdb->prefix}hp_families WHERE gedcom = 'detailed_test' ORDER BY familyID");
  foreach ($families as $family) {
    echo "- Family {$family->familyID}: H={$family->husband}, W={$family->wife}\n";
    if ($family->marrdate) {
      echo "  Marriage: {$family->marrdate} in {$family->marrplace}\n";
    }

    // Check children for this family
    $children = $wpdb->get_results($wpdb->prepare(
      "SELECT c.personID, p.firstname, p.lastname FROM {$wpdb->prefix}hp_children c
             JOIN {$wpdb->prefix}hp_people p ON c.personID = p.personID AND c.gedcom = p.gedcom
             WHERE c.familyID = %s AND c.gedcom = %s ORDER BY c.ordernum",
      $family->familyID,
      'detailed_test'
    ));
    foreach ($children as $child) {
      echo "    Child: {$child->personID} ({$child->firstname} {$child->lastname})\n";
    }
  }

  // Check events
  echo "\n3. EVENTS:\n";
  $events = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}hp_events WHERE gedcom = 'detailed_test'");
  if (!empty($events)) {
    foreach ($events as $event) {
      echo "- Event {$event->eventID}: {$event->persfamID} - Type {$event->eventtypeID}\n";
      echo "  Date: {$event->eventdate}, Place: {$event->eventplace}\n";
    }
  } else {
    echo "❌ No events found - this needs investigation!\n";
  }

  // Check citations
  echo "\n4. CITATIONS:\n";
  $citations = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}hp_citations WHERE gedcom = 'detailed_test'");
  if (!empty($citations)) {
    foreach ($citations as $citation) {
      echo "- Citation {$citation->citationID}: {$citation->persfamID} -> Source {$citation->sourceID}\n";
      echo "  Page: {$citation->page}\n";
    }
  } else {
    echo "❌ No citations found - this needs investigation!\n";
  }

  // Check if birth/death/burial data is in people table instead of events table
  echo "\n5. BIRTH/DEATH DATA IN PEOPLE TABLE:\n";
  $people_with_events = $wpdb->get_results("
        SELECT personID, firstname, lastname, birthdate, deathdate, burialdate, birthplace, deathplace, burialplace
        FROM {$wpdb->prefix}hp_people
        WHERE gedcom = 'detailed_test'
        AND (birthdate IS NOT NULL OR deathdate IS NOT NULL OR burialdate IS NOT NULL)
    ");

  foreach ($people_with_events as $person) {
    echo "- {$person->firstname} {$person->lastname}:\n";
    if ($person->birthdate) echo "  Birth: {$person->birthdate} in {$person->birthplace}\n";
    if ($person->deathdate) echo "  Death: {$person->deathdate} in {$person->deathplace}\n";
    if ($person->burialdate) echo "  Burial: {$person->burialdate} in {$person->burialplace}\n";
  }

  echo "\n=== ANALYSIS SUMMARY ===\n";
  echo "Expected individuals: {$expected['individuals']}, Actual: {$actual['hp_people']} " . ($expected['individuals'] == $actual['hp_people'] ? "✅" : "❌") . "\n";
  echo "Expected families: {$expected['families']}, Actual: {$actual['hp_families']} " . ($expected['families'] == $actual['hp_families'] ? "✅" : "❌") . "\n";
  echo "Expected sources: {$expected['sources']}, Actual: {$actual['hp_sources']} " . ($expected['sources'] == $actual['hp_sources'] ? "✅" : "❌") . "\n";
  echo "Expected repositories: {$expected['repositories']}, Actual: {$actual['hp_repositories']} " . ($expected['repositories'] == $actual['hp_repositories'] ? "✅" : "❌") . "\n";
  echo "Expected children records: 2, Actual: {$actual['hp_children']} " . (2 == $actual['hp_children'] ? "✅" : "❌") . "\n";
  echo "Citations: {$actual['hp_citations']} " . ($actual['hp_citations'] > 0 ? "✅" : "❌ - Should have at least 1") . "\n";
  echo "Events: {$actual['hp_events']} " . ($actual['hp_events'] > 0 ? "✅" : "⚠️ - Events might be stored in people/families tables instead") . "\n";
} else {
  echo "❌ Import failed: " . $result['message'] . "\n";
}

echo "\n=== DETAILED ANALYSIS COMPLETED ===\n";
