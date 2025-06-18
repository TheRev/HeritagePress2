<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../../wp-config.php';

echo "Simple FTM File Debug\n";
echo "====================\n\n";

$gedcom_file = 'C:\MAMP\htdocs\HeritagePress2\gedcom_test_files\FTM_lyle_2025-06-17.ged';

// Check if file is readable
if (!file_exists($gedcom_file)) {
  echo "ERROR: File does not exist\n";
  exit(1);
}

if (!is_readable($gedcom_file)) {
  echo "ERROR: File is not readable\n";
  exit(1);
}

echo "File: $gedcom_file\n";
echo "Size: " . filesize($gedcom_file) . " bytes\n";

// Try to read first few lines
echo "\nFirst 20 lines of GEDCOM:\n";
echo "-------------------------\n";

$handle = fopen($gedcom_file, 'r');
if ($handle) {
  $line_count = 0;
  while (($line = fgets($handle)) !== false && $line_count < 20) {
    echo sprintf("%3d: %s", $line_count + 1, rtrim($line)) . "\n";
    $line_count++;
  }
  fclose($handle);
} else {
  echo "ERROR: Cannot open file for reading\n";
  exit(1);
}

// Count record types
echo "\nAnalyzing GEDCOM structure...\n";
echo "-----------------------------\n";

$content = file_get_contents($gedcom_file);
$lines = explode("\n", $content);

$records = [];
foreach ($lines as $line_num => $line) {
  if (preg_match('/^0\s+(@\w+@|\w+)\s+(\w+)/', $line, $matches)) {
    $type = $matches[2];
    if (!isset($records[$type])) {
      $records[$type] = 0;
    }
    $records[$type]++;
  }
}

foreach ($records as $type => $count) {
  echo "- $type: $count\n";
}

// Test basic parser loading
echo "\nTesting parser class loading...\n";
echo "------------------------------\n";

try {
  require_once 'includes/gedcom/class-hp-enhanced-gedcom-parser.php';
  echo "✓ Parser class loaded\n";

  if (class_exists('HP_Enhanced_GEDCOM_Parser')) {
    echo "✓ Parser class exists\n";

    // Try to instantiate
    $parser = new HP_Enhanced_GEDCOM_Parser($gedcom_file, 'debug_test');
    echo "✓ Parser instantiated\n";

    // Test parsing a few lines manually
    echo "\nTesting manual line reading...\n";
    $reflection = new ReflectionClass($parser);
    $fileHandleProperty = $reflection->getProperty('file_handle');
    $fileHandleProperty->setAccessible(true);
    $handle = $fileHandleProperty->getValue($parser);

    if ($handle) {
      echo "✓ File handle is valid\n";

      // Read first few lines using parser's method
      $getLineMethod = $reflection->getMethod('get_line');
      $getLineMethod->setAccessible(true);

      for ($i = 0; $i < 5; $i++) {
        $line_info = $getLineMethod->invoke($parser);
        echo "Line " . ($i + 1) . ": Level={$line_info['level']}, Tag={$line_info['tag']}, Rest={$line_info['rest']}\n";

        if (empty($line_info['tag'])) {
          break;
        }
      }
    } else {
      echo "✗ File handle is invalid\n";
    }
  } else {
    echo "✗ Parser class not found\n";
  }
} catch (Exception $e) {
  echo "✗ Exception: " . $e->getMessage() . "\n";
  echo "  File: " . $e->getFile() . "\n";
  echo "  Line: " . $e->getLine() . "\n";
}

echo "\nDebug completed.\n";
