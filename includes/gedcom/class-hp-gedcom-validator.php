<?php

/**
 * HeritagePress GEDCOM Validator
 *
 * Validates GEDCOM file structure and content
 *
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_GEDCOM_Validator
{
  /**
   * Reference to parent importer
   * @var HP_GEDCOM_Importer_Controller
   */
  private $importer;
  /**
   * Constructor
   *
   * @param HP_GEDCOM_Importer_Controller $importer Reference to parent importer
   */
  public function __construct($importer)
  {
    $this->importer = $importer;
  }

  /**
   * Validate a GEDCOM file
   *
   * @param string $file_path Path to GEDCOM file
   * @return bool True if valid, false if not
   */
  public function validate_gedcom_file($file_path)
  {
    // Check if file exists
    if (!file_exists($file_path)) {
      $this->importer->add_error('GEDCOM file does not exist: ' . $file_path);
      return false;
    }

    // Check if file is readable
    if (!is_readable($file_path)) {
      $this->importer->add_error('GEDCOM file is not readable: ' . $file_path);
      return false;
    }

    // Check if file has content
    $file_size = filesize($file_path);
    if ($file_size === 0) {
      $this->importer->add_error('GEDCOM file is empty: ' . $file_path);
      return false;
    }

    // Check file extension
    $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    if ($extension !== 'ged') {
      $this->importer->add_warning('File does not have a .ged extension: ' . $extension);
    }

    // Open file and validate basic structure
    $handle = fopen($file_path, 'r');
    if (!$handle) {
      $this->importer->add_error('Could not open GEDCOM file: ' . $file_path);
      return false;
    }

    $is_valid = $this->validate_basic_structure($handle);
    fclose($handle);

    return $is_valid;
  }

  /**
   * Validate basic structure of GEDCOM file
   *
   * @param resource $handle File handle
   * @return bool True if valid, false if not
   */
  private function validate_basic_structure($handle)
  {
    // Read first line
    $line = fgets($handle);

    // Check for HEAD record
    if (!$line || strpos($line, '0 HEAD') !== 0) {
      $this->importer->add_error('File does not begin with a valid GEDCOM header (0 HEAD)');
      return false;
    }

    // Check for valid GEDCOM version
    $found_version = false;
    $valid_version = false;
    $line_count = 1;

    // Read the header section
    while ($line = fgets($handle)) {
      $line_count++;

      // Stop after reading 100 lines or if we hit a new level 0 record
      if ($line_count > 100 || preg_match('/^0\s+[^H]/', $line)) {
        break;
      }

      // Look for GEDC/VERS
      if (preg_match('/^1\s+GEDC/', $line)) {
        // Next few lines should contain version
        $nested_count = 0;
        while ($nested_line = fgets($handle)) {
          $line_count++;
          $nested_count++;

          if ($nested_count > 5) {
            break; // Prevent infinite loop
          }

          if (preg_match('/^2\s+VERS\s+([\d\.]+)/', $nested_line, $matches)) {
            $found_version = true;
            $version = $matches[1];

            // Check for supported version
            if ($version === '5.5' || $version === '5.5.1') {
              $valid_version = true;
            }
            break;
          }

          // Stop if we hit a new level 1 record
          if (preg_match('/^1\s+/', $nested_line)) {
            break;
          }
        }
      }
    }

    // Check version results
    if (!$found_version) {
      $this->importer->add_warning('Could not determine GEDCOM version');
    } elseif (!$valid_version) {
      $this->importer->add_warning('GEDCOM version may not be fully supported');
    }

    // Rewind the file
    rewind($handle);

    // Count basic record types to validate content
    $counts = $this->count_record_types($handle);

    // Verify we have at least some individuals
    if ($counts['INDI'] === 0) {
      $this->importer->add_error('GEDCOM file contains no individual records (INDI)');
      return false;
    }

    return true;
  }

  /**
   * Count the different record types in the GEDCOM file
   *
   * @param resource $handle File handle
   * @return array Record type counts
   */
  private function count_record_types($handle)
  {
    $counts = array(
      'INDI' => 0,
      'FAM' => 0,
      'SOUR' => 0,
      'REPO' => 0,
      'NOTE' => 0,
      'OBJE' => 0,
    );

    // Reset to beginning of file
    rewind($handle);

    // Read through file and count records
    while ($line = fgets($handle)) {
      // Look for level 0 records with an ID
      if (preg_match('/^0\s+@[^@]+@\s+(\w+)/', $line, $matches)) {
        $type = $matches[1];
        if (isset($counts[$type])) {
          $counts[$type]++;
        }
      }
    }

    return $counts;
  }

  /**
   * Get validation statistics
   *
   * @param string $file_path Path to GEDCOM file
   * @return array Validation statistics
   */
  public function get_validation_stats($file_path)
  {
    $handle = fopen($file_path, 'r');
    if (!$handle) {
      return array();
    }

    $counts = $this->count_record_types($handle);
    fclose($handle);

    // Get file info
    $file_size = filesize($file_path);
    $file_date = filemtime($file_path);

    return array(
      'file_name' => basename($file_path),
      'file_size' => $this->format_file_size($file_size),
      'file_date' => date('Y-m-d H:i:s', $file_date),
      'individuals' => $counts['INDI'],
      'families' => $counts['FAM'],
      'sources' => $counts['SOUR'],
      'repositories' => $counts['REPO'],
      'notes' => $counts['NOTE'],
      'media' => $counts['OBJE'],
    );
  }

  /**
   * Format file size for display
   *
   * @param int $bytes File size in bytes
   * @return string Formatted file size
   */
  private function format_file_size($bytes)
  {
    if ($bytes >= 1073741824) {
      return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
      return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
      return number_format($bytes / 1024, 2) . ' KB';
    } else {
      return number_format($bytes) . ' bytes';
    }
  }
}
