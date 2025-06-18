<?php
echo "Starting minimal test...\n";

$gedcom_file = '../../../gedcom_test_files/FTM_lyle_2025-06-17.ged';

if (!file_exists($gedcom_file)) {
  echo "File not found\n";
  exit(1);
}

echo "File found, reading header...\n";

// Read just the header manually
$handle = fopen($gedcom_file, 'r');
if ($handle) {
  $line_count = 0;
  while (($line = fgets($handle)) !== false && $line_count < 30) {
    echo sprintf("%3d: %s", $line_count + 1, rtrim($line) . "\n");
    $line_count++;
  }
  fclose($handle);
}

echo "Done.\n";
