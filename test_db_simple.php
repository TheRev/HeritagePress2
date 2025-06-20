<?php
// Simple database test
require_once '../../../wp-config.php';

// Direct database connection using WordPress config
$connection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

if ($connection->connect_error) {
  die("Connection failed: " . $connection->connect_error);
}

echo "=== TESTING DATABASE INSERT ===\n";

$table = 'wp_hp_trees';

// Test data
$data = [
  'gedcom' => 'TEST456',
  'treename' => 'Test Tree',
  'description' => 'Test Description',
  'owner' => 'Test Owner',
  'email' => 'test@example.com',
  'address' => '123 Test St',
  'city' => 'Test City',
  'state' => 'Test State',
  'zip' => '12345',
  'country' => 'Test Country',
  'phone' => '555-1234',
  'secret' => 0,
  'disallowgedcreate' => 0,
  'disallowpdf' => 0,
  'lastimportdate' => '1970-01-01 00:00:00',
  'importfilename' => ''
];

// Delete any existing test record
$connection->query("DELETE FROM $table WHERE gedcom = 'TEST456'");

// Prepare insert statement
$sql = "INSERT INTO $table (gedcom, treename, description, owner, email, address, city, state, zip, country, phone, secret, disallowgedcreate, disallowpdf, lastimportdate, importfilename) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $connection->prepare($sql);
if ($stmt) {
  $stmt->bind_param(
    'sssssssssssiisss',
    $data['gedcom'],
    $data['treename'],
    $data['description'],
    $data['owner'],
    $data['email'],
    $data['address'],
    $data['city'],
    $data['state'],
    $data['zip'],
    $data['country'],
    $data['phone'],
    $data['secret'],
    $data['disallowgedcreate'],
    $data['disallowpdf'],
    $data['lastimportdate'],
    $data['importfilename']
  );

  if ($stmt->execute()) {
    echo "✓ Insert successful\n";

    // Verify
    $result = $connection->query("SELECT * FROM $table WHERE gedcom = 'TEST456'");
    if ($result && $row = $result->fetch_assoc()) {
      echo "✓ Record verified in database\n";
      print_r($row);
    }

    // Clean up
    $connection->query("DELETE FROM $table WHERE gedcom = 'TEST456'");
    echo "✓ Test record cleaned up\n";
  } else {
    echo "✗ Insert failed: " . $stmt->error . "\n";
  }
  $stmt->close();
} else {
  echo "✗ Prepare failed: " . $connection->error . "\n";
}

$connection->close();

// Now test through WordPress wpdb
echo "\n=== TESTING WORDPRESS WPDB ===\n";

// Bootstrap WordPress
define('WP_USE_THEMES', false);
require_once '../../../wp-load.php';

global $wpdb;
$trees_table = $wpdb->prefix . 'hp_trees';

echo "Table name: $trees_table\n";

// Delete any existing test record
$wpdb->delete($trees_table, ['gedcom' => 'TEST789']);

$insert_data = [
  'gedcom' => 'TEST789',
  'treename' => 'WordPress Test Tree',
  'description' => 'Test via wpdb',
  'owner' => 'WP Test Owner',
  'email' => 'wptest@example.com',
  'address' => '789 WP Test St',
  'city' => 'WP City',
  'state' => 'WP State',
  'zip' => '78900',
  'country' => 'WP Country',
  'phone' => '555-7890',
  'secret' => 0,
  'disallowgedcreate' => 0,
  'disallowpdf' => 0,
  'lastimportdate' => '1970-01-01 00:00:00',
  'importfilename' => ''
];

$result = $wpdb->insert($trees_table, $insert_data);

if ($result === false) {
  echo "✗ WordPress insert failed: " . $wpdb->last_error . "\n";
  echo "Last query: " . $wpdb->last_query . "\n";
} else {
  echo "✓ WordPress insert successful (affected rows: $result)\n";

  // Verify
  $record = $wpdb->get_row($wpdb->prepare("SELECT * FROM $trees_table WHERE gedcom = %s", 'TEST789'));
  if ($record) {
    echo "✓ Record verified via wpdb\n";
    print_r($record);
  }

  // Clean up
  $wpdb->delete($trees_table, ['gedcom' => 'TEST789']);
  echo "✓ WordPress test record cleaned up\n";
}
