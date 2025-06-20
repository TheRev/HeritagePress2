<?php
// Direct database test for HeritagePress trees
$host = 'localhost';
$username = 'root';
$password = 'root';
$database = 'wordpress';

$connection = new mysqli($host, $username, $password, $database);

if ($connection->connect_error) {
  die("Connection failed: " . $connection->connect_error);
}

echo "=== TESTING HERITAGEPRESS TREES TABLE ===\n\n";

// Check if table exists
$result = $connection->query("SHOW TABLES LIKE 'wp_hp_trees'");
if ($result->num_rows > 0) {
  echo "✓ Table wp_hp_trees exists\n\n";

  // Show table structure
  echo "TABLE STRUCTURE:\n";
  $result = $connection->query("DESCRIBE wp_hp_trees");
  while ($row = $result->fetch_assoc()) {
    $null = $row['Null'] == 'NO' ? ' (NOT NULL)' : '';
    $default = $row['Default'] ? " DEFAULT: {$row['Default']}" : '';
    echo "  - {$row['Field']} ({$row['Type']}){$null}{$default}\n";
  }

  echo "\n";

  // Test insert with the exact data the form would send
  echo "TESTING INSERT:\n";

  // First delete any existing test record
  $connection->query("DELETE FROM wp_hp_trees WHERE gedcom = 'TEST001'");

  $sql = "INSERT INTO wp_hp_trees (gedcom, treename, description, owner, email, address, city, state, country, zip, phone, secret, disallowgedcreate, disallowpdf, lastimportdate, importfilename) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

  $stmt = $connection->prepare($sql);
  if ($stmt) {
    $gedcom = 'TEST001';
    $treename = 'Test Tree';
    $description = 'Test Description';
    $owner = 'Test Owner';
    $email = 'test@example.com';
    $address = '123 Test St';
    $city = 'Test City';
    $state = 'Test State';
    $country = 'Test Country';
    $zip = '12345';
    $phone = '555-1234';
    $secret = 0;
    $disallowgedcreate = 0;
    $disallowpdf = 0;
    $lastimportdate = '1970-01-01 00:00:00';
    $importfilename = '';

    $stmt->bind_param('sssssssssssiisss', $gedcom, $treename, $description, $owner, $email, $address, $city, $state, $country, $zip, $phone, $secret, $disallowgedcreate, $disallowpdf, $lastimportdate, $importfilename);

    if ($stmt->execute()) {
      echo "✓ INSERT successful!\n";

      // Verify the insert
      $result = $connection->query("SELECT * FROM wp_hp_trees WHERE gedcom = 'TEST001'");
      if ($result && $row = $result->fetch_assoc()) {
        echo "✓ Record found in database:\n";
        foreach ($row as $key => $value) {
          echo "    $key: $value\n";
        }
      }

      // Clean up
      $connection->query("DELETE FROM wp_hp_trees WHERE gedcom = 'TEST001'");
      echo "✓ Test record cleaned up\n";
    } else {
      echo "✗ INSERT failed: " . $stmt->error . "\n";
    }
    $stmt->close();
  } else {
    echo "✗ Prepare failed: " . $connection->error . "\n";
  }
} else {
  echo "✗ Table wp_hp_trees does not exist\n";

  // Show all tables
  echo "\nAVAILABLE TABLES:\n";
  $result = $connection->query("SHOW TABLES");
  while ($row = $result->fetch_array()) {
    echo "  - {$row[0]}\n";
  }
}

$connection->close();
