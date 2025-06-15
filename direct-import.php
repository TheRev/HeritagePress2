<?php

/**
 * Simple Direct Database Import for Testing
 * This script directly inserts test data into the HeritagePress database
 */

// Load WordPress
require_once(dirname(__FILE__) . '/../../../wp-config.php');

// Security check
if (!current_user_can('manage_options')) {
  die('Permission denied');
}

global $wpdb;

// Function to check and create tables if needed
function check_and_create_tables()
{
  global $wpdb;

  $people_table = $wpdb->prefix . 'hp_people';

  // Check if people table exists
  $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$people_table'") == $people_table;

  if (!$table_exists) {
    // Create the basic people table
    $sql = "CREATE TABLE $people_table (
            ID int(11) NOT NULL AUTO_INCREMENT,
            personID varchar(20) NOT NULL,
            gedcom varchar(50) NOT NULL DEFAULT 'main',
            firstname varchar(100) DEFAULT NULL,
            lastname varchar(100) DEFAULT NULL,
            lnprefix varchar(50) DEFAULT NULL,
            prefix varchar(20) DEFAULT NULL,
            suffix varchar(20) DEFAULT NULL,
            nickname varchar(50) DEFAULT NULL,
            nameorder varchar(20) DEFAULT 'western',
            sex varchar(1) DEFAULT NULL,
            birthdate varchar(50) DEFAULT NULL,
            birthplace varchar(255) DEFAULT NULL,
            deathdate varchar(50) DEFAULT NULL,
            deathplace varchar(255) DEFAULT NULL,
            living tinyint(1) DEFAULT 0,
            private tinyint(1) DEFAULT 0,
            changedate datetime DEFAULT CURRENT_TIMESTAMP,
            changedby varchar(50) DEFAULT NULL,
            PRIMARY KEY (ID),
            UNIQUE KEY person_tree (personID, gedcom),
            KEY name_index (lastname, firstname),
            KEY birth_index (birthdate),
            KEY living_index (living),
            KEY private_index (private)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    return $wpdb->get_var("SHOW TABLES LIKE '$people_table'") == $people_table;
  }

  return true;
}

// Function to insert sample data
function insert_sample_data()
{
  global $wpdb;

  $people_table = $wpdb->prefix . 'hp_people';

  // Clear existing test data
  $wpdb->delete($people_table, array('gedcom' => 'test_tree'));

  // Sample people data
  $sample_people = array(
    array(
      'personID' => 'I1',
      'gedcom' => 'test_tree',
      'firstname' => 'Robert Eugene',
      'lastname' => 'Williams',
      'sex' => 'M',
      'birthdate' => '2 OCT 1822',
      'birthplace' => 'Weston, Madison, Connecticut',
      'deathdate' => '14 APR 1905',
      'deathplace' => 'Stamford, Fairfield, CT',
      'living' => 0,
      'private' => 0,
      'changedate' => current_time('mysql'),
      'changedby' => 'test_import'
    ),
    array(
      'personID' => 'I2',
      'gedcom' => 'test_tree',
      'firstname' => 'Mary Ann',
      'lastname' => 'Wilson',
      'sex' => 'F',
      'birthdate' => 'BEF 1828',
      'birthplace' => 'Connecticut',
      'deathdate' => '',
      'deathplace' => '',
      'living' => 0,
      'private' => 0,
      'changedate' => current_time('mysql'),
      'changedby' => 'test_import'
    ),
    array(
      'personID' => 'I3',
      'gedcom' => 'test_tree',
      'firstname' => 'Joe',
      'lastname' => 'Williams',
      'sex' => 'M',
      'birthdate' => '11 JUN 1861',
      'birthplace' => 'Idaho Falls, Bonneville, Idaho',
      'deathdate' => '',
      'deathplace' => '',
      'living' => 0,
      'private' => 0,
      'changedate' => current_time('mysql'),
      'changedby' => 'test_import'
    ),
    array(
      'personID' => 'I4',
      'gedcom' => 'test_tree',
      'firstname' => 'John',
      'lastname' => 'Smith',
      'sex' => 'M',
      'birthdate' => '15 JUL 1850',
      'birthplace' => 'New York, New York',
      'deathdate' => '3 MAR 1920',
      'deathplace' => 'Brooklyn, New York',
      'living' => 0,
      'private' => 0,
      'changedate' => current_time('mysql'),
      'changedby' => 'test_import'
    ),
    array(
      'personID' => 'I5',
      'gedcom' => 'test_tree',
      'firstname' => 'Sarah Elizabeth',
      'lastname' => 'Johnson',
      'sex' => 'F',
      'birthdate' => '22 MAR 1855',
      'birthplace' => 'Boston, Massachusetts',
      'deathdate' => '',
      'deathplace' => '',
      'living' => 0,
      'private' => 0,
      'changedate' => current_time('mysql'),
      'changedby' => 'test_import'
    ),
    array(
      'personID' => 'I6',
      'gedcom' => 'test_tree',
      'firstname' => 'Emily Rose',
      'lastname' => 'Davis',
      'sex' => 'F',
      'birthdate' => '10 MAY 1990',
      'birthplace' => 'Seattle, Washington',
      'deathdate' => '',
      'deathplace' => '',
      'living' => 1,
      'private' => 1,
      'changedate' => current_time('mysql'),
      'changedby' => 'test_import'
    ),
    array(
      'personID' => 'I7',
      'gedcom' => 'test_tree',
      'firstname' => 'Michael James',
      'lastname' => 'Thompson',
      'sex' => 'M',
      'birthdate' => 'ABT 1975',
      'birthplace' => 'Los Angeles, California',
      'deathdate' => '',
      'deathplace' => '',
      'living' => 1,
      'private' => 0,
      'changedate' => current_time('mysql'),
      'changedby' => 'test_import'
    ),
    array(
      'personID' => 'I8',
      'gedcom' => 'test_tree',
      'firstname' => 'Unknown',
      'lastname' => 'Person',
      'sex' => 'U',
      'birthdate' => '',
      'birthplace' => '',
      'deathdate' => '',
      'deathplace' => '',
      'living' => 0,
      'private' => 0,
      'changedate' => current_time('mysql'),
      'changedby' => 'test_import'
    )
  );

  $inserted = 0;
  $errors = array();

  foreach ($sample_people as $person) {
    $result = $wpdb->insert($people_table, $person);

    if ($result !== false) {
      $inserted++;
    } else {
      $errors[] = $wpdb->last_error . " - Person: " . $person['firstname'] . ' ' . $person['lastname'];
    }
  }

  return array(
    'success' => $inserted > 0,
    'inserted' => $inserted,
    'total' => count($sample_people),
    'errors' => $errors
  );
}

