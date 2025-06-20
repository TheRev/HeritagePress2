<?php

/**
 * Enhanced GEDCOM Parser for HeritagePress
 *
 * This enhanced parser handles more complex GEDCOM fields including:
 * - Family records (HUSB, WIFE, CHIL)
 * - Sources and citations
 * - Events with dates and places
 * - Burial, residence, and other events
 * - Family relationships (FAMC, FAMS)
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Enhanced_GEDCOM_Parser
{
  private $file_handle;
  private $tree_id;
  private $stats;
  private $errors;
  private $warnings;
  private $line_info;
  private $save_state;
  private $import_options;

  public function __construct($file_path, $tree_id, $import_options = array())
  {
    $this->tree_id = $tree_id;
    $this->import_options = array_merge(array(
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
    ), $import_options);

    $this->init_stats();
    $this->errors = array();
    $this->warnings = array();
    $this->save_state = array(
      'del' => $this->import_options['del'],
      'ucaselast' => $this->import_options['ucaselast'],
      'norecalc' => $this->import_options['norecalc'],
      'media' => $this->import_options['importmedia'],
      'latlong' => $this->import_options['importlatlong'],
      'branch' => $this->import_options['branch'],
      'ioffset' => 0,
      'foffset' => 0,
      'soffset' => 0,
      'noffset' => 0,
      'roffset' => 0
    );

    // Calculate offsets for append mode
    if ($this->import_options['del'] === 'append') {
      $this->calculate_offsets();
    }

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
   * Parse GEDCOM file line by line (Enhanced style)
   */  public function parse()
  {
    global $wpdb;

    try {
      // Start transaction
      $wpdb->query('START TRANSACTION');

      // Handle "All current data" replacement
      if ($this->import_options['del'] === 'yes') {
        $this->clear_all_data();
      }

      // Main parsing loop
      $this->line_info = $this->get_line();
      while ($this->line_info['tag']) {
        if ($this->line_info['level'] == 0) {
          preg_match('/^@(\S+)@/', $this->line_info['tag'], $matches);
          $id = isset($matches[1]) ? $matches[1] : '';
          $rest = trim($this->line_info['rest']);

          // Handle "Events only" mode
          if ($this->import_options['eventsonly'] === 'yes') {
            // In events only mode, skip individual and family records, only process events
            if (in_array($rest, array('INDI', 'FAM'))) {
              $this->process_events_only($id, $rest);
            } else {
              $this->skip_record();
            }
          } else {
            // Normal processing
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
              case 'REPO':
                $this->parse_repository($id);
                break;
              case 'SUBM':
              case 'SUBN':
                // Skip these for now but count them
                $this->skip_record();
                break;
              default:
                // Skip unknown record types
                $this->skip_record();
                break;
            }
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
   * Parse a GEDCOM line (standard style)
   */
  private function get_line()
  {
    $line_info = array();

    if ($line = ltrim(fgets($this->file_handle, 1024))) {
      // Clean up the line (standard style)
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
   * Parse individual record (enhanced with more fields)
   */
  private function parse_individual($person_id)
  {
    global $wpdb;

    $info = $this->init_individual();
    $events = array();
    $prev_level = 1;

    $this->line_info = $this->get_line();

    while ($this->line_info['tag'] && $this->line_info['level'] >= $prev_level) {
      if ($this->line_info['level'] == $prev_level) {
        $tag = $this->line_info['tag'];

        switch ($tag) {
          case 'NAME':
            $this->parse_name($info);
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

          case 'BURI':
            $burial_info = $this->parse_event($prev_level);
            $info['burialdate'] = $burial_info['date'];
            $info['burialplace'] = $burial_info['place'];
            break;
          case 'RESI':
            $residence_info = $this->parse_event($prev_level);
            // For now, just count the event (could be saved to events table)
            $this->stats['events']++;
            break;

          case 'EVEN':
          case 'EDUC':
          case 'OCCU':
          case 'RELI':
          case 'BAPM':
          case 'CONF':
          case 'FCOM':
          case 'ORDN':
          case 'NATU':
          case 'EMIG':
          case 'IMMI':
          case 'CENS':
          case 'PROB':
          case 'WILL':
          case 'GRAD':
          case 'RETI':
            // Handle other individual events
            $event_info = $this->parse_event($prev_level);
            $this->stats['events']++;
            break;

          case 'FAMS':
            // Family as spouse - extract family ID
            preg_match('/^@(\S+)@/', $this->line_info['rest'], $matches);
            if (!empty($matches[1])) {
              if (empty($info['spouse_families'])) {
                $info['spouse_families'] = array();
              }
              $info['spouse_families'][] = $matches[1];
            }
            $this->line_info = $this->get_line();
            break;

          case 'FAMC':
            // Family as child - extract family ID
            preg_match('/^@(\S+)@/', $this->line_info['rest'], $matches);
            if (!empty($matches[1])) {
              $info['famc'] = $matches[1];
            }
            $this->skip_sub_record($prev_level);
            break;

          case 'ADOP':
          case 'SLGC':
            // Skip these events for now
            $this->skip_sub_record($prev_level);
            break;

          case 'NOTE':
            // Handle note references for individuals
            $this->handle_note_reference($person_id, 'general');
            break;

          default:
            // Skip unknown tags
            $this->line_info = $this->get_line();
            break;
        }
      } else {
        break;
      }
    }

    // Save individual to database
    $this->save_individual($person_id, $info);
    $this->stats['individuals']++;
  }

  /**
   * Parse NAME field (standard style)
   */  private function parse_name(&$info)
  {
    preg_match('/(.*)\s*\/(.*)\/\s*(.*)/', $this->line_info['rest'], $matches);

    if (!empty($matches[2])) {
      $info['lastname'] = addslashes(trim($matches[2]));
      if ($this->save_state['ucaselast']) {
        $info['lastname'] = strtoupper($info['lastname']);
      }
    }

    if (!empty($matches[1])) {
      $info['firstname'] = addslashes(trim($matches[1]));

      // Handle suffix (after surname)
      if (!empty($matches[3])) {
        $info['suffix'] = trim($matches[3]);
        if (substr($info['suffix'], 0, 1) == ',') {
          $info['suffix'] = substr($info['suffix'], 1);
        }
        $info['suffix'] = addslashes(trim($info['suffix']));
      }
    } elseif (!empty($matches[3])) {
      $info['firstname'] = addslashes(trim($matches[3]));
    } elseif (empty($matches[2])) {
      $info['firstname'] = addslashes(trim($this->line_info['rest']));
    }    // Advance to next line and handle NAME sub-fields
    $this->line_info = $this->get_line();

    // Skip NAME sub-fields (GIVN, SURN, _MARNM, etc.)
    while ($this->line_info['tag'] && $this->line_info['level'] > 1) {
      $this->line_info = $this->get_line();
    }
  }

  /**
   * Parse family record (enhanced)
   */
  private function parse_family($family_id)
  {
    global $wpdb;

    $info = $this->init_family();
    $prev_level = 1;
    $children = array();

    $this->line_info = $this->get_line();

    while ($this->line_info['tag'] && $this->line_info['level'] >= $prev_level) {
      if ($this->line_info['level'] == $prev_level) {
        $tag = $this->line_info['tag'];

        switch ($tag) {
          case 'HUSB':
            preg_match('/^@(\S+)@/', $this->line_info['rest'], $matches);
            $info['husband'] = isset($matches[1]) ? $matches[1] : '';
            $this->line_info = $this->get_line();
            break;

          case 'WIFE':
            preg_match('/^@(\S+)@/', $this->line_info['rest'], $matches);
            $info['wife'] = isset($matches[1]) ? $matches[1] : '';
            $this->line_info = $this->get_line();
            break;

          case 'CHIL':
            preg_match('/^@(\S+)@/', $this->line_info['rest'], $matches);
            if (!empty($matches[1])) {
              $children[] = $matches[1];
            }
            $this->line_info = $this->get_line();
            break;
          case 'MARR':
            $marriage_info = $this->parse_event($prev_level);
            $info['marrdate'] = $marriage_info['date'];
            $info['marrplace'] = $marriage_info['place'];
            break;
          case 'DIV':
          case 'ANUL':
          case 'ENGA':
          case 'MARB':
          case 'MARS':
          case 'SLGS':
            // Handle family events
            $event_info = $this->parse_event($prev_level);
            $this->stats['events']++;
            break;

          default:
            $this->line_info = $this->get_line();
            break;
        }
      } else {
        break;
      }
    }

    // Save family to database
    $this->save_family($family_id, $info, $children);
    $this->stats['families']++;
  }

  /**
   * Parse event information (date, place, sources, etc.)
   */
  private function parse_event($prev_level)
  {
    $event = array('date' => '', 'place' => '', 'sources' => array());
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
          case 'SOUR':
            // Parse source citation
            $source_info = $this->parse_source_citation($event_level);
            $event['sources'][] = $source_info;
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
   * Parse source citation (basic)
   */
  private function parse_source_citation($prev_level)
  {
    $citation = array('source_id' => '', 'page' => '', 'text' => '');

    preg_match('/^@(\S+)@/', $this->line_info['rest'], $matches);
    if (!empty($matches[1])) {
      $citation['source_id'] = $matches[1];
    }

    $cite_level = $prev_level + 1;
    $this->line_info = $this->get_line();

    while ($this->line_info['tag'] && $this->line_info['level'] >= $cite_level) {
      if ($this->line_info['level'] == $cite_level) {
        switch ($this->line_info['tag']) {
          case 'PAGE':
            $citation['page'] = addslashes($this->line_info['rest']);
            $this->line_info = $this->get_line();
            break;
          case 'TEXT':
            $citation['text'] = addslashes($this->line_info['rest']);
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

    return $citation;
  }

  /**
   * Skip a sub-record and all its children
   */
  private function skip_sub_record($start_level)
  {
    $this->line_info = $this->get_line();

    while ($this->line_info['tag'] && $this->line_info['level'] > $start_level) {
      $this->line_info = $this->get_line();
    }
  }
  /**
   * Parse source record (full implementation)
   */
  private function parse_source($source_id)
  {
    global $wpdb;

    $source_data = array(
      'gedcom' => $this->tree_id,
      'sourceID' => $source_id,
      'callnum' => '',
      'type' => '',
      'title' => '',
      'author' => '',
      'publisher' => '',
      'other' => '',
      'shorttitle' => '',
      'comments' => '',
      'actualtext' => '',
      'repoID' => '',
      'changedate' => date('Y-m-d H:i:s'),
      'changedby' => 'GEDCOM Import'
    );

    $prev_level = 1;
    $this->line_info = $this->get_line();

    while ($this->line_info['tag'] && $this->line_info['level'] >= $prev_level) {
      if ($this->line_info['level'] == $prev_level) {
        $tag = $this->line_info['tag'];

        switch ($tag) {
          case 'AUTH':
            $source_data['author'] = trim($this->line_info['rest']);
            $this->line_info = $this->get_line();
            break;

          case 'TITL':
            $source_data['title'] = trim($this->line_info['rest']);
            $this->line_info = $this->get_line();
            break;

          case 'PUBL':
            $source_data['publisher'] = trim($this->line_info['rest']);
            $this->line_info = $this->get_line();
            break;

          case 'REPO':
            // Extract repository ID from @REPO@ format
            if (preg_match('/^@(.+)@$/', trim($this->line_info['rest']), $matches)) {
              $source_data['repoID'] = $matches[1];
            }
            $this->line_info = $this->get_line();
            break;

          case 'NOTE':
            $source_data['comments'] = trim($this->line_info['rest']);
            // Handle continuation lines
            $this->line_info = $this->get_line();
            while ($this->line_info['tag'] && $this->line_info['level'] > $prev_level) {
              if ($this->line_info['tag'] === 'CONC') {
                $source_data['comments'] .= ' ' . trim($this->line_info['rest']);
              } elseif ($this->line_info['tag'] === 'CONT') {
                $source_data['comments'] .= "\n" . trim($this->line_info['rest']);
              }
              $this->line_info = $this->get_line();
            }
            break;

          default:
            $this->line_info = $this->get_line();
            break;
        }
      } else {
        $this->line_info = $this->get_line();
      }
    }    // Insert into sources table (handle duplicates for append mode)
    $table_name = $wpdb->prefix . 'hp_sources';

    if ($this->import_options['del'] === 'append') {
      // Check if source already exists
      $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM $table_name WHERE gedcom = %s AND sourceID = %s",
        $source_data['gedcom'],
        $source_data['sourceID']
      ));

      if ($existing) {
        // Skip if already exists in append mode
        return;
      }
    }

    $result = $wpdb->insert($table_name, $source_data);

    if ($result === false) {
      $this->warnings[] = "Failed to insert source: $source_id";
    } else {
      $this->stats['sources']++;
    }
  }
  /**
   * Parse media record (full implementation)
   */
  private function parse_media($media_id)
  {
    global $wpdb;

    $media_data = array(
      'mediatypeID' => 'photos', // Default type
      'mediakey' => $media_id,
      'gedcom' => $this->tree_id,
      'form' => '',
      'path' => '',
      'description' => '',
      'datetaken' => '',
      'placetaken' => '',
      'notes' => '',
      'owner' => '',
      'thumbpath' => '',
      'alwayson' => 0,
      'map' => '',
      'abspath' => 0,
      'status' => '',
      'showmap' => 0,
      'cemeteryID' => 0,
      'plot' => '',
      'linktocem' => 0,
      'longitude' => '',
      'latitude' => '',
      'zoom' => 0,
      'width' => 0,
      'height' => 0,
      'left_value' => 0,
      'top_value' => 0,
      'bodytext' => '',
      'usenl' => 0,
      'newwindow' => 0,
      'usecollfolder' => 0,
      'private' => 0,
      'changedate' => date('Y-m-d H:i:s'),
      'changedby' => 'GEDCOM Import'
    );

    $prev_level = 1;
    $this->line_info = $this->get_line();

    while ($this->line_info['tag'] && $this->line_info['level'] >= $prev_level) {
      if ($this->line_info['level'] == $prev_level) {
        $tag = $this->line_info['tag'];

        switch ($tag) {
          case 'FILE':
            $media_data['path'] = trim($this->line_info['rest']);
            $this->line_info = $this->get_line();
            break;

          case 'FORM':
            $media_data['form'] = trim($this->line_info['rest']);
            $this->line_info = $this->get_line();
            break;

          case 'TITL':
            $media_data['description'] = trim($this->line_info['rest']);
            $this->line_info = $this->get_line();
            break;

          case '_TEXT':
            $media_data['notes'] = trim($this->line_info['rest']);
            $this->line_info = $this->get_line();
            break;

          case '_DATE':
            $media_data['datetaken'] = trim($this->line_info['rest']);
            $this->line_info = $this->get_line();
            break;

          default:
            $this->line_info = $this->get_line();
            break;
        }
      } else {
        $this->line_info = $this->get_line();
      }
    }

    // Determine media type from file extension
    if ($media_data['form']) {
      $media_data['mediatypeID'] = $this->get_media_type($media_data['form']);
    }    // Insert into media table (handle duplicates for append mode)
    $table_name = $wpdb->prefix . 'hp_media';

    if ($this->import_options['del'] === 'append') {
      // Check if media already exists
      $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT mediaID FROM $table_name WHERE gedcom = %s AND mediakey = %s",
        $media_data['gedcom'],
        $media_data['mediakey']
      ));

      if ($existing) {
        // Skip if already exists in append mode
        return;
      }
    }

    $result = $wpdb->insert($table_name, $media_data);

    if ($result === false) {
      $this->warnings[] = "Failed to insert media: $media_id";
    } else {
      $this->stats['media']++;
    }
  }

  /**
   * Get media type from file extension
   */
  private function get_media_type($form)
  {
    $form = strtolower(trim($form));

    switch ($form) {
      case 'jpg':
      case 'jpeg':
      case 'png':
      case 'gif':
      case 'bmp':
      case 'tif':
      case 'tiff':
        return 'photos';
      case 'pdf':
      case 'doc':
      case 'docx':
      case 'txt':
        return 'documents';
      case 'mp3':
      case 'wav':
      case 'wma':
        return 'recordings';
      case 'mp4':
      case 'avi':
      case 'mov':
      case 'wmv':
        return 'videos';
      default:
        return 'photos'; // Default to photos
    }
  }

  /**
   * Parse note record (full implementation)
   */
  private function parse_note($note_id)
  {
    global $wpdb;

    $note_data = array(
      'noteID' => $note_id,
      'gedcom' => $this->tree_id,
      'note' => ''
    );

    $prev_level = 1;
    $this->line_info = $this->get_line();

    // The first line after NOTE might be the actual note content
    if ($this->line_info['level'] == 0 && !empty($this->line_info['rest'])) {
      $note_data['note'] = trim($this->line_info['rest']);
    }

    while ($this->line_info['tag'] && $this->line_info['level'] >= $prev_level) {
      if ($this->line_info['level'] == $prev_level) {
        $tag = $this->line_info['tag'];

        switch ($tag) {
          case 'CONC':
            $note_data['note'] .= ' ' . trim($this->line_info['rest']);
            $this->line_info = $this->get_line();
            break;

          case 'CONT':
            $note_data['note'] .= "\n" . trim($this->line_info['rest']);
            $this->line_info = $this->get_line();
            break;

          default:
            $this->line_info = $this->get_line();
            break;
        }
      } else {
        $this->line_info = $this->get_line();
      }
    }

    // Insert into xnotes table
    $table_name = $wpdb->prefix . 'hp_xnotes';
    $result = $wpdb->insert($table_name, $note_data);

    if ($result === false) {
      $this->warnings[] = "Failed to insert note: $note_id";
    } else {
      $this->stats['notes']++;
    }
  }

  /**
   * Parse repository record (full implementation)
   */
  private function parse_repository($repo_id)
  {
    global $wpdb;

    $repo_data = array(
      'repoID' => $repo_id,
      'reponame' => '',
      'gedcom' => $this->tree_id,
      'addressID' => 0,
      'changedate' => date('Y-m-d H:i:s'),
      'changedby' => 'GEDCOM Import'
    );

    $prev_level = 1;
    $this->line_info = $this->get_line();

    while ($this->line_info['tag'] && $this->line_info['level'] >= $prev_level) {
      if ($this->line_info['level'] == $prev_level) {
        $tag = $this->line_info['tag'];

        switch ($tag) {
          case 'NAME':
            $repo_data['reponame'] = trim($this->line_info['rest']);
            $this->line_info = $this->get_line();
            break;

          case 'ADDR':
            // For now, just skip address parsing - could be enhanced later
            $this->skip_sub_record($prev_level);
            break;

          default:
            $this->line_info = $this->get_line();
            break;
        }
      } else {
        $this->line_info = $this->get_line();
      }
    }    // Insert into repositories table (handle duplicates for append mode)
    $table_name = $wpdb->prefix . 'hp_repositories';

    if ($this->import_options['del'] === 'append') {
      // Check if repository already exists
      $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM $table_name WHERE gedcom = %s AND repoID = %s",
        $repo_data['gedcom'],
        $repo_data['repoID']
      ));

      if ($existing) {
        // Skip if already exists in append mode
        return;
      }
    }

    $result = $wpdb->insert($table_name, $repo_data);

    if ($result === false) {
      $this->warnings[] = "Failed to insert repository: $repo_id";
    }
    // Note: No stats increment for repositories as they're not counted in the original stats
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
      'suffix' => '',
      'sex' => 'U',
      'birthdate' => '',
      'birthplace' => '',
      'deathdate' => '',
      'deathplace' => '',
      'burialdate' => '',
      'burialplace' => '',
      'famc' => '',
      'spouse_families' => array(),
      'living' => 0,
      'private' => 0
    );
  }

  /**
   * Initialize family data structure
   */
  private function init_family()
  {
    return array(
      'husband' => '',
      'wife' => '',
      'marrdate' => '',
      'marrplace' => '',
      'living' => 0,
      'private' => 0
    );
  }

  /**
   * Save individual to database (enhanced)
   */  private function save_individual($person_id, $info)
  {
    global $wpdb;

    $table_name = $wpdb->prefix . 'hp_people';

    // Apply offset for append mode
    $person_id = $this->apply_offset($person_id, 'individual');

    // Check if individual already exists
    $existing = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $table_name WHERE personID = %s AND gedcom = %s",
      $person_id,
      $this->tree_id
    ));

    // Handle replacement options (standard)
    if ($existing) {
      switch ($this->save_state['del']) {
        case 'no':
          return; // Don't replace existing records
        case 'match':
          // Only replace if this is a matching record
          break;
        case 'yes':
          // Replace all current data
          break;
        case 'append':
          // Should not happen since we applied offset
          return;
      }

      // For newer only option, check dates
      if ($this->import_options['neweronly']) {
        $existing_changedate = $wpdb->get_var($wpdb->prepare(
          "SELECT changedate FROM $table_name WHERE personID = %s AND gedcom = %s",
          $person_id,
          $this->tree_id
        ));

        // Skip if existing record is newer (simplified date comparison)
        if ($existing_changedate && $existing_changedate > date('Y-m-d H:i:s', strtotime('-1 day'))) {
          return;
        }
      }
    }

    // Apply name transformations
    $lastname = $info['lastname'];
    $firstname = $info['firstname'];

    // Uppercase surnames option
    if ($this->import_options['ucaselast']) {
      $lastname = strtoupper($lastname);
    }

    $data = array(
      'personID' => $person_id,
      'lastname' => $lastname,
      'firstname' => $firstname,
      'suffix' => $info['suffix'],
      'sex' => $info['sex'],
      'birthdate' => $info['birthdate'],
      'birthplace' => $info['birthplace'],
      'deathdate' => $info['deathdate'],
      'deathplace' => $info['deathplace'],
      'burialdate' => $info['burialdate'],
      'burialplace' => $info['burialplace'],
      'famc' => $info['famc'],
      'living' => $info['living'],
      'private' => $info['private'],
      'gedcom' => $this->tree_id,
      'changedate' => date('Y-m-d H:i:s')
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

    $this->stats['individuals']++;
  }

  /**
   * Save family to database
   */  private function save_family($family_id, $info, $children)
  {
    global $wpdb;

    $families_table = $wpdb->prefix . 'hp_families';
    $children_table = $wpdb->prefix . 'hp_children';

    // Apply offset for append mode
    $family_id = $this->apply_offset($family_id, 'family');

    // Check if family already exists
    $existing = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $families_table WHERE familyID = %s AND gedcom = %s",
      $family_id,
      $this->tree_id
    ));

    // Handle replacement options (standard)
    if ($existing) {
      switch ($this->save_state['del']) {
        case 'no':
          return; // Don't replace existing records
        case 'match':
          // Only replace if this is a matching record
          break;
        case 'yes':
          // Replace all current data
          break;
        case 'append':
          // Should not happen since we applied offset
          return;
      }

      // For newer only option, check dates
      if ($this->import_options['neweronly']) {
        $existing_changedate = $wpdb->get_var($wpdb->prepare(
          "SELECT changedate FROM $families_table WHERE familyID = %s AND gedcom = %s",
          $family_id,
          $this->tree_id
        ));

        // Skip if existing record is newer (simplified date comparison)
        if ($existing_changedate && $existing_changedate > date('Y-m-d H:i:s', strtotime('-1 day'))) {
          return;
        }
      }
    }

    $family_data = array(
      'familyID' => $family_id,
      'husband' => $info['husband'],
      'wife' => $info['wife'],
      'marrdate' => $info['marrdate'],
      'marrplace' => $info['marrplace'],
      'living' => $info['living'],
      'private' => $info['private'],
      'gedcom' => $this->tree_id,
      'changedate' => date('Y-m-d H:i:s')
    );

    if ($existing) {
      // Update existing record
      $wpdb->update($families_table, $family_data, array(
        'familyID' => $family_id,
        'gedcom' => $this->tree_id
      ));

      // Delete existing children relationships
      $wpdb->delete($children_table, array(
        'familyID' => $family_id,
        'gedcom' => $this->tree_id
      ));
    } else {
      $order = 1;
      foreach ($children as $child_id) {
        $wpdb->insert($children_table, array(
          'familyID' => $family_id,
          'personID' => $child_id,
          'ordernum' => $order,
          'gedcom' => $this->tree_id
        ));
        $order++;
      }
    }

    $this->stats['families']++;
  }

  /**
   * Calculate offsets for append mode (standard)
   */
  private function calculate_offsets()
  {
    global $wpdb;

    // Calculate person ID offset
    $max_person = $wpdb->get_var($wpdb->prepare(
      "SELECT MAX(CAST(SUBSTRING(personID, 2) AS UNSIGNED)) FROM {$wpdb->prefix}hp_people WHERE gedcom = %s AND personID REGEXP '^I[0-9]+$'",
      $this->tree_id
    ));
    $this->save_state['ioffset'] = $max_person ? $max_person : 0;

    // Calculate family ID offset
    $max_family = $wpdb->get_var($wpdb->prepare(
      "SELECT MAX(CAST(SUBSTRING(familyID, 2) AS UNSIGNED)) FROM {$wpdb->prefix}hp_families WHERE gedcom = %s AND familyID REGEXP '^F[0-9]+$'",
      $this->tree_id
    ));
    $this->save_state['foffset'] = $max_family ? $max_family : 0;

    // Calculate source ID offset
    $max_source = $wpdb->get_var($wpdb->prepare(
      "SELECT MAX(CAST(SUBSTRING(sourceID, 2) AS UNSIGNED)) FROM {$wpdb->prefix}hp_sources WHERE gedcom = %s AND sourceID REGEXP '^S[0-9]+$'",
      $this->tree_id
    ));
    $this->save_state['soffset'] = $max_source ? $max_source : 0;

    // Calculate note ID offset
    $max_note = $wpdb->get_var($wpdb->prepare(
      "SELECT MAX(CAST(SUBSTRING(noteID, 2) AS UNSIGNED)) FROM {$wpdb->prefix}hp_xnotes WHERE gedcom = %s AND noteID REGEXP '^N[0-9]+$'",
      $this->tree_id
    ));
    $this->save_state['noffset'] = $max_note ? $max_note : 0;

    // Calculate media ID offset
    $max_media = $wpdb->get_var($wpdb->prepare(
      "SELECT MAX(CAST(SUBSTRING(mediaID, 2) AS UNSIGNED)) FROM {$wpdb->prefix}hp_media WHERE gedcom = %s AND mediaID REGEXP '^M[0-9]+$'",
      $this->tree_id
    ));
    $this->save_state['roffset'] = $max_media ? $max_media : 0;
  }

  /**
   * Apply offset to ID for append mode
   */
  private function apply_offset($id, $type)
  {
    if ($this->import_options['del'] !== 'append') {
      return $id;
    }

    // Extract numeric part and apply offset
    if (preg_match('/^([A-Z])(\d+)$/', $id, $matches)) {
      $prefix = $matches[1];
      $number = intval($matches[2]);

      switch ($type) {
        case 'individual':
          return $prefix . ($number + $this->save_state['ioffset']);
        case 'family':
          return $prefix . ($number + $this->save_state['foffset']);
        case 'source':
          return $prefix . ($number + $this->save_state['soffset']);
        case 'note':
          return $prefix . ($number + $this->save_state['noffset']);
        case 'media':
          return $prefix . ($number + $this->save_state['roffset']);
      }
    }

    return $id;
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

  /**
   * Clear all data for "All current data" replacement mode
   */
  private function clear_all_data()
  {
    global $wpdb;

    // Delete all records for this tree
    $tables = array(
      $wpdb->prefix . 'hp_people',
      $wpdb->prefix . 'hp_families',
      $wpdb->prefix . 'hp_children',
      $wpdb->prefix . 'hp_sources',
      $wpdb->prefix . 'hp_media',
      $wpdb->prefix . 'hp_xnotes',
      $wpdb->prefix . 'hp_events'
    );

    foreach ($tables as $table) {
      $wpdb->delete($table, array('gedcom' => $this->tree_id));
    }
  }

  /**
   * Process events only mode
   */
  private function process_events_only($id, $type)
  {
    // In events only mode, we extract and update only event information
    // This is a simplified implementation - in full genealogy software this would update
    // existing records with new event data only
    $this->skip_record(); // For now, just skip - this would need more complex logic
  }
}
