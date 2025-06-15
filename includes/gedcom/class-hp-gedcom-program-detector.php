<?php

/**
 * HeritagePress GEDCOM Program Detector
 *
 * Detects source program from GEDCOM files
 *
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_GEDCOM_Program_Detector
{
  /**
   * Program signatures for common genealogy software
   */
  private $program_signatures = array(
    // Family Tree Maker
    'FTM' => array(
      'patterns' => array(
        '/1\s+SOUR\s+(?:FAMILY_?TREE_?MAKER|FTM|Family Tree Maker)/i',
        '/_FREL|_MREL/i', // Common custom tags
      ),
      'name' => 'Family Tree Maker',
    ),

    // RootsMagic
    'RM' => array(
      'patterns' => array(
        '/1\s+SOUR\s+(?:ROOTS_?MAGIC|RootsMagic)/i',
        '/_UID\s+[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', // UUID pattern
      ),
      'name' => 'RootsMagic',
    ),

    // Legacy Family Tree
    'LEGACY' => array(
      'patterns' => array(
        '/1\s+SOUR\s+(?:LEGACY|Legacy)/i',
        '/_PRIM|_SCBK|_PREF/i', // Common Legacy tags
      ),
      'name' => 'Legacy Family Tree',
    ),

    // Ancestral Quest
    'AQ' => array(
      'patterns' => array(
        '/1\s+SOUR\s+(?:AQ|ANCESTRAL_?QUEST|Ancestral Quest)/i',
      ),
      'name' => 'Ancestral Quest',
    ),

    // Reunion
    'REUNION' => array(
      'patterns' => array(
        '/1\s+SOUR\s+(?:REUNION|Reunion)/i',
      ),
      'name' => 'Reunion',
    ),

    // Ancestry.com
    'ANCESTRY' => array(
      'patterns' => array(
        '/1\s+SOUR\s+(?:ANCESTRY\.COM|Ancestry)/i',
      ),
      'name' => 'Ancestry.com',
    ),

    // MyHeritage
    'MYHERITAGE' => array(
      'patterns' => array(
        '/1\s+SOUR\s+(?:MYHERITAGE|MyHeritage)/i',
        '/_UPD/i', // Common MyHeritage tag
      ),
      'name' => 'MyHeritage',
    ),
  );

  /**
   * Detect source program from GEDCOM file
   *
   * @param string $file_path Path to GEDCOM file
   * @return array Program information
   */
  public function detect_program($file_path)
  {
    $program_info = array(
      'name' => 'Unknown',
      'version' => '',
      'certainty' => 'low',
      'custom_tags' => array(),
    );

    // Open the file
    $handle = fopen($file_path, 'r');
    if (!$handle) {
      return $program_info;
    }

    // Read first 100 lines to look for program signatures
    $header_text = '';
    $line_count = 0;
    while ($line = fgets($handle) and $line_count < 100) {
      $header_text .= $line;
      $line_count++;

      // Stop when we reach end of header
      if (preg_match('/^0\s+@/', $line)) {
        break;
      }
    }

    // Close the file
    fclose($handle);

    // Extract source and version from header
    $this->extract_source_from_header($header_text, $program_info);

    // Check for specific program signatures
    $this->check_program_signatures($header_text, $program_info);

    // Scan for custom tags
    $this->scan_for_custom_tags($file_path, $program_info);

    return $program_info;
  }

  /**
   * Extract source program and version from header
   *
   * @param string $header_text Header text
   * @param array  &$program_info Program information to update
   */
  private function extract_source_from_header($header_text, &$program_info)
  {
    // Look for SOUR in header
    if (preg_match('/1\s+SOUR\s+([^\n\r]+)/i', $header_text, $matches)) {
      $source = trim($matches[1]);
      $program_info['name'] = $source;
      $program_info['certainty'] = 'medium';

      // Look for version
      if (preg_match('/2\s+VERS\s+([^\n\r]+)/i', $header_text, $vers_matches)) {
        $program_info['version'] = trim($vers_matches[1]);
      }
    }
  }

  /**
   * Check for specific program signatures
   *
   * @param string $header_text Header text
   * @param array  &$program_info Program information to update
   */
  private function check_program_signatures($header_text, &$program_info)
  {
    foreach ($this->program_signatures as $id => $signature) {
      foreach ($signature['patterns'] as $pattern) {
        if (preg_match($pattern, $header_text)) {
          $program_info['name'] = $signature['name'];
          $program_info['certainty'] = 'high';
          return;
        }
      }
    }
  }

  /**
   * Scan file for custom tags used by different programs
   *
   * @param string $file_path Path to GEDCOM file
   * @param array  &$program_info Program information to update
   */
  private function scan_for_custom_tags($file_path, &$program_info)
  {
    // Open the file
    $handle = fopen($file_path, 'r');
    if (!$handle) {
      return;
    }

    $custom_tags = array();
    $line_count = 0;
    $max_lines = 5000; // Limit scanning to avoid large files

    // Scan for custom tags (starting with _)
    while ($line = fgets($handle) and $line_count < $max_lines) {
      $line_count++;

      // Look for custom tags
      if (preg_match('/^\d+\s+(_[A-Z0-9]+)/', $line, $matches)) {
        $tag = $matches[1];
        if (!isset($custom_tags[$tag])) {
          $custom_tags[$tag] = 0;
        }
        $custom_tags[$tag]++;
      }
    }

    // Close the file
    fclose($handle);

    // Only store tags that appear multiple times
    foreach ($custom_tags as $tag => $count) {
      if ($count >= 3) {
        $program_info['custom_tags'][] = $tag;
      }
    }

    // Use custom tags to refine program detection
    $this->refine_detection_from_tags($program_info);
  }

  /**
   * Refine program detection based on custom tags
   *
   * @param array &$program_info Program information to update
   */
  private function refine_detection_from_tags(&$program_info)
  {
    // Only proceed if certainty is not already high
    if ($program_info['certainty'] === 'high') {
      return;
    }

    $tag_sets = array(
      'FTM' => array('_FREL', '_MREL', '_PLAC', '_ATTR'),
      'RM' => array('_UID', '_PRIM', '_SDATE', '_MEDI'),
      'LEGACY' => array('_PRIM', '_SCBK', '_PREF', '_PLAC'),
    );

    $scores = array();
    foreach ($tag_sets as $program => $tags) {
      $scores[$program] = 0;
      foreach ($program_info['custom_tags'] as $tag) {
        if (in_array($tag, $tags)) {
          $scores[$program]++;
        }
      }
    }

    // Find program with highest score
    $max_score = 0;
    $max_program = '';
    foreach ($scores as $program => $score) {
      if ($score > $max_score) {
        $max_score = $score;
        $max_program = $program;
      }
    }

    // Update program info if score is significant
    if ($max_score >= 2 && isset($this->program_signatures[$max_program])) {
      $program_info['name'] = $this->program_signatures[$max_program]['name'];
      $program_info['certainty'] = 'medium';
    }
  }
}
