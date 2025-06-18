<?php
require_once('c:/MAMP/htdocs/HeritagePress2/wp-config.php');
global $wpdb;

echo "=== TNG NOTES SYSTEM ANALYSIS ===\n\n";

// Check hp_xnotes table structure
$table = $wpdb->prefix . 'hp_xnotes';
echo "hp_xnotes table structure:\n";
$structure = $wpdb->get_results("DESCRIBE $table");
foreach ($structure as $column) {
  echo "  {$column->Field}: {$column->Type}\n";
}

echo "\nSample hp_xnotes data:\n";
$data = $wpdb->get_results("SELECT * FROM $table LIMIT 3");
foreach ($data as $row) {
  print_r($row);
}

// Check hp_notelinks table structure
$table = $wpdb->prefix . 'hp_notelinks';
echo "\nhp_notelinks table structure:\n";
$structure = $wpdb->get_results("DESCRIBE $table");
foreach ($structure as $column) {
  echo "  {$column->Field}: {$column->Type}\n";
}

echo "\nSample hp_notelinks data:\n";
$data = $wpdb->get_results("SELECT * FROM $table LIMIT 3");
foreach ($data as $row) {
  print_r($row);
}

echo "\n=== COMPARISON WITH TNG STRUCTURE ===\n";
echo "TNG xnotes structure should have:\n";
echo "  - ID (auto-increment)\n";
echo "  - noteID (VARCHAR(22))\n";
echo "  - gedcom (VARCHAR(20))\n";
echo "  - note (TEXT)\n\n";

echo "TNG notelinks structure should have:\n";
echo "  - ID (auto-increment)\n";
echo "  - persfamID (VARCHAR(22))\n";
echo "  - gedcom (VARCHAR(20))\n";
echo "  - xnoteID (INT)\n";
echo "  - eventID (VARCHAR(10))\n";
echo "  - ordernum (FLOAT)\n";
echo "  - secret (TINYINT)\n";
