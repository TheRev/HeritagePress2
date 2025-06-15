<?php

/**
 * HeritagePress GEDCOM Parser
 *
 * Handles parsing of GEDCOM files and extracting structured data
 *
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_GEDCOM_Parser
{
  /**
   * Reference to parent importer
   * @var HP_GEDCOM_Importer_Controller
   */
  private $importer;

  /**
   * Current line number
   */
  private $line_number = 0;

  /**
   * File handle
   */
  private $file_handle = null;

  /**
   * Record handlers
   */
  private $record_handlers = array();

  /**
   * Current record being processed
   */
  private $current_record = null;

  /**
   * Record hierarchy stack
   */
  private $record_stack = array();

  /**
   * Constructor
   *   * @param HP_GEDCOM_Importer_Controller $importer Reference to parent importer
   */
  public function __construct($importer)
  {
    $this->importer = $importer;
    $this->init_record_handlers();
  }

  /**
   * Initialize record handlers
   */
  private function init_record_handlers()
  {
    $this->record_handlers = array(
      'INDI' => new HP_GEDCOM_Individual(),
      'FAM'  => new HP_GEDCOM_Family(),
      'SOUR' => new HP_GEDCOM_Source(),
      'REPO' => new HP_GEDCOM_Repository(),
      'NOTE' => new HP_GEDCOM_Note(),
      'OBJE' => new HP_GEDCOM_Media(),
    );
  }

  /**
   * Parse a GEDCOM file
   *
   * @param string $file_path Path to GEDCOM file
   * @param string $tree_id   Tree ID to import into
   * @param array  $options   Import options
   * @return array Processing results
   */
  public function parse_file($file_path, $tree_id, $options)
  {
    $stats = array(
      'total_lines' => 0,
      'processed_lines' => 0,
      'individuals' => 0,
      'families' => 0,
      'sources' => 0,
      'repositories' => 0,
      'notes' => 0,
      'media' => 0,
    );

    try {
      $this->file_handle = fopen($file_path, 'r');
      if (!$this->file_handle) {
        throw new Exception('Could not open GEDCOM file');
      }

      // Process header first
      $header = $this->parse_header();

      // Now process all records
      while (!feof($this->file_handle)) {
        $line = $this->read_line();
        if ($line === false) {
          break;
        }

        $stats['total_lines']++;

        $parsed = $this->parse_gedcom_line($line);
        if (!$parsed) {
          continue;
        }

        $stats['processed_lines']++;

        if ($parsed['level'] === 0) {
          // Finalize previous record if it exists
          $this->process_completed_record();

          // Start a new record
          $this->current_record = $this->init_record($parsed);
          $this->record_stack = array($this->current_record);
        } else {
          // Add to hierarchy
          $this->add_to_hierarchy($this->current_record, $parsed, $this->record_stack);
        }

        // Check memory and process in batches if needed
        $this->check_memory();
      }

      // Process the final record
      $this->process_completed_record();

      // Post-processing steps
      $this->post_process();

      fclose($this->file_handle);

      // Update stats with records processed
      $stats['individuals'] = count($this->record_handlers['INDI']->get_processed_ids());
      $stats['families'] = count($this->record_handlers['FAM']->get_processed_ids());
      $stats['sources'] = count($this->record_handlers['SOUR']->get_processed_ids());
      $stats['repositories'] = count($this->record_handlers['REPO']->get_processed_ids());
      $stats['notes'] = count($this->record_handlers['NOTE']->get_processed_ids());
      $stats['media'] = count($this->record_handlers['OBJE']->get_processed_ids());

      return array(
        'success' => true,
        'stats' => $stats,
      );
    } catch (Exception $e) {
      if ($this->file_handle) {
        fclose($this->file_handle);
      }
      return array(
        'success' => false,
        'error' => $e->getMessage(),
        'stats' => $stats,
      );
    }
  }

  /**
   * Parse the GEDCOM header
   *
   * @return array Header information
   */
  private function parse_header()
  {
    $header = array();
    $in_header = false;

    // Go to beginning of file
    rewind($this->file_handle);
    $this->line_number = 0;

    while (!feof($this->file_handle)) {
      $line = $this->read_line();
      if ($line === false) {
        break;
      }

      $parsed = $this->parse_gedcom_line($line);
      if (!$parsed) {
        continue;
      }

      // Check for header start
      if ($parsed['level'] === 0 && $parsed['tag'] === 'HEAD') {
        $in_header = true;
        $header['tag'] = 'HEAD';
        continue;
      }

      // Check for header end
      if ($parsed['level'] === 0 && $parsed['tag'] !== 'HEAD') {
        // Rewind to beginning of this record
        $this->rewind_one_line();
        break;
      }

      // Process header fields
      if ($in_header) {
        if ($parsed['level'] === 1 && $parsed['tag'] === 'SOUR') {
          $header['source'] = $parsed['value'];
        }
        if ($parsed['level'] === 1 && $parsed['tag'] === 'GEDC') {
          $header['gedc'] = array();
        }
        if ($parsed['level'] === 2 && $parsed['tag'] === 'VERS' && isset($header['gedc'])) {
          $header['gedc']['version'] = $parsed['value'];
        }
        if ($parsed['level'] === 1 && $parsed['tag'] === 'CHAR') {
          $header['charset'] = $parsed['value'];
        }
      }
    }

    return $header;
  }

  /**
   * Parse a GEDCOM line into structured data
   *
   * @param string $line GEDCOM line
   * @return array|false Parsed line or false
   */
  private function parse_gedcom_line($line)
  {
    $line = trim($line);
    if (empty($line)) {
      return false;
    }

    // Basic line pattern: LEVEL TAG [XREF] VALUE
    if (preg_match('/^(\d+)\s+(@[^@]+@)?\s*([A-Z0-9_]+)(\s+(.*))?$/', $line, $matches)) {
      $level = (int) $matches[1];
      $xref = !empty($matches[2]) ? trim($matches[2], '@') : '';
      $tag = $matches[3];
      $value = isset($matches[5]) ? trim($matches[5]) : '';

      // Check for pointer reference in value
      if (preg_match('/^@([^@]+)@$/', $value, $pointer_match)) {
        $pointer = $pointer_match[1];
        $value = '';

        return array(
          'level' => $level,
          'tag' => $tag,
          'xref' => $xref,
          'pointer' => $pointer,
          'value' => $value,
        );
      }

      return array(
        'level' => $level,
        'tag' => $tag,
        'xref' => $xref,
        'value' => $value,
      );
    }

    return false;
  }

  /**
   * Initialize a new record
   *
   * @param array $parsed Parsed GEDCOM line
   * @return array New record structure
   */
  private function init_record($parsed)
  {
    return array(
      'type' => $parsed['tag'],
      'id' => $parsed['xref'],
      'children' => array(),
    );
  }

  /**
   * Add a parsed line to the record hierarchy
   *
   * @param array $current_record Current record
   * @param array $parsed         Parsed line
   * @param array $record_stack   Record hierarchy stack
   */
  private function add_to_hierarchy(&$current_record, $parsed, &$record_stack)
  {
    // Determine where in the hierarchy this belongs
    $level = $parsed['level'];

    // Pop the stack until we're at the right level
    while (count($record_stack) > $level) {
      array_pop($record_stack);
    }

    // Get parent node
    $parent = &$record_stack[count($record_stack) - 1];

    // Create new node
    $node = array(
      'tag' => $parsed['tag'],
      'value' => isset($parsed['value']) ? $parsed['value'] : '',
      'children' => array(),
    );

    if (isset($parsed['pointer'])) {
      $node['pointer'] = $parsed['pointer'];
    }

    if (!empty($parsed['xref'])) {
      $node['id'] = $parsed['xref'];
    }

    // Add to parent's children
    $parent['children'][] = $node;

    // Add to stack for potential children
    $record_stack[] = &$parent['children'][count($parent['children']) - 1];
  }

  /**
   * Process a completed record
   */
  private function process_completed_record()
  {
    if (!$this->current_record) {
      return;
    }

    $record_type = $this->current_record['type'];
    if (isset($this->record_handlers[$record_type])) {
      $this->record_handlers[$record_type]->process($this->current_record);
    }

    $this->current_record = null;
  }

  /**
   * Check memory usage and perform cleanup if necessary
   */
  private function check_memory()
  {
    // Implementation for memory management
  }

  /**
   * Read a line from the file
   *
   * @return string|false Line or false on EOF
   */
  private function read_line()
  {
    $line = fgets($this->file_handle);
    if ($line !== false) {
      $this->line_number++;

      // Handle different character encodings
      if (function_exists('mb_detect_encoding') && function_exists('mb_convert_encoding')) {
        $encoding = mb_detect_encoding($line, 'UTF-8, ISO-8859-1, WINDOWS-1252', true);
        if ($encoding && $encoding !== 'UTF-8') {
          $line = mb_convert_encoding($line, 'UTF-8', $encoding);
        }
      }
    }

    return $line;
  }

  /**
   * Rewind to previous line
   */
  private function rewind_one_line()
  {
    if ($this->line_number > 0) {
      // Seek to beginning of current line
      $pos = ftell($this->file_handle);
      $line = $this->read_line();
      fseek($this->file_handle, $pos - strlen($line));
      $this->line_number--;
    }
  }

  /**
   * Perform post-processing steps after all records are processed
   */
  private function post_process()
  {
    foreach ($this->record_handlers as $handler) {
      $handler->finalize();
    }
  }
}
