<?php

/**
 * Simple test with just the first individual from our comprehensive GEDCOM
 */

// Create a minimal test GEDCOM with just one individual
$test_gedcom = "0 HEAD
1 SOUR Test
1 GEDC
2 VERS 5.5.1
1 CHAR UTF-8
0 @I1@ INDI
1 NAME John William /Smith/
1 SEX M
1 BIRT
2 DATE 15 MAR 1920
2 PLAC Chicago, Cook County, Illinois, USA
0 TRLR";

file_put_contents(__DIR__ . '/simple_debug.ged', $test_gedcom);

// WordPress bootstrap
$wp_root = dirname(dirname(dirname(dirname(__FILE__))));
require_once($wp_root . '/wp-config.php');
require_once($wp_root . '/wp-includes/wp-db.php');

// Load our classes
require_once(__DIR__ . '/includes/gedcom/class-hp-gedcom-importer.php');

echo "Testing Simple Individual Parse\n";
echo "===============================\n";

$file_path = __DIR__ . '/simple_debug.ged';
$tree_id = 'simple_debug';

// Clear existing data
global $wpdb;
$wpdb->delete($wpdb->prefix . 'hp_people', array('gedcom' => $tree_id));

try {
  $importer = new HP_GEDCOM_Importer_Controller($file_path, $tree_id, array('del' => 'yes'));
  $result = $importer->import();

  if ($result && $result['success']) {
    echo "Import completed successfully!\n\n";

    // Check what was actually imported
    $person = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}hp_people WHERE gedcom = '{$tree_id}' AND personID = 'I1'");

    if ($person) {
      echo "Imported individual I1:\n";
      echo "  Full Name: {$person->firstname} {$person->lastname}\n";
      echo "  Sex: '{$person->sex}'\n";
      echo "  Birth Date: '{$person->birthdate}'\n";
      echo "  Birth Place: '{$person->birthplace}'\n";
      echo "  Death Date: '{$person->deathdate}'\n";
      echo "  Death Place: '{$person->deathplace}'\n";
    } else {
      echo "No individual found with ID I1\n";
    }
  } else {
    echo "Import failed: " . ($result['error'] ?? 'Unknown error') . "\n";
  }
} catch (Exception $e) {
  echo "Exception: " . $e->getMessage() . "\n";
}
