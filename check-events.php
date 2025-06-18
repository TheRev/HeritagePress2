<?php
require_once('c:/MAMP/htdocs/HeritagePress2/wp-config.php');

global $wpdb;

echo "=== EVENTS TABLE ANALYSIS ===\n\n";

$events = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}hp_events WHERE gedcom = 'ftm_test' ORDER BY persfamID, eventtypeID");

echo "Total events: " . count($events) . "\n\n";

foreach ($events as $event) {
  echo "Person/Family: {$event->persfamID}\n";
  echo "Event Type ID: {$event->eventtypeID}\n";
  echo "Date: {$event->eventdate}\n";
  echo "Place: {$event->eventplace}\n";
  echo "Parent Tag: {$event->parenttag}\n";
  echo "Info: {$event->info}\n";
  echo "---\n";
}
