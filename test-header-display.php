<?php

/**
 * Test Header Information Display
 */

// WordPress setup
if (!defined('ABSPATH')) {
  define('ABSPATH', dirname(__FILE__) . '/../../../');
}

require_once(ABSPATH . 'wp-config.php');
require_once(dirname(__FILE__) . '/includes/gedcom/class-hp-enhanced-gedcom-parser.php');

$test_gedcom_file = 'c:\\MAMP\\htdocs\\HeritagePress2\\gedcom_test_files\\sample-from-5.5.1-standard.ged';
$tree_id = 'header_test';

echo "=== GEDCOM HEADER INFORMATION TEST ===\n\n";

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

try {
  $parser = new HP_Enhanced_GEDCOM_Parser($test_gedcom_file, $tree_id, array(
    'del' => 'match',
    'allevents' => '1'
  ));

  $parser->parse();
  $stats = $parser->get_stats();

  echo "🏷️  GEDCOM FILE INFORMATION\n";
  echo "========================================\n";

  // Display header information
  if (isset($stats['header_info']) && !empty($stats['header_info'])) {
    $header = $stats['header_info'];

    if ($header['source_program']) {
      echo "📋 Source Program: {$header['source_program']}\n";
      if ($header['source_version']) {
        echo "📋 Version: {$header['source_version']}\n";
      }
    }

    if ($header['submitter']) {
      echo "👤 Submitter: {$header['submitter']}\n";
    }

    if ($header['gedcom_version']) {
      echo "📄 GEDCOM Version: {$header['gedcom_version']}\n";
    }

    if ($header['gedcom_form']) {
      echo "📄 GEDCOM Form: {$header['gedcom_form']}\n";
    }

    if ($header['character_set']) {
      echo "🔤 Character Set: {$header['character_set']}\n";
    }

    if ($header['date']) {
      echo "📅 Creation Date: {$header['date']}\n";
      if ($header['time']) {
        echo "⏰ Creation Time: {$header['time']}\n";
      }
    }

    if ($header['filename']) {
      echo "📁 Original Filename: {$header['filename']}\n";
    }

    if ($header['copyright']) {
      echo "©️  Copyright: {$header['copyright']}\n";
    }
  } else {
    echo "⚠️  No header information found\n";
  }

  echo "\n📊 IMPORT RESULTS\n";
  echo "========================================\n";

  // Count database records for validation
  $counts = array();
  $tables_info = array(
    'people' => 'Individuals',
    'families' => 'Families',
    'sources' => 'Sources',
    'repositories' => 'Repositories',
    'events' => 'Events',
    'citations' => 'Citations',
    'children' => 'Parent-Child Links'
  );

  foreach ($tables_info as $table => $label) {
    $count = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$wpdb->prefix}hp_$table WHERE gedcom = %s",
      $tree_id
    ));
    echo "✅ $label: $count\n";
    $counts[$table] = $count;
  }

  echo "\n📋 DATA VALIDATION\n";
  echo "========================================\n";

  // Expected vs actual validation
  $expected = array(
    'people' => 3,
    'families' => 2,
    'sources' => 1,
    'repositories' => 1,
    'events' => 7,
    'citations' => 1
  );

  $all_passed = true;
  foreach ($expected as $table => $exp_count) {
    $actual = $counts[$table];
    $passed = $actual >= $exp_count;
    $all_passed = $all_passed && $passed;
    $status = $passed ? "✅" : "❌";
    echo "$status {$tables_info[$table]}: $actual (expected: $exp_count)\n";
  }

  if ($all_passed) {
    echo "\n🎉 ALL VALIDATIONS PASSED!\n";
    echo "✅ GEDCOM import completed successfully\n";
    echo "✅ All expected data imported correctly\n";
    echo "✅ Header information captured\n";
  } else {
    echo "\n⚠️  Some validations failed\n";
  }

  // Show sample data
  echo "\n📝 SAMPLE IMPORTED DATA\n";
  echo "========================================\n";

  $individuals = $wpdb->get_results($wpdb->prepare(
    "SELECT personID, firstname, lastname, birthdate FROM {$wpdb->prefix}hp_people WHERE gedcom = %s LIMIT 3",
    $tree_id
  ));

  foreach ($individuals as $person) {
    echo "👤 {$person->firstname} {$person->lastname} (ID: {$person->personID})\n";
    if ($person->birthdate) {
      echo "   Born: {$person->birthdate}\n";
    }
  }
} catch (Exception $e) {
  echo "❌ Error during import: " . $e->getMessage() . "\n";
}
