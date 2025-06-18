<?php
$mysqli = new mysqli('localhost', 'root', 'root', 'wordpress');

echo "Checking for notes tables:\n";
$result = $mysqli->query("SHOW TABLES LIKE '%notes%'");
while ($row = $result->fetch_array()) {
  echo "Found: " . $row[0] . "\n";
}

echo "\nChecking for all hp_ tables:\n";
$result = $mysqli->query("SHOW TABLES LIKE 'wp_hp_%'");
while ($row = $result->fetch_array()) {
  echo "Found: " . $row[0] . "\n";
}
