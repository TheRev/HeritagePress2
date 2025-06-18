<?php
require_once('c:/MAMP/htdocs/HeritagePress2/wp-config.php');
global $wpdb;

echo "Recent import results (surnames check):\n";
$results = $wpdb->get_results("SELECT personID, lastname, firstname FROM {$wpdb->prefix}hp_people WHERE gedcom = 'main' ORDER BY ID DESC LIMIT 4");

foreach ($results as $row) {
  echo $row->personID . ': ' . $row->firstname . ' ' . $row->lastname . "\n";
}
