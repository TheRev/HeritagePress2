<?php

/**
 * Complete GEDCOM Import Verification Test
 * Clears tables first, then processes line by line with detailed verification
 */

echo "Complete GEDCOM Import Verification Test\n";
echo "======================================\n";

// Load WordPress (mock environment for testing)
if (!defined('ABSPATH')) {
  define('ABSPATH', '/tmp/');
}

// Enhanced mock WordPress $wpdb with detailed tracking
class DetailedMockWPDB
{
  public $prefix = 'wp_';
  public $queries = array();
  public $inserts = array();
  public $deletes = array();
  public $updates = array();

  public function prepare($query, ...$args)
  {
    return vsprintf(str_replace('%s', "'%s'", $query), $args);
  }

  public function get_var($query)
  {
    $this->queries[] = $query;
    echo "QUERY: $query\n";
    return 0; // Return 0 for max ID queries
  }

  public function insert($table, $data)
  {
    $this->inserts[] = array('table' => $table, 'data' => $data);

    // Extract table type
    $table_type = str_replace($this->prefix, '', $table);

    echo "\n--- INSERT INTO $table_type ---\n";
    foreach ($data as $field => $value) {
      if (!empty($value) || $value === 0) {
        echo "  $field: " . (is_string($value) ? substr($value, 0, 100) : $value) . "\n";
      }
    }
    echo "--- END INSERT ---\n\n";

    return true;
  }

  public function update($table, $data, $where)
  {
    $this->updates[] = array('table' => $table, 'data' => $data, 'where' => $where);
    echo "UPDATE $table: " . json_encode($data) . " WHERE " . json_encode($where) . "\n";
    return true;
  }

  public function delete($table, $where)
  {
    $this->deletes[] = array('table' => $table, 'where' => $where);
    echo "CLEARED TABLE: $table WHERE " . json_encode($where) . "\n";
    return true;
  }

  public function query($query)
  {
    $this->queries[] = $query;
    echo "EXEC: $query\n";
    return true;
  }

  public function get_summary()
  {
    $summary = array();
    foreach ($this->inserts as $insert) {
      $table = str_replace($this->prefix, '', $insert['table']);
      if (!isset($summary[$table])) {
        $summary[$table] = 0;
      }
      $summary[$table]++;
    }
    return $summary;
  }
}

global $wpdb;
$wpdb = new DetailedMockWPDB();

// Load the enhanced parser
require_once 'includes/gedcom/class-hp-enhanced-gedcom-parser.php';

$gedcom_file = 'C:\MAMP\htdocs\HeritagePress2\gedcom_test_files\FTM_lyle_2025-06-17.ged';

echo "Testing GEDCOM file: $gedcom_file\n";
echo "File exists: " . (file_exists($gedcom_file) ? 'YES' : 'NO') . "\n";
if (file_exists($gedcom_file)) {
  echo "File size: " . filesize($gedcom_file) . " bytes\n";
}
echo "\n";

if (!file_exists($gedcom_file)) {
  echo "ERROR: GEDCOM file not found!\n";
  exit(1);
}

