<?php

/**
 * HeritagePress GEDCOM Importer - Main Controller Class
 *
 * This is the main controller class that coordinates the GEDCOM import process.
 * It delegates parsing, validation and processing to specialized classes.
 *
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

// Load required classes
require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/class-hp-gedcom-parser.php';
require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/class-hp-gedcom-validator.php';
require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/class-hp-gedcom-program-detector.php';
require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/class-hp-gedcom-utils.php';

// Load record handlers
require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/records/class-hp-gedcom-record-base.php';
require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/records/class-hp-gedcom-individual.php';
require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/records/class-hp-gedcom-family.php';
require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/records/class-hp-gedcom-source.php';
require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/records/class-hp-gedcom-media.php';
require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/records/class-hp-gedcom-repository.php';
require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/records/class-hp-gedcom-note.php';

class HP_Modular_GEDCOM_Importer
{
  /**
   * GEDCOM file path
   */
  private $file_path;

  /**
   * Tree ID to import into
   */
  private $tree_id;

  /**
   * Import options
   */
  private $options = array();

  /**
   * Import stats
   */
  private $stats = array();

  /**
   * Warnings generated during import
   */
  private $warnings = array();

  /**
   * Errors generated during import
   */
  private $errors = array();

  /**
   * Parser instance
   */
  private $parser;

  /**
   * Validator instance
   */
  private $validator;

  /**
   * Program detector instance
   */
  private $program_detector;

  /**
   * Utilities instance
   */
  private $utils;

  /**
   * Constructor
   *
   * @param string $file_path Path to GEDCOM file
   * @param string $tree_id   Tree ID to import into
   * @param array  $options   Import options
   */
  public function __construct($file_path = '', $tree_id = 'main', $options = array())
  {
    $this->file_path = $file_path;
    $this->tree_id = $tree_id;
    $this->options = wp_parse_args($options, array(
      'overwrite_existing' => false,
      'import_media' => true,
      'media_path' => '',
      'privacy_year_threshold' => 100,
      'batch_size' => 100,
      'memory_limit' => '256M',
    ));

    $this->init_stats();
    $this->init_components();
  }

  /**
   * Initialize statistics counters
   */
  private function init_stats()
  {
    $this->stats = array(
      'start_time' => 0,
      'end_time' => 0,
      'individuals' => 0,
      'families' => 0,
      'sources' => 0,
      'repositories' => 0,
      'notes' => 0,
      'media' => 0,
      'events' => 0,
      'total_lines' => 0,
      'processed_lines' => 0,
    );
  }

  /**
   * Initialize component classes
   */
  private function init_components()
  {
    $this->parser = new HP_GEDCOM_Parser($this);
    $this->validator = new HP_GEDCOM_Validator($this);
    $this->program_detector = new HP_GEDCOM_Program_Detector();
    $this->utils = new HP_GEDCOM_Utils();
  }

  /**
   * Main import method
   *
   * @return array Import results
   */
  public function import()
  {
    global $wpdb;

    $this->log_import_start();

    try {
      // Validate the GEDCOM file first
      if (!$this->validator->validate_gedcom_file($this->file_path)) {
        $this->log_import_error('Invalid GEDCOM file');
        return array('success' => false, 'errors' => $this->errors);
      }

      // Detect the source program
      $program_info = $this->program_detector->detect_program($this->file_path);
      $this->stats['source_program'] = $program_info['name'];
      $this->stats['source_version'] = $program_info['version'];

      // Begin transaction
      $wpdb->query('START TRANSACTION');

      // Parse and process the file
      $result = $this->parser->parse_file($this->file_path, $this->tree_id, $this->options);

      if (!$result['success']) {
        $wpdb->query('ROLLBACK');
        $this->log_import_error($result['error']);
        return array('success' => false, 'errors' => $this->errors);
      }

      // Update statistics
      $this->stats = array_merge($this->stats, $result['stats']);

      // Commit transaction
      $wpdb->query('COMMIT');

      $this->log_import_success();
      return array(
        'success' => true,
        'stats' => $this->stats,
        'warnings' => $this->warnings,
        'program_info' => $program_info
      );
    } catch (Exception $e) {
      $wpdb->query('ROLLBACK');
      $this->log_import_error($e->getMessage());
      return array('success' => false, 'errors' => $this->errors);
    }
  }

  /**
   * Add a warning message
   *
   * @param string $message Warning message
   */
  public function add_warning($message)
  {
    $this->warnings[] = $message;
  }

  /**
   * Add an error message
   *
   * @param string $message Error message
   */
  public function add_error($message)
  {
    $this->errors[] = $message;
  }

  /**
   * Log the start of the import process
   */
  private function log_import_start()
  {
    $this->stats['start_time'] = microtime(true);

    // Log the import to the database
    // Implementation depends on HP logging system
  }

  /**
   * Log successful import completion
   */
  private function log_import_success()
  {
    $this->stats['end_time'] = microtime(true);
    $this->stats['duration'] = $this->stats['end_time'] - $this->stats['start_time'];

    // Log the successful completion
    // Implementation depends on HP logging system
  }

  /**
   * Log import error
   *
   * @param string $error_message Error message
   */
  private function log_import_error($error_message)
  {
    $this->stats['end_time'] = microtime(true);
    $this->stats['duration'] = $this->stats['end_time'] - $this->stats['start_time'];

    // Log the error
    // Implementation depends on HP logging system
  }

  /**
   * Get import statistics
   *
   * @return array Import statistics
   */
  public function get_stats()
  {
    return $this->stats;
  }

  /**
   * Get import warnings
   *
   * @return array Import warnings
   */
  public function get_warnings()
  {
    return $this->warnings;
  }

  /**
   * Get import errors
   *
   * @return array Import errors
   */
  public function get_errors()
  {
    return $this->errors;
  }
}
