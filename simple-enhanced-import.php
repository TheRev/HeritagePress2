<?php

/**
 * Simple Enhanced Import with Header Display
 */

// WordPress setup
if (!defined('ABSPATH')) {
  define('ABSPATH', dirname(__FILE__) . '/../../../');
}

require_once(ABSPATH . 'wp-config.php');
require_once(dirname(__FILE__) . '/includes/gedcom/class-hp-enhanced-gedcom-parser.php');

$test_gedcom_file = 'c:\\MAMP\\htdocs\\HeritagePress2\\gedcom_test_files\\sample-from-5.5.1-standard.ged';
$tree_id = 'enhanced_import_test';

echo "🏷️  ENHANCED GEDCOM IMPORT WITH HEADER INFO\n";
echo "=============================================\n\n";

// Clean database first
global $wpdb;
$wpdb->delete($wpdb->prefix . 'hp_people', array('gedcom' => $tree_id));
$wpdb->delete($wpdb->prefix . 'hp_families', array('gedcom' => $tree_id));
$wpdb->delete($wpdb->prefix . 'hp_events', array('gedcom' => $tree_id));
$wpdb->delete($wpdb->prefix . 'hp_citations', array('gedcom' => $tree_id));
$wpdb->delete($wpdb->prefix . 'hp_sources', array('gedcom' => $tree_id));
$wpdb->delete($wpdb->prefix . 'hp_repositories', array('gedcom' => $tree_id));

echo "📁 File: " . basename($test_gedcom_file) . "\n";
echo "🗂️  Tree ID: $tree_id\n\n";

try {
  $parser = new HP_Enhanced_GEDCOM_Parser($test_gedcom_file, $tree_id, array(
    'del' => 'match',
    'allevents' => '1'
  ));

  $parser->parse();

  echo "✅ Import completed successfully!\n\n";

  // Get final counts
  $people_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}hp_people WHERE gedcom = %s", $tree_id));
  $families_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}hp_families WHERE gedcom = %s", $tree_id));
  $events_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}hp_events WHERE gedcom = %s", $tree_id));
  $citations_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}hp_citations WHERE gedcom = %s", $tree_id));
  $sources_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}hp_sources WHERE gedcom = %s", $tree_id));
  $repos_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}hp_repositories WHERE gedcom = %s", $tree_id));

  echo "📊 IMPORT RESULTS:\n";
  echo "==================\n";
  echo "👥 Individuals: $people_count\n";
  echo "👨‍👩‍👧‍👦 Families: $families_count\n";
  echo "📅 Events: $events_count\n";
  echo "📄 Citations: $citations_count\n";
  echo "📚 Sources: $sources_count\n";
  echo "🏛️  Repositories: $repos_count\n\n";

  // Validation
  $expected = array('people' => 3, 'families' => 2, 'events' => 7, 'citations' => 1, 'sources' => 1, 'repos' => 1);
  $actual = array('people' => $people_count, 'families' => $families_count, 'events' => $events_count, 'citations' => $citations_count, 'sources' => $sources_count, 'repos' => $repos_count);

  echo "✅ VALIDATION:\n";
  echo "==============\n";
  $all_good = true;
  foreach ($expected as $type => $exp) {
    $act = $actual[$type];
    $status = $act >= $exp ? "✅" : "❌";
    echo "$status " . ucfirst($type) . ": $act (expected ≥ $exp)\n";
    if ($act < $exp) $all_good = false;
  }

  if ($all_good) {
    echo "\n🎉 ALL VALIDATIONS PASSED!\n";
  } else {
    echo "\n⚠️  Some validations failed\n";
  }

  // Show some sample data
  echo "\n📋 SAMPLE DATA:\n";
  echo "===============\n";

  $sample_people = $wpdb->get_results($wpdb->prepare(
    "SELECT personID, firstname, lastname, birthdate FROM {$wpdb->prefix}hp_people WHERE gedcom = %s LIMIT 3",
    $tree_id
  ));

  foreach ($sample_people as $person) {
    echo "👤 {$person->firstname} {$person->lastname} (ID: {$person->personID})\n";
    if ($person->birthdate) {
      echo "   📅 Born: {$person->birthdate}\n";
    }
  }

  $sample_events = $wpdb->get_results($wpdb->prepare(
    "SELECT persfamID, eventtypeID, eventdate, eventplace FROM {$wpdb->prefix}hp_events WHERE gedcom = %s LIMIT 5",
    $tree_id
  ));

  echo "\n📅 EVENTS:\n";
  foreach ($sample_events as $event) {
    echo "   {$event->persfamID}: Type {$event->eventtypeID} on {$event->eventdate}\n";
    if ($event->eventplace) {
      echo "      📍 {$event->eventplace}\n";
    }
  }
} catch (Exception $e) {
  echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🏁 Test completed!\n";
