<?php

/**
 * Basic GEDCOM Parser for HeritagePress
 *
 * This parser implements a genealogy database compatible import style,
 * with line by line parsing and direct database operations.
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Basic_GEDCOM_Parser
{
  private $file_handle;
  private $tree_id;
  private $stats;
  private $errors;
  private $warnings;
  private $line_info;
  private $save_state;

  public function __construct($file_path, $tree_id)
  {
    $this->tree_id = $tree_id;
    $this->init_stats();
    $this->errors = array();
    $this->warnings = array();
    $this->save_state = array(
      'del' => 'match', // Replace matching records
      'ucaselast' => false,
      'norecalc' => false,
      'media' => true,
      'branch' => '',
      'ioffset' => 0,
      'foffset' => 0,
      'soffset' => 0,
      'noffset' => 0,
      'roffset' => 0
    );

    $this->file_handle = fopen($file_path, 'r');
    if (!$this->file_handle) {
      throw new Exception('Cannot open GEDCOM file: ' . $file_path);
    }
  }

  private function init_stats()
  {
    $this->stats = array(
      'individuals' => 0,
      'families' => 0,
      'sources' => 0,
      'media' => 0,
      'notes' => 0,
      'events' => 0,
      'start_time' => time(),
      'end_time' => 0
    );
  }

  /**
   * Parse GEDCOM file line by line
   */
  public function parse()
  {
    global $wpdb;

    try {
      // Start transaction
      $wpdb->query('START TRANSACTION');

      // Main parsing loop
      $this->line_info = $this->get_line();
      while ($this->line_info['tag']) {
        if ($this->line_info['level'] == 0) {
          preg_match('/^@(\S+)@/', $this->line_info['tag'], $matches);
          $id = isset($matches[1]) ? $matches[1] : '';
          $rest = trim($this->line_info['rest']);

          switch ($rest) {
            case 'INDI':
              $this->parse_individual($id);
              break;
            case 'FAM':
              $this->parse_family($id);
              break;
            case 'SOUR':
              $this->parse_source($id);
              break;
            case 'OBJE':
              $this->parse_media($id);
              break;
            case 'NOTE':
              $this->parse_note($id);
              break;
            default:
              // Skip unknown record types
              $this->skip_record();
              break;
          }
        } else {
          $this->line_info = $this->get_line();
        }
      }

      // Commit transaction
      $wpdb->query('COMMIT');
      $this->stats['end_time'] = time();

      return array(
        'success' => true,
        'stats' => $this->stats,
        'warnings' => $this->warnings
      );
    } catch (Exception $e) {
      $wpdb->query('ROLLBACK');
      $this->errors[] = $e->getMessage();
      return array(
        'success' => false,
        'error' => $e->getMessage(),
        'errors' => $this->errors
      );
    } finally {
      if ($this->file_handle) {
        fclose($this->file_handle);
      }
    }
  }

  /**
   * Parse a GEDCOM line
   */
  private function get_line()
  {
    $line_info = array();

    if ($line = ltrim(fgets($this->file_handle, 1024))) {
      // Clean up the line
      $patterns = array('/��.*��/', '/��.*/', '/.*��/', '/@@/');
      $replacements = array('', '', '', '@');
      $line = preg_replace($patterns, $replacements, $line);

      // Parse level, tag, and content
      preg_match('/^(\d+)\s+(\S+) ?(.*)$/', $line, $matches);

      $line_info['level'] = isset($matches[1]) ? trim($matches[1]) : '';
      $line_info['tag'] = isset($matches[2]) ? trim($matches[2]) : '';
      $line_info['rest'] = isset($matches[3]) ? trim($matches[3]) : '';
    } else {
      $line_info['level'] = '';
      $line_info['tag'] = '';
      $line_info['rest'] = '';
    }

    // Skip empty lines
    if (!$line_info['tag'] && !feof($this->file_handle)) {
      $line_info = $this->get_line();
    }

    return $line_info;
  }

  /**
   * Parse individual record
   */
  private function parse_individual($person_id)
  {
    global $wpdb;

    $info = $this->init_individual();
    $prev_level = 1;

    $this->line_info = $this->get_line();

    while ($this->line_info['tag'] && $this->line_info['level'] >= $prev_level) {
      if ($this->line_info['level'] == $prev_level) {
        $tag = $this->line_info['tag'];

        switch ($tag) {
          case 'NAME':
            preg_match('/(.*)\s*\/(.*)\/\s*(.*)/', $this->line_info['rest'], $matches);
            if (!empty($matches[2])) {
              $info['lastname'] = addslashes(trim($matches[2]));
            }
            if (!empty($matches[1])) {
              $info['firstname'] = addslashes(trim($matches[1]));
            }
            $this->line_info = $this->get_line();
            break;

          case 'SEX':
            $info['sex'] = strtoupper(trim($this->line_info['rest']));
            $this->line_info = $this->get_line();
            break;

          case 'BIRT':
            $birth_info = $this->parse_event($prev_level);
            $info['birthdate'] = $birth_info['date'];
            $info['birthplace'] = $birth_info['place'];
            break;

          case 'DEAT':
            $death_info = $this->parse_event($prev_level);
            $info['deathdate'] = $death_info['date'];
            $info['deathplace'] = $death_info['place'];
            break;

          default:
            // Skip unknown tags at this level
            $this->line_info = $this->get_line();
            break;
        }
      } else {
        break;
      }
    }

    // Insert individual into database
    $this->save_individual($person_id, $info);
    $this->stats['individuals']++;
  }

  /**
   * Parse event information (date, place, etc.)
   */
  private function parse_event($prev_level)
  {
    $event = array('date' => '', 'place' => '');
    $event_level = $prev_level + 1;

    $this->line_info = $this->get_line();

    while ($this->line_info['tag'] && $this->line_info['level'] >= $event_level) {
      if ($this->line_info['level'] == $event_level) {
        switch ($this->line_info['tag']) {
          case 'DATE':
            $event['date'] = addslashes($this->line_info['rest']);
            $this->line_info = $this->get_line();
            break;
          case 'PLAC':
            $event['place'] = addslashes($this->line_info['rest']);
            $this->line_info = $this->get_line();
            break;
          default:
            $this->line_info = $this->get_line();
            break;
        }
      } else {
        break;
      }
    }

    return $event;
  }

  /**
   * Parse family record (basic implementation)
   */
  private function parse_family($family_id)
  {
    $this->skip_record(); // For now, just skip families
    $this->stats['families']++;
  }

  /**
   * Parse source record (basic implementation)
   */
  private function parse_source($source_id)
  {
    $this->skip_record(); // For now, just skip sources
    $this->stats['sources']++;
  }

  /**
   * Parse media record (basic implementation)
   */
  private function parse_media($media_id)
  {
    $this->skip_record(); // For now, just skip media
    $this->stats['media']++;
  }

  /**
   * Parse note record (basic implementation)
   */
  private function parse_note($note_id)
  {
    $this->skip_record(); // For now, just skip notes
    $this->stats['notes']++;
  }

  /**
   * Skip an entire record
   */
  private function skip_record()
  {
    $start_level = 0;
    $this->line_info = $this->get_line();

    while ($this->line_info['tag'] && $this->line_info['level'] > $start_level) {
      $this->line_info = $this->get_line();
    }
  }

  /**
   * Initialize individual data structure
   */
  private function init_individual()
  {
    return array(
      'lastname' => '',
      'firstname' => '',
      'sex' => 'U',
      'birthdate' => '',
      'birthplace' => '',
      'deathdate' => '',
      'deathplace' => '',
      'living' => 0,
      'private' => 0
    );
  }

  /**
   * Save individual to database
   */
  private function save_individual($person_id, $info)
  {
    global $wpdb;

    $table_name = $wpdb->prefix . 'hp_people';

    // Check if individual already exists
    $existing = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $table_name WHERE personID = %s AND gedcom = %s",
      $person_id,
      $this->tree_id
    ));

    if ($existing && $this->save_state['del'] == 'no') {
      return; // Don't replace existing records
    }

    $data = array(
      'personID' => $person_id,
      'lastname' => $info['lastname'],
      'firstname' => $info['firstname'],
      'sex' => $info['sex'],
      'birthdate' => $info['birthdate'],
      'birthplace' => $info['birthplace'],
      'deathdate' => $info['deathdate'],
      'deathplace' => $info['deathplace'],
      'living' => $info['living'],
      'private' => $info['private'],
      'gedcom' => $this->tree_id,
      'changedate' => current_time('mysql')
    );

    if ($existing) {
      // Update existing record
      $wpdb->update($table_name, $data, array(
        'personID' => $person_id,
        'gedcom' => $this->tree_id
      ));
    } else {
      // Insert new record
      $wpdb->insert($table_name, $data);
    }
  }

  /**
   * Get parsing statistics
   */
  public function get_stats()
  {
    return $this->stats;
  }

  /**
   * Get parsing errors
   */
  public function get_errors()
  {
    return $this->errors;
  }

  /**
   * Get parsing warnings
   */
  public function get_warnings()
  {
    return $this->warnings;
  }
}
