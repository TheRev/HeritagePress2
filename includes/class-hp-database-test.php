<?php

/**
 * HeritagePress Database Test & Validation
 *
 * This file tests the database implementation and validates table structures
 * Run this to verify all tables are properly configured
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

class HP_Database_Test
{
  private $db;
  private $results = array();

  public function __construct()
  {
    $this->db = new HP_Database();
  }

  /**
   * Run all database tests
   */
  public function run_tests()
  {
    echo "<h2>HeritagePress Database Validation</h2>\n";

    $this->test_table_structure();
    $this->test_table_counts();
    $this->test_default_data();
    $this->display_results();

    return $this->results;
  }

  /**
   * Test database table structure
   */
  private function test_table_structure()
  {
    echo "<h3>Testing Table Structure</h3>\n";

    $expected_tables = array(
      'persons',
      'families',
      'children',
      'events',
      'eventtypes',
      'sources',
      'citations',
      'places',
      'media',
      'medialinks',
      'repositories',
      'notes',
      'trees',
      'user_permissions',
      'import_logs',
      'associations',
      'addresses',
      'cemeteries',
      'albums',
      'albumlinks',
      'xnotes',
      'notelinks',
      'mostwanted',
      'mediatypes',
      'states'
    );

    foreach ($expected_tables as $table) {
      $table_name = $this->db->get_table_name($table);
      $exists = $this->check_table_exists($table_name);

      $this->results['tables'][$table] = array(
        'name' => $table_name,
        'exists' => $exists,
        'status' => $exists ? 'PASS' : 'FAIL'
      );

      echo "Table {$table_name}: " . ($exists ? "✓ EXISTS" : "✗ MISSING") . "\n";
    }
  }

  /**
   * Test table record counts
   */
  private function test_table_counts()
  {
    echo "<h3>Testing Table Counts</h3>\n";

    $count_tables = array('eventtypes', 'mediatypes', 'states', 'trees');

    foreach ($count_tables as $table) {
      $count = $this->get_table_count($table);
      $expected = $this->get_expected_count($table);

      $this->results['counts'][$table] = array(
        'actual' => $count,
        'expected' => $expected,
        'status' => ($count >= $expected) ? 'PASS' : 'FAIL'
      );

      echo "Table {$table}: {$count} records (expected: {$expected}) " .
        (($count >= $expected) ? "✓" : "✗") . "\n";
    }
  }

  /**
   * Test default data insertion
   */
  private function test_default_data()
  {
    echo "<h3>Testing Default Data</h3>\n";

    // Check for required event types
    $vital_events = $this->check_vital_events();
    echo "Vital events (Birth, Death, Marriage): " . ($vital_events ? "✓ PRESENT" : "✗ MISSING") . "\n";

    // Check for default tree
    $default_tree = $this->check_default_tree();
    echo "Default tree 'main': " . ($default_tree ? "✓ PRESENT" : "✗ MISSING") . "\n";

    // Check for media types
    $media_types = $this->check_media_types();
    echo "Media types (Photo, Document, etc.): " . ($media_types ? "✓ PRESENT" : "✗ MISSING") . "\n";
  }

  /**
   * Display test results summary
   */
  private function display_results()
  {
    echo "<h3>Test Summary</h3>\n";

    $total_tests = 0;
    $passed_tests = 0;

    // Count table tests
    if (isset($this->results['tables'])) {
      foreach ($this->results['tables'] as $test) {
        $total_tests++;
        if ($test['status'] === 'PASS') $passed_tests++;
      }
    }

    // Count other tests
    if (isset($this->results['counts'])) {
      foreach ($this->results['counts'] as $test) {
        $total_tests++;
        if ($test['status'] === 'PASS') $passed_tests++;
      }
    }

    $success_rate = ($total_tests > 0) ? round(($passed_tests / $total_tests) * 100, 1) : 0;

    echo "Tests passed: {$passed_tests}/{$total_tests} ({$success_rate}%)\n";

    if ($success_rate >= 90) {
      echo "🎉 Database implementation is excellent!\n";
    } elseif ($success_rate >= 75) {
      echo "✅ Database implementation is good.\n";
    } else {
      echo "⚠️  Database implementation needs attention.\n";
    }
  }

  /**
   * Helper methods for testing
   */
  private function check_table_exists($table_name)
  {
    global $wpdb;
    $result = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
    return ($result === $table_name);
  }

  private function get_table_count($table)
  {
    global $wpdb;
    $table_name = $this->db->get_table_name($table);
    return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
  }

  private function get_expected_count($table)
  {
    $expected = array(
      'eventtypes' => 15, // Birth, death, marriage, etc.
      'mediatypes' => 8,  // Photo, document, audio, etc.
      'states' => 50,     // US states
      'trees' => 1        // Default 'main' tree
    );

    return isset($expected[$table]) ? $expected[$table] : 0;
  }

  private function check_vital_events()
  {
    global $wpdb;
    $table_name = $this->db->get_table_name('eventtypes');
    $count = $wpdb->get_var(
      "SELECT COUNT(*) FROM {$table_name}
             WHERE event_type IN ('BIRT', 'DEAT', 'MARR') AND is_vital = 1"
    );
    return ($count >= 3);
  }

  private function check_default_tree()
  {
    global $wpdb;
    $table_name = $this->db->get_table_name('trees');
    $exists = $wpdb->get_var(
      "SELECT COUNT(*) FROM {$table_name} WHERE id = 'main'"
    );
    return ($exists > 0);
  }

  private function check_media_types()
  {
    global $wpdb;
    $table_name = $this->db->get_table_name('mediatypes');
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
    return ($count >= 4); // At least Photo, Document, Audio, Video
  }
}

// Usage example (for admin interface):
/*
if (current_user_can('manage_options')) {
    $test = new HP_Database_Test();
    $test->run_tests();
}
*/
