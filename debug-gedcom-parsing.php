<?php

/**
 * Debug GEDCOM Parsing Issues
 */

// WordPress bootstrap
$wp_root = dirname(dirname(dirname(dirname(__FILE__))));
require_once($wp_root . '/wp-config.php');
require_once($wp_root . '/wp-includes/wp-db.php');

// Load our classes
require_once(__DIR__ . '/includes/gedcom/class-hp-gedcom-importer.php');

echo "Debugging GEDCOM Parse Issues\n";
echo "=============================\n\n";

$file_path = __DIR__ . '/comprehensive_test.ged';
$tree_id = 'debug_test';

// Clear existing data
global $wpdb;
$wpdb->delete($wpdb->prefix . 'hp_people', array('gedcom' => $tree_id));
$wpdb->delete($wpdb->prefix . 'hp_families', array('gedcom' => $tree_id));

// Show first few lines of GEDCOM file
echo "First 20 lines of GEDCOM file:\n";
$lines = file($file_path);
for ($i = 0; $i < min(20, count($lines)); $i++) {
  echo sprintf("%2d: %s", $i + 1, $lines[$i]);
}

echo "\n\nTesting import with debug...\n";

try {
  // Create importer with simple options
  $importer = new HP_GEDCOM_Importer_Controller($file_path, $tree_id, array(
    'del' => 'yes'
  ));

  // Run import
  $result = $importer->import();

  if ($result && $result['success']) {
    echo "Import completed successfully!\n\n";

    // Check what was actually imported
    $people = $wpdb->get_results("SELECT personID, firstname, lastname, sex, birthdate, birthplace FROM {$wpdb->prefix}hp_people WHERE gedcom = '{$tree_id}' ORDER BY personID LIMIT 3");

    echo "Imported individuals:\n";
    foreach ($people as $person) {
      echo "ID: {$person->personID}\n";
      echo "  Name: {$person->firstname} {$person->lastname}\n";
      echo "  Sex: '{$person->sex}'\n";
      echo "  Birth Date: '{$person->birthdate}'\n";
      echo "  Birth Place: '{$person->birthplace}'\n\n";
    }

    $families = $wpdb->get_results("SELECT familyID, husband, wife, marrdate, marrplace FROM {$wpdb->prefix}hp_families WHERE gedcom = '{$tree_id}' ORDER BY familyID LIMIT 2");

    echo "Imported families:\n";
    foreach ($families as $family) {
      echo "ID: {$family->familyID}\n";
      echo "  Husband: {$family->husband}\n";
      echo "  Wife: {$family->wife}\n";
      echo "  Marriage Date: '{$family->marrdate}'\n";
      echo "  Marriage Place: '{$family->marrplace}'\n\n";
    }
  } else {
    echo "Import failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    $errors = $importer->get_errors();
    if (!empty($errors)) {
      echo "Errors: " . implode(', ', $errors) . "\n";
    }
  }
} catch (Exception $e) {
  echo "Exception: " . $e->getMessage() . "\n";
}
