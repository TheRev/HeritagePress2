<?php

/**
 * Direct enhanced parser test
 */

echo "Direct Enhanced Parser Test\n";
echo "==========================\n";

// Mock WordPress environment
if (!defined('ABSPATH')) {
  define('ABSPATH', '/tmp/');
}

// Mock WordPress $wpdb
class MockWPDB
{
  public $prefix = 'wp_';

  public function prepare($query, ...$args)
  {
    return vsprintf(str_replace('%s', "'%s'", $query), $args);
  }

  public function get_var($query)
  {
    echo "QUERY: $query\n";
    return 0; // Return 0 for max ID queries
  }

  public function insert($table, $data)
  {
    echo "INSERT INTO $table: " . json_encode($data) . "\n";
    return true;
  }

  public function update($table, $data, $where)
  {
    echo "UPDATE $table SET " . json_encode($data) . " WHERE " . json_encode($where) . "\n";
    return true;
  }

  public function delete($table, $where)
  {
    echo "DELETE FROM $table WHERE " . json_encode($where) . "\n";
    return true;
  }

  public function query($query)
  {
    echo "EXEC: $query\n";
    return true;
  }
}

global $wpdb;
$wpdb = new MockWPDB();

// Load the enhanced parser
require_once 'includes/gedcom/class-hp-enhanced-gedcom-parser.php';

$gedcom_file = '../../../gedcom_test_files/FTM_lyle_2025-06-17.ged';

echo "Testing file: $gedcom_file\n";
echo "File exists: " . (file_exists($gedcom_file) ? 'YES' : 'NO') . "\n";
echo "File size: " . filesize($gedcom_file) . " bytes\n\n";

try {
  // Create parser instance
  $parser = new HP_Enhanced_GEDCOM_Parser($gedcom_file, 'test_tree', array(
    'del' => 'yes',
    'ucaselast' => 0
  ));

  echo "Parser created successfully\n";

  // Run the parse
  echo "Starting parse...\n";
  $result = $parser->parse();

  echo "Parse completed!\n";
  echo "Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";

  if (isset($result['stats'])) {
    echo "\nSTATISTICS:\n";
    foreach ($result['stats'] as $key => $value) {
      if ($key === 'header_info' && is_array($value)) {
        echo "  $key:\n";
        foreach ($value as $hkey => $hvalue) {
          echo "    $hkey: $hvalue\n";
        }
      } else {
        echo "  $key: $value\n";
      }
    }
  }

  if (isset($result['warnings']) && !empty($result['warnings'])) {
    echo "\nWARNINGS:\n";
    foreach ($result['warnings'] as $warning) {
      echo "  - $warning\n";
    }
  }

  // Display header info
  $header_info = $parser->get_header_info();
  if (!empty($header_info)) {
    echo "\nHEADER INFO:\n";
    foreach ($header_info as $key => $value) {
      echo "  $key: $value\n";
    }
  }
} catch (Exception $e) {
  echo "ERROR: " . $e->getMessage() . "\n";
  echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nTest completed.\n";