// Run the import
echo "<!DOCTYPE html><html><head><title>HeritagePress Database Import</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;}";
echo ".success{background:#d4edda;border:1px solid #c3e6cb;color:#155724;padding:15px;border-radius:4px;margin:20px 0;}";
echo ".error{background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:15px;border-radius:4px;margin:20px 0;}";
echo ".info{background:#e7f3ff;border:1px solid #b6d7ff;padding:15px;border-radius:4px;margin:20px 0;}";
echo "</style></head><body>";

echo "<h1>HeritagePress Database Import</h1>";

// Check tables
echo "<h2>1. Checking Database Tables</h2>";
if (check_and_create_tables()) {
  echo "<div class='success'>✅ People table exists or was created successfully</div>";
} else {
  echo "<div class='error'>❌ Failed to create people table</div>";
  echo "</body></html>";
  exit;
}

// Show table info
$people_table = $wpdb->prefix . 'hp_people';
$existing_count = $wpdb->get_var("SELECT COUNT(*) FROM $people_table WHERE gedcom = 'test_tree'");
echo "<div class='info'>Current test_tree people count: $existing_count</div>";

// Insert data
echo "<h2>2. Inserting Sample Data</h2>";
$result = insert_sample_data();

if ($result['success']) {
  echo "<div class='success'>";
  echo "<h3>✅ Import Successful!</h3>";
  echo "<p>Inserted {$result['inserted']} out of {$result['total']} people into 'test_tree'</p>";

  if (!empty($result['errors'])) {
    echo "<h4>Errors:</h4><ul>";
    foreach ($result['errors'] as $error) {
      echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
  }
  echo "</div>";
} else {
  echo "<div class='error'>";
  echo "<h3>❌ Import Failed</h3>";
  echo "<p>No records were inserted</p>";
  if (!empty($result['errors'])) {
    echo "<h4>Errors:</h4><ul>";
    foreach ($result['errors'] as $error) {
      echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
  }
  echo "</div>";
}

// Show final count
$final_count = $wpdb->get_var("SELECT COUNT(*) FROM $people_table WHERE gedcom = 'test_tree'");
echo "<div class='info'>Final test_tree people count: $final_count</div>";

// Show sample records
if ($final_count > 0) {
  echo "<h2>3. Sample Records</h2>";
  $samples = $wpdb->get_results("SELECT personID, firstname, lastname, sex, birthdate, living FROM $people_table WHERE gedcom = 'test_tree' ORDER BY personID LIMIT 5");

  if ($samples) {
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse:collapse;width:100%;'>";
    echo "<tr><th>Person ID</th><th>Name</th><th>Sex</th><th>Birth Date</th><th>Living</th></tr>";
    foreach ($samples as $person) {
      echo "<tr>";
      echo "<td>" . htmlspecialchars($person->personID) . "</td>";
      echo "<td>" . htmlspecialchars($person->firstname . ' ' . $person->lastname) . "</td>";
      echo "<td>" . htmlspecialchars($person->sex) . "</td>";
      echo "<td>" . htmlspecialchars($person->birthdate) . "</td>";
      echo "<td>" . ($person->living ? 'Yes' : 'No') . "</td>";
      echo "</tr>";
    }
    echo "</table>";
  }
}

echo "<h2>4. Next Steps</h2>";
echo "<div class='info'>";
echo "<p>Now you can test the People section:</p>";
echo "<ul>";
echo "<li><a href='" . admin_url('admin.php?page=heritagepress-people') . "'>Go to HeritagePress → People</a></li>";
echo "<li>Select 'test_tree' from any tree dropdown</li>";
echo "<li>Test browsing, searching, adding, and editing people</li>";
echo "<li>Try the reports and utilities features</li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";
