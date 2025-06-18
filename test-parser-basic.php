<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../../wp-config.php';

echo "Testing Basic Parser Loading...\n";
echo "==============================\n\n";

// Test if the parser file exists
$parser_file = 'includes/gedcom/class-hp-enhanced-gedcom-parser.php';
if (file_exists($parser_file)) {
  echo "✓ Parser file exists: $parser_file\n";
} else {
  echo "✗ Parser file missing: $parser_file\n";
  exit(1);
}

// Test file inclusion
echo "Testing file inclusion...\n";
try {
  require_once $parser_file;
  echo "✓ Parser file included successfully\n";
} catch (Exception $e) {
  echo "✗ Failed to include parser: " . $e->getMessage() . "\n";
  exit(1);
}

// Test class existence
if (class_exists('HP_Enhanced_GEDCOM_Parser')) {
  echo "✓ HP_Enhanced_GEDCOM_Parser class found\n";
} else {
  echo "✗ HP_Enhanced_GEDCOM_Parser class not found\n";
  exit(1);
}

// Test database connection
global $wpdb;
if ($wpdb) {
  echo "✓ WordPress database connection available\n";
} else {
  echo "✗ WordPress database connection missing\n";
  exit(1);
}

// Test table existence
$xnotes_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}hp_xnotes'");
if ($xnotes_exists) {
  echo "✓ hp_xnotes table exists\n";
} else {
  echo "✗ hp_xnotes table missing\n";
}

$people_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}hp_people'");
if ($people_exists) {
  echo "✓ hp_people table exists\n";
} else {
  echo "✗ hp_people table missing\n";
}

echo "\nBasic checks completed. All components appear to be ready.\n";
echo "The parser should be functional for GEDCOM import.\n";
