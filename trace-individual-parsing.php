<?php

/**
 * Trace individual parsing issue
 */

// Load WordPress properly
require_once('c:/MAMP/htdocs/HeritagePress2/wp-config.php');

// Load the enhanced parser
require_once('includes/gedcom/class-hp-enhanced-gedcom-parser.php');

$gedcom_file = 'C:\\MAMP\\htdocs\\HeritagePress2\\gedcom_test_files\\FTM_lyle_2025-06-17.ged';

echo "=== TRACING INDIVIDUAL PARSING ===\n";

// Clear events table first
global $wpdb;
$wpdb->delete($wpdb->prefix . 'hp_events', array('gedcom' => 'trace_test'));

// Create parser
$parser = new HP_Enhanced_GEDCOM_Parser($gedcom_file, 'trace_test', array(
  'del' => 'yes',
  'allevents' => 'yes'
));

// Use reflection to access private methods
$reflection = new ReflectionClass($parser);

// Access the file handle
$file_handle_prop = $reflection->getProperty('file_handle');
$file_handle_prop->setAccessible(true);
$file_handle = $file_handle_prop->getValue($parser);

// Access get_line method
$get_line_method = $reflection->getMethod('get_line');
$get_line_method->setAccessible(true);

// Access line_info property
$line_info_prop = $reflection->getProperty('line_info');
$line_info_prop->setAccessible(true);

// Access parse_individual method
$parse_individual_method = $reflection->getMethod('parse_individual');
$parse_individual_method->setAccessible(true);

// Find the individual record
$line_count = 0;
do {
  $line_info = $get_line_method->invoke($parser);
  $line_info_prop->setValue($parser, $line_info);
  $line_count++;

  if ($line_count > 100) break; // Safety limit

} while (!($line_info['level'] == 0 && preg_match('/^@I114@/', $line_info['tag'])));

if ($line_info && preg_match('/^@I114@/', $line_info['tag'])) {
  echo "Found individual I114 at line $line_count\n";
  echo "Calling parse_individual...\n";

  // Parse the individual
  $parse_individual_method->invoke($parser, 'I114');

  echo "Individual parsing completed\n";

  // Check how many events were saved
  $events_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}hp_events WHERE gedcom = %s AND persfamID = %s",
    'trace_test',
    'I114'
  ));

  echo "Events saved for I114: $events_count\n";

  if ($events_count > 0) {
    $events = $wpdb->get_results($wpdb->prepare(
      "SELECT eventtypeID, eventdate, eventplace FROM {$wpdb->prefix}hp_events WHERE gedcom = %s AND persfamID = %s",
      'trace_test',
      'I114'
    ));

    foreach ($events as $event) {
      echo "  Event type {$event->eventtypeID}: {$event->eventdate} at {$event->eventplace}\n";
    }
  }
} else {
  echo "Individual I114 not found!\n";
}

fclose($file_handle);
echo "=== TRACE COMPLETED ===\n";
