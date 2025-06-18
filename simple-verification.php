<?php

/**
 * Simple verification of imported data
 */

require_once('c:/MAMP/htdocs/HeritagePress2/wp-config.php');

global $wpdb;

echo "=== IMPORTED DATA VERIFICATION ===\n\n";

// Check people data
$people = $wpdb->get_results("SELECT personID, firstname, lastname, sex, birthdate, birthplace, deathdate, deathplace, burialdate, burialplace FROM {$wpdb->prefix}hp_people WHERE gedcom = 'ftm_test'");

echo "PEOPLE RECORDS (" . count($people) . "):\n";
foreach ($people as $person) {
  echo "ID: {$person->personID}\n";
  echo "Name: {$person->firstname} {$person->lastname}\n";
  echo "Sex: {$person->sex}\n";
  echo "Birth: {$person->birthdate} at {$person->birthplace}\n";
  echo "Death: {$person->deathdate} at {$person->deathplace}\n";
  echo "Burial: {$person->burialdate} at {$person->burialplace}\n";
  echo "\n";
}

// Check families
$families = $wpdb->get_results("SELECT familyID, husband, wife, marrdate, marrplace, divdate, divplace FROM {$wpdb->prefix}hp_families WHERE gedcom = 'ftm_test'");

echo "FAMILY RECORDS (" . count($families) . "):\n";
foreach ($families as $family) {
  echo "ID: {$family->familyID}\n";
  echo "Husband: {$family->husband}, Wife: {$family->wife}\n";
  echo "Marriage: {$family->marrdate} at {$family->marrplace}\n";
  echo "Divorce: {$family->divdate} at {$family->divplace}\n";
  echo "\n";
}

// Check events
$events = $wpdb->get_results("SELECT persfamID, eventtypeID, eventdate, eventplace, parenttag FROM {$wpdb->prefix}hp_events WHERE gedcom = 'ftm_test' ORDER BY persfamID");

echo "EVENT RECORDS (" . count($events) . "):\n";
foreach ($events as $event) {
  echo "Person/Family: {$event->persfamID}\n";
  echo "Type: {$event->eventtypeID}, Parent: {$event->parenttag}\n";
  echo "Date: {$event->eventdate}\n";
  echo "Place: {$event->eventplace}\n";
  echo "\n";
}

// Check sources
$sources_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_sources WHERE gedcom = 'ftm_test'");
echo "SOURCES: $sources_count records\n";

// Check media
$media_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_media WHERE gedcom = 'ftm_test'");
echo "MEDIA: $media_count records\n";

// Check repositories
$repo_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_repositories WHERE gedcom = 'ftm_test'");
echo "REPOSITORIES: $repo_count records\n";

echo "\n=== VERIFICATION COMPLETED ===\n";
