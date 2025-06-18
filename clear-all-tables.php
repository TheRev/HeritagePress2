<?php

/**
 * Clear all HeritagePress database tables for testing
 */

// Load WordPress properly
require_once('c:/MAMP/htdocs/HeritagePress2/wp-config.php');

echo "=== CLEARING ALL HERITAGEPRESS TABLES ===\n";

global $wpdb;

// List of all HeritagePress tables to clear
$tables_to_clear = array(
  'hp_people',
  'hp_families',
  'hp_sources',
  'hp_events',
  'hp_eventtypes',
  'hp_citations',
  'hp_media',
  'hp_xnotes',
  'hp_repositories',
  'hp_places'
);

echo "Clearing all data from HeritagePress tables...\n\n";

foreach ($tables_to_clear as $table) {
  $full_table = $wpdb->prefix . $table;

  // Check if table exists first
  $table_exists = $wpdb->get_var($wpdb->prepare(
    "SHOW TABLES LIKE %s",
    $full_table
  ));

  if ($table_exists) {
    // Clear all data from the table
    $result = $wpdb->query("TRUNCATE TABLE $full_table");

    if ($result !== false) {
      echo "✅ Cleared $table: All records deleted\n";
    } else {
      echo "❌ Failed to clear $table: " . $wpdb->last_error . "\n";
    }
  } else {
    echo "⚠️  Table $table does not exist\n";
  }
}

echo "\n=== VERIFICATION ===\n";

// Verify all tables are empty
foreach ($tables_to_clear as $table) {
  $full_table = $wpdb->prefix . $table;

  $table_exists = $wpdb->get_var($wpdb->prepare(
    "SHOW TABLES LIKE %s",
    $full_table
  ));

  if ($table_exists) {
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table");
    echo "$table: $count records\n";
  }
}

echo "\n=== DATABASE CLEARED ===\n";
echo "All HeritagePress tables have been cleared and are ready for testing.\n";
