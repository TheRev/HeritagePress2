<?php
require_once 'heritagepress.php';

echo "Testing import jobs table creation...\n";

if (class_exists('HP_Database_Manager')) {
  echo "HP_Database_Manager class found\n";
  try {
    $db = new HP_Database_Manager();
    $result = $db->ensure_import_jobs_table();
    echo "Table creation result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";

    // Test if table exists now
    global $wpdb;
    $table_name = $wpdb->prefix . 'hp_import_jobs';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    echo "Table exists check: " . ($table_exists ? 'YES' : 'NO') . "\n";
  } catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
  }
} else {
  echo "HP_Database_Manager class not found\n";
}
