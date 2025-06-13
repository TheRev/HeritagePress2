<?php

/**
 * HeritagePress GEDCOM Importer Class
 *
 * Handles GEDCOM file imports
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_GEDCOM_Importer
{
  /**
   * File path
   */
  private $file_path;

  /**
   * Tree ID
   */
  private $tree_id;

  /**
   * Import statistics
   */
  private $stats = array();

  /**
   * Constructor
   */
  public function __construct($file_path = '', $tree_id = 'main')
  {
    $this->file_path = $file_path;
    $this->tree_id = $tree_id;
    $this->init_stats();
  }

  /**
   * Initialize statistics
   */
  private function init_stats()
  {
    $this->stats = array(
      'persons' => 0,
      'families' => 0,
      'events' => 0,
      'sources' => 0,
      'errors' => 0
    );
  }

  /**
   * Import GEDCOM file
   */
  public function import()
  {
    if (!file_exists($this->file_path)) {
      throw new Exception('GEDCOM file not found: ' . $this->file_path);
    }

    $this->log_import_start();

    try {
      $this->parse_file();
      $this->log_import_success();
      return $this->stats;
    } catch (Exception $e) {
      $this->log_import_error($e->getMessage());
      throw $e;
    }
  }

  /**
   * Parse GEDCOM file
   */
  private function parse_file()
  {
    $handle = fopen($this->file_path, 'r');
    if (!$handle) {
      throw new Exception('Cannot open GEDCOM file for reading');
    }

    // Basic GEDCOM parsing - this is a simplified version
    // In a real implementation, you'd want a proper GEDCOM parser
    while (($line = fgets($handle)) !== false) {
      $this->parse_line(trim($line));
    }

    fclose($handle);
  }

  /**
   * Parse individual GEDCOM line
   */
  private function parse_line($line)
  {
    // This is a very basic parser - real GEDCOM parsing is much more complex
    if (empty($line)) {
      return;
    }

    // Extract level, tag, and value
    if (preg_match('/^(\d+)\s+(@\w+@)?\s*(\w+)\s*(.*)$/', $line, $matches)) {
      $level = intval($matches[1]);
      $id = isset($matches[2]) ? trim($matches[2], '@') : '';
      $tag = $matches[3];
      $value = isset($matches[4]) ? $matches[4] : '';

      // Handle different GEDCOM tags
      switch ($tag) {
        case 'INDI':
          $this->stats['persons']++;
          break;
        case 'FAM':
          $this->stats['families']++;
          break;
        case 'SOUR':
          $this->stats['sources']++;
          break;
      }
    }
  }

  /**
   * Log import start
   */
  private function log_import_start()
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hp_import_logs';

    $wpdb->insert(
      $table_name,
      array(
        'tree_id' => $this->tree_id,
        'filename' => basename($this->file_path),
        'file_size' => filesize($this->file_path),
        'status' => 'processing',
        'started_date' => current_time('mysql')
      )
    );
  }

  /**
   * Log import success
   */
  private function log_import_success()
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hp_import_logs';

    $wpdb->update(
      $table_name,
      array(
        'status' => 'completed',
        'records_imported' => array_sum($this->stats),
        'completed_date' => current_time('mysql')
      ),
      array('tree_id' => $this->tree_id, 'status' => 'processing')
    );
  }

  /**
   * Log import error
   */
  private function log_import_error($error_message)
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hp_import_logs';

    $wpdb->update(
      $table_name,
      array(
        'status' => 'failed',
        'import_notes' => $error_message,
        'completed_date' => current_time('mysql')
      ),
      array('tree_id' => $this->tree_id, 'status' => 'processing')
    );
  }

  /**
   * Get import statistics
   */
  public function get_stats()
  {
    return $this->stats;
  }
}
