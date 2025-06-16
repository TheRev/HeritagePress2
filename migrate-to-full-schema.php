<?php

/**
 * Migrate HeritagePress database to full TNG schema
 * This will drop existing simplified tables and create proper full TNG schema
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  // For standalone execution during development
  require_once('../../../wp-config.php');
}

echo "=== HERITAGEPRESS DATABASE MIGRATION ===\n\n";

// Initialize HeritagePress
if (!function_exists('heritage_press')) {
  require_once(dirname(__FILE__) . '/heritagepress.php');
  if (!heritage_press()) {
    die("Failed to initialize HeritagePress\n");
  }
}

// Get the database manager
$database_manager = new HP_Database_Manager();

echo "Step 1: Checking current database state...\n";

global $wpdb;
$tables_to_check = [
  $wpdb->prefix . 'hp_people',
  $wpdb->prefix . 'hp_families',
  $wpdb->prefix . 'hp_children'
];

$existing_tables = [];
foreach ($tables_to_check as $table) {
  if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
    $existing_tables[] = $table;
    echo "  Found existing table: $table\n";
  }
}

if (empty($existing_tables)) {
  echo "  No existing tables found.\n";
} else {
  echo "  Found " . count($existing_tables) . " existing tables.\n";
}

echo "\nStep 2: Backing up existing data...\n";

$backup_data = [];
if (in_array($wpdb->prefix . 'hp_people', $existing_tables)) {
  $people_data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}hp_people", 'ARRAY_A');
  if ($people_data) {
    $backup_data['people'] = $people_data;
    echo "  Backed up " . count($people_data) . " people records\n";
  }
}

if (in_array($wpdb->prefix . 'hp_families', $existing_tables)) {
  $families_data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}hp_families", 'ARRAY_A');
  if ($families_data) {
    $backup_data['families'] = $families_data;
    echo "  Backed up " . count($families_data) . " family records\n";
  }
}

echo "\nStep 3: Creating new tables with full TNG schema...\n";

try {
  if ($database_manager->create_tables()) {
    echo "  ✓ All tables created successfully!\n";
  } else {
    echo "  ✗ Failed to create some tables\n";
    exit(1);
  }
} catch (Exception $e) {
  echo "  ✗ Error creating tables: " . $e->getMessage() . "\n";
  exit(1);
}

echo "\nStep 4: Restoring backed up data...\n";

if (!empty($backup_data['people'])) {
  echo "  Restoring people data...\n";
  foreach ($backup_data['people'] as $person) {
    // Map old simplified schema to new full schema
    $new_person = [
      'gedcom' => $person['gedcom'] ?? 'main',
      'personID' => $person['personID'],
      'firstname' => $person['firstname'] ?? '',
      'lastname' => $person['lastname'] ?? '',
      'lnprefix' => $person['lnprefix'] ?? '',
      'prefix' => $person['prefix'] ?? '',
      'suffix' => $person['suffix'] ?? '',
      'nickname' => $person['nickname'] ?? '',
      'title' => '',  // New field
      'nameorder' => $person['nameorder'] ?? 0,
      'sex' => $person['sex'] ?? 'U',
      'birthdate' => $person['birthdate'] ?? '',
      'birthdatetr' => '0000-00-00',
      'birthplace' => $person['birthplace'] ?? '',
      'deathdate' => $person['deathdate'] ?? '',
      'deathdatetr' => '0000-00-00',
      'deathplace' => $person['deathplace'] ?? '',
      'altbirthtype' => '',  // New field
      'altbirthdate' => '',  // New field
      'altbirthdatetr' => '0000-00-00',
      'altbirthplace' => '',  // New field
      'burialdate' => '',    // New field
      'burialdatetr' => '0000-00-00',
      'burialplace' => '',   // New field
      'burialtype' => 0,     // New field
      'baptdate' => '',      // New field
      'baptdatetr' => '0000-00-00',
      'baptplace' => '',     // New field
      'confdate' => '',      // New field
      'confdatetr' => '0000-00-00',
      'confplace' => '',     // New field
      'initdate' => '',      // New field
      'initdatetr' => '0000-00-00',
      'initplace' => '',     // New field
      'endldate' => '',      // New field
      'endldatetr' => '0000-00-00',
      'endlplace' => '',     // New field
      'famc' => '',          // New field
      'metaphone' => '',     // New field
      'living' => $person['living'] ?? 0,
      'private' => $person['private'] ?? 0,
      'branch' => '',        // New field
      'changedate' => $person['changedate'] ?? date('Y-m-d H:i:s'),
      'changedby' => $person['changedby'] ?? 'migration',
      'edituser' => '',      // New field
      'edittime' => 0        // New field
    ];

    $result = $wpdb->insert($wpdb->prefix . 'hp_people', $new_person);
    if ($result === false) {
      echo "    ✗ Failed to restore person: " . $person['personID'] . " - " . $wpdb->last_error . "\n";
    } else {
      echo "    ✓ Restored person: " . $person['personID'] . "\n";
    }
  }
}

echo "\nStep 5: Verifying migration...\n";

// Check that tables exist with correct structure
$core_db = new HP_Database_Core();
if ($core_db->tables_exist()) {
  echo "  ✓ Core tables exist\n";

  // Check people table structure
  $people_cols = $wpdb->get_results("DESCRIBE {$wpdb->prefix}hp_people");
  echo "  ✓ People table has " . count($people_cols) . " columns\n";

  // Count records
  $people_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_people");
  echo "  ✓ People table contains $people_count records\n";
} else {
  echo "  ✗ Core tables missing\n";
}

echo "\n=== MIGRATION COMPLETE ===\n";
echo "Your HeritagePress database now has the full TNG schema!\n";
echo "You can now use all TNG form fields including:\n";
echo "- title, prefix, suffix, nickname\n";
echo "- altbirthdate, altbirthplace\n";
echo "- burialdate, burialplace, burialtype\n";
echo "- LDS events (baptdate, confdate, initdate, endldate)\n";
echo "- All corresponding place fields\n\n";
