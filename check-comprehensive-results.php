<?php
require_once('c:/MAMP/htdocs/HeritagePress2/wp-config.php');
global $wpdb;

echo "Comprehensive Test Results Check:\n\n";

$results = $wpdb->get_results("SELECT personID, firstname, lastname, birthdate, birthplace, sex FROM {$wpdb->prefix}hp_people WHERE gedcom = 'comprehensive_test' ORDER BY personID LIMIT 7");

foreach ($results as $row) {
  echo $row->personID . ': ' . $row->firstname . ' ' . $row->lastname . ' (' . $row->sex . ')' . "\n";
  echo '  Born: ' . ($row->birthdate ?: 'No date') . ' in ' . ($row->birthplace ?: 'No place') . "\n\n";
}

echo "Family data:\n";
$families = $wpdb->get_results("SELECT familyID, husband, wife, marrdate, marrplace FROM {$wpdb->prefix}hp_families WHERE gedcom = 'comprehensive_test' ORDER BY familyID LIMIT 4");

foreach ($families as $family) {
  echo $family->familyID . ': ' . $family->husband . ' + ' . $family->wife . "\n";
  echo '  Married: ' . ($family->marrdate ?: 'No date') . ' in ' . ($family->marrplace ?: 'No place') . "\n\n";
}
