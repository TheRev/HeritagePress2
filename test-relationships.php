<?php

/**
 * Test Relationships Functionality
 * Quick test to verify the spouse/partner lookup functionality
 */

// This file can be accessed via browser to test relationship functionality
// Remove this file after testing

define('ABSPATH', '../../../');
require_once('../../../wp-config.php');

global $wpdb;

// Table references
$people_table = $wpdb->prefix . 'hp_people';
$families_table = $wpdb->prefix . 'hp_families';

// Helper function for testing
function get_person_relationships($person_id, $gedcom, $wpdb, $families_table, $people_table)
{
  // Find families where this person is either husband or wife
  $families = $wpdb->get_results(
    $wpdb->prepare(
      "SELECT f.*,
       h.firstname as husband_firstname, h.lastname as husband_lastname, h.lnprefix as husband_lnprefix,
       w.firstname as wife_firstname, w.lastname as wife_lastname, w.lnprefix as wife_lnprefix
       FROM $families_table f
       LEFT JOIN $people_table h ON f.husband = h.personID AND f.gedcom = h.gedcom
       LEFT JOIN $people_table w ON f.wife = w.personID AND f.gedcom = w.gedcom
       WHERE f.gedcom = %s AND (f.husband = %s OR f.wife = %s)
       ORDER BY f.marrdatetr",
      $gedcom,
      $person_id,
      $person_id
    ),
    ARRAY_A
  );

  $spouses = array();
  $partners = array();

  foreach ($families as $family) {
    $is_husband = ($family['husband'] === $person_id);
    $spouse_id = $is_husband ? $family['wife'] : $family['husband'];

    if (!empty($spouse_id)) {
      $spouse_name_parts = array();
      $spouse_firstname = $is_husband ? $family['wife_firstname'] : $family['husband_firstname'];
      $spouse_lastname = $is_husband ? $family['wife_lastname'] : $family['husband_lastname'];
      $spouse_lnprefix = $is_husband ? $family['wife_lnprefix'] : $family['husband_lnprefix'];

      if (!empty($spouse_firstname)) $spouse_name_parts[] = $spouse_firstname;
      if (!empty($spouse_lnprefix)) $spouse_name_parts[] = $spouse_lnprefix;
      if (!empty($spouse_lastname)) $spouse_name_parts[] = $spouse_lastname;

      $spouse_name = implode(' ', $spouse_name_parts);

      // Determine if this is a spouse (married) or partner (unmarried/divorced)
      if (!empty($family['marrdate']) && empty($family['divdate'])) {
        $spouses[] = array(
          'name' => $spouse_name,
          'id' => $spouse_id,
          'marriage_date' => $family['marrdate'],
          'marriage_place' => $family['marrplace']
        );
      } else {
        $partners[] = array(
          'name' => $spouse_name,
          'id' => $spouse_id,
          'relationship_type' => !empty($family['divdate']) ? 'former spouse' : 'partner'
        );
      }
    }
  }

  return array('spouses' => $spouses, 'partners' => $partners);
}

echo "<h1>HeritagePress Relationships Test</h1>";

// Check if tables exist
$people_exists = $wpdb->get_var("SHOW TABLES LIKE '$people_table'") == $people_table;
$families_exists = $wpdb->get_var("SHOW TABLES LIKE '$families_table'") == $families_table;

echo "<h2>Database Tables Status:</h2>";
echo "<p>People table ($people_table): " . ($people_exists ? "EXISTS" : "MISSING") . "</p>";
echo "<p>Families table ($families_table): " . ($families_exists ? "EXISTS" : "MISSING") . "</p>";

if ($people_exists && $families_exists) {
  // Get sample people data
  $sample_people = $wpdb->get_results(
    "SELECT personID, gedcom, firstname, lastname FROM $people_table LIMIT 5",
    ARRAY_A
  );

  echo "<h2>Sample People and Their Relationships:</h2>";

  foreach ($sample_people as $person) {
    echo "<h3>Person: {$person['firstname']} {$person['lastname']} (ID: {$person['personID']})</h3>";

    $relationships = get_person_relationships($person['personID'], $person['gedcom'], $wpdb, $families_table, $people_table);

    echo "<strong>Spouses:</strong> ";
    if (!empty($relationships['spouses'])) {
      foreach ($relationships['spouses'] as $spouse) {
        echo $spouse['name'] . " (ID: {$spouse['id']}) ";
        if (!empty($spouse['marriage_date'])) {
          echo "married {$spouse['marriage_date']} ";
        }
        echo "<br>";
      }
    } else {
      echo "None<br>";
    }

    echo "<strong>Partners:</strong> ";
    if (!empty($relationships['partners'])) {
      foreach ($relationships['partners'] as $partner) {
        echo $partner['name'] . " (ID: {$partner['id']}, {$partner['relationship_type']}) <br>";
      }
    } else {
      echo "None<br>";
    }

    echo "<hr>";
  }
} else {
  echo "<p style='color: red;'>Required database tables are missing. Please ensure the HeritagePress database is properly set up.</p>";
}

echo "<p><em>This is a test file. Delete after testing.</em></p>";
