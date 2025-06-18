<?php

/**
 * Final header and events focused test
 */

echo "Final Enhanced Parser Test - Header & Events Focus\n";
echo "================================================\n";

// Mock WordPress environment
if (!defined('ABSPATH')) {
  define('ABSPATH', '/tmp/');
}

// Mock WordPress $wpdb with event counting
class MockWPDB
{
  public $prefix = 'wp_';
  public $event_count = 0;

  public function prepare($query, ...$args)
  {
    return vsprintf(str_replace('%s', "'%s'", $query), $args);
  }

  public function get_var($query)
  {
    return 0; // Return 0 for max ID queries
  }

  public function insert($table, $data)
  {
    if (strpos($table, 'hp_events') !== false) {
      $this->event_count++;
      echo "EVENT #" . $this->event_count . ": {$data['persfamID']} - {$data['eventdate']} - {$data['eventplace']}\n";
    }
    return true;
  }

  public function update($table, $data, $where)
  {
    return true;
  }

  public function delete($table, $where)
  {
    return true;
  }

  public function query($query)
  {
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

  echo "\nParse completed!\n";
  echo "Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";
  echo "Total events inserted: " . $wpdb->event_count . "\n\n";

  // Display comprehensive header info
  echo "=== HEADER INFORMATION ===\n";
  $header_info = $parser->get_header_info();
  if (!empty($header_info)) {
    foreach ($header_info as $key => $value) {
      if (!empty($value)) {
        echo "  " . strtoupper($key) . ": $value\n";
      }
    }
  } else {
    echo "  No header information extracted\n";
  }

  // Display statistics
  echo "\n=== IMPORT STATISTICS ===\n";
  if (isset($result['stats'])) {
    foreach ($result['stats'] as $key => $value) {
      if ($key !== 'header_info' && !is_array($value)) {
        echo "  " . ucfirst($key) . ": $value\n";
      }
    }
  }
  // Display submitter information (get through method)
  if (method_exists($parser, 'get_submitters')) {
    $submitters = $parser->get_submitters();
  } else {
    $submitters = array(); // Not available
  }
  if (!empty($submitters)) {
    echo "\n=== SUBMITTER INFORMATION ===\n";
    foreach ($submitters as $id => $submitter) {
      echo "  Submitter ID: $id\n";
      foreach ($submitter as $field => $value) {
        if (!empty($value)) {
          echo "    " . ucfirst($field) . ": $value\n";
        }
      }
    }
  }

  // Show warnings if any
  if (isset($result['warnings']) && !empty($result['warnings'])) {
    echo "\n=== WARNINGS ===\n";
    $warning_count = count($result['warnings']);
    echo "Total warnings: $warning_count\n";
    if ($warning_count <= 10) {
      foreach ($result['warnings'] as $warning) {
        echo "  - $warning\n";
      }
    } else {
      echo "First 5 warnings:\n";
      for ($i = 0; $i < 5; $i++) {
        echo "  - " . $result['warnings'][$i] . "\n";
      }
      echo "  ... and " . ($warning_count - 5) . " more\n";
    }
  }
} catch (Exception $e) {
  echo "ERROR: " . $e->getMessage() . "\n";
  echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== TEST COMPLETED ===\n";
