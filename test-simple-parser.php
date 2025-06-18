<?php
require_once '../../../wp-config.php';
require_once 'includes/gedcom/class-hp-enhanced-gedcom-parser.php';

global $wpdb;

echo "Testing Enhanced GEDCOM Parser - Simple Test\n";
echo "==========================================\n\n";

// Create simple test GEDCOM
$simple_gedcom = '0 HEAD
1 SOUR Test
1 GEDC
2 VERS 5.5.1
1 CHAR UTF-8

0 @I1@ INDI
1 NAME Test /Person/
1 SEX M

0 TRLR';

// Write test file
$test_file = __DIR__ . '/simple_test.ged';
file_put_contents($test_file, $simple_gedcom);
echo "Created test file: $test_file\n";

try {
  // Test parser initialization
  echo "\nTesting parser initialization...\n";
  $parser = new HP_Enhanced_GEDCOM_Parser($test_file, 'simple_test');
  echo "✓ Parser initialized successfully\n";

  // Test parsing
  echo "\nTesting GEDCOM parsing...\n";
  $result = $parser->parse();

  if ($result) {
    echo "✓ Parsing completed successfully\n";

    // Check results
    $stats = $parser->get_stats();
    echo "\nStats:\n";
    foreach ($stats as $key => $value) {
      echo "  $key: $value\n";
    }

    $errors = $parser->get_errors();
    $warnings = $parser->get_warnings();

    if (!empty($errors)) {
      echo "\nErrors:\n";
      foreach ($errors as $error) {
        echo "  - $error\n";
      }
    }

    if (!empty($warnings)) {
      echo "\nWarnings:\n";
      foreach ($warnings as $warning) {
        echo "  - $warning\n";
      }
    }
  } else {
    echo "✗ Parsing failed\n";
    $errors = $parser->get_errors();
    if (!empty($errors)) {
      foreach ($errors as $error) {
        echo "Error: $error\n";
      }
    }
  }
} catch (Exception $e) {
  echo "✗ Exception: " . $e->getMessage() . "\n";
  echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

// Clean up
if (file_exists($test_file)) {
  unlink($test_file);
  echo "\n✓ Test file cleaned up\n";
}

echo "\nSimple parser test completed.\n";