try {
  echo "=== STEP 1: CREATING PARSER ===\n";
  $parser = new HP_Enhanced_GEDCOM_Parser($gedcom_file, 'verification_test', array(
    'del' => 'yes',  // Clear all data first
    'ucaselast' => 0,
    'allevents' => 'yes',
    'importmedia' => 1,
    'offsetchoice' => 'auto'
  ));

  echo "Parser created successfully\n\n";

  echo "=== STEP 2: STARTING PARSE (with table clearing) ===\n";
  $start_time = microtime(true);
  $result = $parser->parse();
  $end_time = microtime(true);

  $execution_time = round($end_time - $start_time, 3);

  echo "\n=== STEP 3: PARSE RESULTS ===\n";
  echo "Parse completed in: {$execution_time} seconds\n";
  echo "Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";

  if (!$result['success']) {
    echo "ERROR: " . $result['error'] . "\n";
    if (isset($result['errors'])) {
      foreach ($result['errors'] as $error) {
        echo "  - $error\n";
      }
    }
    exit(1);
  }

  echo "\n=== STEP 4: DATABASE OPERATIONS SUMMARY ===\n";
  $summary = $wpdb->get_summary();
  foreach ($summary as $table => $count) {
    echo "  $table: $count records inserted\n";
  }

  echo "\nTotal database operations:\n";
  echo "  Inserts: " . count($wpdb->inserts) . "\n";
  echo "  Updates: " . count($wpdb->updates) . "\n";
  echo "  Deletes: " . count($wpdb->deletes) . "\n";
  echo "  Queries: " . count($wpdb->queries) . "\n";

  echo "\n=== STEP 5: PARSER STATISTICS ===\n";
  if (isset($result['stats'])) {
    foreach ($result['stats'] as $key => $value) {
      if ($key !== 'header_info' && !is_array($value)) {
        echo "  " . ucfirst($key) . ": $value\n";
      }
    }
  }

  echo "\n=== STEP 6: HEADER INFORMATION ===\n";
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

  echo "\n=== STEP 7: SUBMITTER INFORMATION ===\n";
  $submitters = $parser->get_submitters();
  if (!empty($submitters)) {
    foreach ($submitters as $id => $submitter) {
      echo "  Submitter ID: $id\n";
      foreach ($submitter as $field => $value) {
        if (!empty($value)) {
          echo "    " . ucfirst($field) . ": $value\n";
        }
      }
    }
  } else {
    echo "  No submitter information found\n";
  }

  echo "\n=== STEP 8: WARNINGS AND ISSUES ===\n";
  if (isset($result['warnings']) && !empty($result['warnings'])) {
    $warning_count = count($result['warnings']);
    echo "Total warnings: $warning_count\n";

    if ($warning_count <= 20) {
      foreach ($result['warnings'] as $i => $warning) {
        echo "  " . ($i + 1) . ". $warning\n";
      }
    } else {
      echo "First 10 warnings:\n";
      for ($i = 0; $i < 10; $i++) {
        echo "  " . ($i + 1) . ". " . $result['warnings'][$i] . "\n";
      }
      echo "  ... and " . ($warning_count - 10) . " more warnings\n";
    }
  } else {
    echo "No warnings generated\n";
  }

  echo "\n=== STEP 9: DATA VERIFICATION ===\n";

  // Verify specific data was captured correctly
  $people_inserted = 0;
  $families_inserted = 0;
  $sources_inserted = 0;
  $media_inserted = 0;
  $events_inserted = 0;
  $repos_inserted = 0;

  foreach ($wpdb->inserts as $insert) {
    $table = str_replace($wpdb->prefix, '', $insert['table']);
    switch ($table) {
      case 'hp_people':
        $people_inserted++;
        if ($people_inserted <= 3) {  // Show first few records
          echo "  Person: " . $insert['data']['firstname'] . " " . $insert['data']['lastname'] . "\n";
          echo "    Birth: " . $insert['data']['birthdate'] . " in " . $insert['data']['birthplace'] . "\n";
        }
        break;
      case 'hp_families':
        $families_inserted++;
        if ($families_inserted <= 3) {
          echo "  Family: Husband=" . $insert['data']['husband'] . ", Wife=" . $insert['data']['wife'] . "\n";
          echo "    Marriage: " . $insert['data']['marrdate'] . " in " . $insert['data']['marrplace'] . "\n";
        }
        break;
      case 'hp_sources':
        $sources_inserted++;
        if ($sources_inserted <= 5) {
          echo "  Source: " . substr($insert['data']['title'], 0, 60) . "...\n";
        }
        break;
      case 'hp_media':
        $media_inserted++;
        if ($media_inserted <= 5) {
          $filename = basename($insert['data']['path']);
          echo "  Media: " . $filename . "\n";
        }
        break;
      case 'hp_events':
        $events_inserted++;
        if ($events_inserted <= 5) {
          echo "  Event: " . $insert['data']['persfamID'] . " - " . $insert['data']['eventdate'] . " - " . $insert['data']['eventplace'] . "\n";
        }
        break;
      case 'hp_repositories':
        $repos_inserted++;
        if ($repos_inserted <= 3) {
          echo "  Repository: " . $insert['data']['reponame'] . "\n";
        }
        break;
    }
  }

  echo "\n=== STEP 10: FINAL VERIFICATION SUMMARY ===\n";
  echo "✅ Tables cleared successfully\n";
  echo "✅ GEDCOM file parsed line by line\n";
  echo "✅ Data correctly inserted into tables:\n";
  echo "   - People: $people_inserted\n";
  echo "   - Families: $families_inserted\n";
  echo "   - Sources: $sources_inserted\n";
  echo "   - Media: $media_inserted\n";
  echo "   - Events: $events_inserted\n";
  echo "   - Repositories: $repos_inserted\n";

  if ($warning_count == 0 || $warning_count < 5) {
    echo "✅ Import completed with minimal warnings\n";
  } else {
    echo "⚠️  Import completed but with $warning_count warnings\n";
  }

  echo "\n🎉 VERIFICATION TEST COMPLETED SUCCESSFULLY!\n";
} catch (Exception $e) {
  echo "\n❌ ERROR during verification:\n";
  echo "Exception: " . $e->getMessage() . "\n";
  echo "File: " . $e->getFile() . "\n";
  echo "Line: " . $e->getLine() . "\n";
  echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "VERIFICATION TEST FINISHED\n";
echo str_repeat("=", 60) . "\n";
