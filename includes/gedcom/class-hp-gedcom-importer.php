<?php

/**
 * HeritagePress GEDCOM Importer Controller - Main Controller Class
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

class HP_GEDCOM_Importer_Controller
{
  /**
   * WordPress functions fallback for non-WP environments
   */
  private function ensure_wp_functions()
  {
    if (!function_exists('wp_parse_args')) {
      /**
       * Merge user defined arguments into defaults array
       *
       * @param array|string $args Value to merge with $defaults
       * @param array $defaults Array that serves as the defaults
       * @return array Merged user defined values with defaults
       */
      function wp_parse_args($args, $defaults = array())
      {
        if (is_object($args)) {
          $parsed_args = get_object_vars($args);
        } elseif (is_array($args)) {
          $parsed_args = &$args;
        } else {
          parse_str($args, $parsed_args);
        }

        return array_merge($defaults, $parsed_args);
      }
    }
  }
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
   * Progress callback
   */
  private $progress_callback = null;

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
   */  public function __construct($file_path = '', $tree_id = 'main', $options = array())
  {
    $this->ensure_wp_functions();
    $this->file_path = $file_path;
    $this->tree_id = $tree_id;
    $this->options = wp_parse_args($options, array(
      'overwrite_existing' => false,
      'import_media' => true,
      'media_path' => '',
      'privacy_year_threshold' => 100,
      'batch_size' => 100,
      'memory_limit' => '256M',
      // Import options
      'del' => 'match',
      'allevents' => '',
      'eventsonly' => '',
      'ucaselast' => 0,
      'norecalc' => 0,
      'neweronly' => 0,
      'importmedia' => 0,
      'importlatlong' => 0,
      'offsetchoice' => 'auto',
      'useroffset' => 0,
      'branch' => ''
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
   * Main import method - using enhanced parser
   *
   * @return array Import results
   */
  public function import()
  {
    $this->log_import_start();

    try {
      // Basic file validation
      if (!file_exists($this->file_path)) {
        throw new Exception('GEDCOM file does not exist: ' . $this->file_path);
      }

      if (!is_readable($this->file_path)) {
        throw new Exception('GEDCOM file is not readable: ' . $this->file_path);
      }

      if (filesize($this->file_path) === 0) {
        throw new Exception('GEDCOM file is empty');
      }      // Load the enhanced parser
      require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/class-hp-enhanced-gedcom-parser.php';

      // Create parser instance with options
      $parser = new HP_Enhanced_GEDCOM_Parser($this->file_path, $this->tree_id, $this->options);

      // Parse the file
      $result = $parser->parse();

      if ($result['success']) {
        // Update our stats with parser results
        $this->stats = array_merge($this->stats, $result['stats']);
        $this->warnings = array_merge($this->warnings, $result['warnings']);

        $this->log_import_success();
        return array(
          'success' => true,
          'stats' => $this->stats,
          'warnings' => $this->warnings
        );
      } else {
        $this->errors = array_merge($this->errors, $result['errors']);
        $this->log_import_error($result['error']);
        return array(
          'success' => false,
          'error' => $result['error'],
          'errors' => $this->errors
        );
      }
    } catch (Exception $e) {
      $this->add_error($e->getMessage());
      $this->log_import_error($e->getMessage());
      return array(
        'success' => false,
        'error' => $e->getMessage(),
        'errors' => $this->errors
      );
    }
  }

  /**
   * Run the import process with UTF-8 support
   *
   * This method ensures proper handling of UTF-8 encoded GEDCOM files
   * by setting the appropriate options and handling character encoding.
   *
   * @return array Import results
   */
  public function import_with_utf8_support()
  {
    // Set UTF-8 encoding option
    $this->options['encoding'] = 'UTF-8';

    // Run the import process
    return $this->import();
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
   * Returns detailed statistics about the import process including
   * counts of records processed, errors, warnings, etc.
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

  /**
   * Configure the importer with additional options
   *
   * @param array $options Configuration options
   * @return self
   */
  public function configure($options)
  {
    $this->options = array_merge($this->options, $options);
    return $this;
  }

  /**
   * Set a progress callback
   *
   * @param callable $callback Function to call with progress updates
   * @return self
   */
  public function set_progress_callback($callback)
  {
    $this->progress_callback = $callback;
    return $this;
  }
}
