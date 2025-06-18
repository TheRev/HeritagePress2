<?php
require_once '../../../wp-config.php';
global $wpdb;

echo "Simple notes table test...\n\n";

// Test inserting a note directly
$note_data = [
  'noteID' => 'N001',
  'gedcom' => 'test_direct',
  'note' => 'This is a direct test note'
];

echo "Inserting test note...\n";
$result = $wpdb->insert($wpdb->prefix . 'hp_xnotes', $note_data);

if ($result) {
  echo "✓ Note inserted successfully (ID: {$wpdb->insert_id})\n";

  // Verify it was inserted
  $inserted_note = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}hp_xnotes WHERE ID = {$wpdb->insert_id}");
  if ($inserted_note) {
    echo "✓ Note verified in database:\n";
    echo "  - ID: {$inserted_note->ID}\n";
    echo "  - Note ID: {$inserted_note->noteID}\n";
    echo "  - Gedcom: {$inserted_note->gedcom}\n";
    echo "  - Content: {$inserted_note->note}\n";
  }

  // Clean up
  $wpdb->delete($wpdb->prefix . 'hp_xnotes', ['ID' => $wpdb->insert_id]);
  echo "✓ Test data cleaned up\n";
} else {
  echo "✗ Failed to insert note: " . $wpdb->last_error . "\n";
}

echo "\nDirect notes test completed.\n";
