<?php

/**
 * Debug GEDCOM Import - See exactly what's happening
 */

// Load WordPress properly
require_once('c:/MAMP/htdocs/HeritagePress2/wp-config.php');

// Load the enhanced parser
require_once('includes/gedcom/class-hp-enhanced-gedcom-parser.php');

echo "=== DEBUG GEDCOM IMPORT ===\n";

$gedcom_file = 'C:\\MAMP\\htdocs\\HeritagePress2\\gedcom_test_files\\FTM_lyle_2025-06-17.ged';

global $wpdb;

// Enable WordPress debug mode
$wpdb->show_errors();

// Clear existing data
echo "Clearing test data...\n";
$wpdb->delete($wpdb->prefix . 'hp_people', array('gedcom' => 'debug_test'));
$wpdb->delete($wpdb->prefix . 'hp_families', array('gedcom' => 'debug_test'));
$wpdb->delete($wpdb->prefix . 'hp_events', array('gedcom' => 'debug_test'));

try {
  $parser = new HP_Enhanced_GEDCOM_Parser($gedcom_file, 'debug_test', array(
    'del' => 'yes',
    'ucaselast' => 0,
    'allevents' => 'yes'
  ));

  echo "Starting debug import...\n";
  $result = $parser->parse();

  if ($result['success']) {
    echo "Import completed successfully!\n\n";

    // Check what's actually in the database
    echo "=== DATABASE VERIFICATION ===\n";

    $people = $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM {$wpdb->prefix}hp_people WHERE gedcom = %s",
      'debug_test'
    ));

    echo "People records found: " . count($people) . "\n";
    foreach ($people as $person) {
      echo "Person: {$person->personID} - {$person->firstname} {$person->lastname}\n";
      echo "  Birth: {$person->birthdate} at {$person->birthplace}\n";
      echo "  Death: {$person->deathdate} at {$person->deathplace}\n";
      echo "  Sex: {$person->sex}\n";
      echo "  Living: {$person->living}, Private: {$person->private}\n\n";
    }

    $families = $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM {$wpdb->prefix}hp_families WHERE gedcom = %s",
      'debug_test'
    ));

    echo "Family records found: " . count($families) . "\n";
    foreach ($families as $family) {
      echo "Family: {$family->familyID} - H:{$family->husband} W:{$family->wife}\n";
      echo "  Marriage: {$family->marrdate} at {$family->marrplace}\n\n";
    }

    $events = $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM {$wpdb->prefix}hp_events WHERE gedcom = %s",
      'debug_test'
    ));

    echo "Event records found: " . count($events) . "\n";
    foreach ($events as $event) {
      echo "Event: {$event->persfamID} - Type:{$event->eventtypeID} - {$event->eventdate} at {$event->eventplace}\n";
    }

    // Show any warnings
    if (!empty($result['warnings'])) {
      echo "\n=== WARNINGS ===\n";
      foreach (array_slice($result['warnings'], 0, 10) as $warning) {
        echo "- $warning\n";
      }
    }
  } else {
    echo "Import FAILED!\n";
    echo "Error: " . $result['error'] . "\n";
  }
} catch (Exception $e) {
  echo "EXCEPTION: " . $e->getMessage() . "\n";
  echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Show any SQL errors
if ($wpdb->last_error) {
  echo "\nSQL ERROR: " . $wpdb->last_error . "\n";
}

echo "\n=== DEBUG COMPLETED ===\n";
