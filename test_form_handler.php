<?php
// Test form submission directly
require_once '../../../wp-config.php';
require_once '../../../wp-load.php';

// Include the admin_trees.php to load the handler
require_once 'admin_trees.php';

echo "=== TESTING FORM HANDLER ===\n";

// Simulate POST data
$_POST = [
  'action' => 'heritagepress_add_newtree',
  'tree_id' => 'TEST123',
  'tree_name' => 'Test Tree Name',
  'description' => 'Test Description',
  'owner' => 'Test Owner',
  'email' => 'test@example.com',
  'address' => '123 Test St',
  'city' => 'Test City',
  'state' => 'Test State',
  'zip' => '12345',
  'country' => 'Test Country',
  'phone' => '555-1234',
  'private' => '1',
  'disallowgedcreate' => '1',
  'disallowpdf' => '0',
  '_wpnonce' => wp_create_nonce('heritagepress_newtree')
];

echo "Simulated POST data:\n";
print_r($_POST);

// Check if function exists
if (function_exists('heritagepress_handle_add_newtree_tab')) {
  echo "\n✓ Handler function exists\n";

  // Try to call it directly
  try {
    echo "Calling handler...\n";
    ob_start();
    heritagepress_handle_add_newtree_tab();
    $output = ob_get_clean();
    echo "Handler completed successfully\n";
    if ($output) {
      echo "Handler output: $output\n";
    }
  } catch (Exception $e) {
    echo "Handler error: " . $e->getMessage() . "\n";
  }
} else {
  echo "\n✗ Handler function does not exist\n";
}

// Check if record was inserted
global $wpdb;
$trees_table = $wpdb->prefix . 'hp_trees';
$record = $wpdb->get_row($wpdb->prepare("SELECT * FROM $trees_table WHERE gedcom = %s", 'TEST123'));
if ($record) {
  echo "\n✓ Record found in database:\n";
  print_r($record);

  // Clean up
  $wpdb->delete($trees_table, ['gedcom' => 'TEST123']);
  echo "✓ Test record cleaned up\n";
} else {
  echo "\n✗ No record found in database\n";
}
