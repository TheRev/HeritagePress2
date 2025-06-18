<?php
require_once '../../../wp-config.php';
require_once 'includes/gedcom/class-hp-enhanced-gedcom-parser.php';

global $wpdb;

echo "Testing notes table fix...\n\n";

// Check if hp_xnotes table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}hp_xnotes'");
if ($table_exists) {
  echo "✓ hp_xnotes table exists\n";
} else {
  echo "✗ hp_xnotes table missing\n";
  exit(1);
}

// Check table structure
echo "\nChecking hp_xnotes table structure:\n";
$columns = $wpdb->get_results("DESCRIBE {$wpdb->prefix}hp_xnotes");
foreach ($columns as $column) {
  echo "  - {$column->Field} ({$column->Type})\n";
}

// Test simple note insertion
echo "\nTesting note insertion...\n";
$test_note_data = [
  'persfamID' => 'TEST001',
  'gedcom' => 'test_tree',
  'eventID' => '',
  'note' => 'This is a test note',
  'secret' => ''
];

$result = $wpdb->insert($wpdb->prefix . 'hp_xnotes', $test_note_data);
if ($result) {
  echo "✓ Note inserted successfully (ID: {$wpdb->insert_id})\n";

  // Clean up test data
  $wpdb->delete($wpdb->prefix . 'hp_xnotes', ['ID' => $wpdb->insert_id]);
  echo "✓ Test data cleaned up\n";
} else {
  echo "✗ Failed to insert note: " . $wpdb->last_error . "\n";
}

echo "\nNotes table test completed.\n";
