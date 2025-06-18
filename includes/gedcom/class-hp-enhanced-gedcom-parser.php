<?php

/**
 * Enhanced GEDCOM Parser for HeritagePress
 *
 * This enhanced parser handles complex GEDCOM fields including:
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
  private $header_info;

  /**
   * Submitter records storage
   */
  private $submitters = array();

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
    $this->header_info = array();
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
      'end_time' => 0,
      'header_info' => array()
    );
  }

  /**
   * Parse GEDCOM file line by line (Enhanced)
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

      // Parse header information first
      $this->line_info = $this->get_line();
      if ($this->line_info['tag'] === 'HEAD') {
        $this->parse_header();
      }      // Main parsing loop
      $line_count = 0;
      $max_lines = 50000; // Safety limit

      while ($this->line_info['tag'] && $line_count < $max_lines) {
        $line_count++;

        if ($this->line_info['level'] == 0) {
          preg_match('/^@(\S+)@/', $this->line_info['tag'], $matches);
          $id = isset($matches[1]) ? $matches[1] : '';
          $rest = trim($this->line_info['rest']);

          // Skip header if we encounter it again
          if ($this->line_info['tag'] === 'HEAD') {
            $this->skip_record();
            continue;
          }

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
                $this->parse_submitter($id);
                break;
              case 'SUBN':
                // Skip submission records
                $this->skip_record();
                break;
              default:
                // Skip unknown record types or handle more loosely
                $this->warnings[] = "Unknown record type: $rest (ID: $id)";
                $this->skip_record();
                break;
            }
          }
        } else {
          // If we encounter a non-level 0 line at the top level, skip it
          // This can happen with malformed GEDCOM files or parsing errors
          $this->line_info = $this->get_line();
        }
      }

      if ($line_count >= $max_lines) {
        $this->warnings[] = "Parsing stopped at maximum line limit ($max_lines) for safety";
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
   * Parse GEDCOM header information
   */
  private function parse_header()
  {
    $header_info = array(
      'source_program' => '',
      'source_version' => '',
      'destination' => '',
      'submitter' => '',
      'gedcom_version' => '',
      'gedcom_form' => '',
      'character_set' => '',
      'date' => '',
      'time' => '',
      'filename' => '',
      'copyright' => ''
    );

    $prev_level = 0;
    $this->line_info = $this->get_line();

    while ($this->line_info['tag'] && $this->line_info['level'] > $prev_level) {
      if ($this->line_info['level'] == 1) {
        $tag = $this->line_info['tag'];
        $value = trim($this->line_info['rest']);

        switch ($tag) {
          case 'SOUR':
            $header_info['source_program'] = $value;
            // Parse all source sub-records
            $next_line = $this->get_line();
            while ($next_line['level'] == 2) {
              switch ($next_line['tag']) {
                case 'VERS':
                  $header_info['source_version'] = trim($next_line['rest']);
                  break;
                case 'NAME':
                  $header_info['source_name'] = trim($next_line['rest']);
                  break;
                case 'CORP':
                  $header_info['source_corporation'] = trim($next_line['rest']);
                  // Check for address and phone under CORP
                  $corp_line = $this->get_line();
                  while ($corp_line['level'] == 3) {
                    if ($corp_line['tag'] == 'ADDR') {
                      $header_info['source_address'] = trim($corp_line['rest']);
                      // Check for continuation lines
                      $addr_line = $this->get_line();
                      while ($addr_line['level'] == 4 && ($addr_line['tag'] == 'CONT' || $addr_line['tag'] == 'CONC')) {
                        if ($addr_line['tag'] == 'CONT') {
                          $header_info['source_address'] .= "\n" . trim($addr_line['rest']);
                        } else {
                          $header_info['source_address'] .= " " . trim($addr_line['rest']);
                        }
                        $addr_line = $this->get_line();
                      }
                      $corp_line = $addr_line;
                    } else if ($corp_line['tag'] == 'PHON') {
                      $header_info['source_phone'] = trim($corp_line['rest']);
                      $corp_line = $this->get_line();
                    } else {
                      $corp_line = $this->get_line();
                    }
                  }
                  $next_line = $corp_line;
                  continue 2; // Skip the normal increment
                default:
                  break;
              }
              $next_line = $this->get_line();
            }
            $this->line_info = $next_line;
            break;
          case 'DEST':
            $header_info['destination'] = $value;
            $this->line_info = $this->get_line();
            break;
          case 'SUBM':
            $header_info['submitter'] = $value;
            $this->line_info = $this->get_line();
            break;
          case 'GEDC':
            // Parse GEDCOM version info
            $gedc_line = $this->get_line();
            while ($gedc_line['level'] == 2) {
              if ($gedc_line['tag'] == 'VERS') {
                $header_info['gedcom_version'] = trim($gedc_line['rest']);
              } else if ($gedc_line['tag'] == 'FORM') {
                $header_info['gedcom_form'] = trim($gedc_line['rest']);
              }
              $gedc_line = $this->get_line();
            }
            $this->line_info = $gedc_line;
            break;
          case 'CHAR':
            $header_info['character_set'] = $value;
            $this->line_info = $this->get_line();
            break;
          case 'DATE':
            $header_info['date'] = $value;
            // Check for time on next line
            $next_line = $this->get_line();
            if ($next_line['level'] == 2 && $next_line['tag'] == 'TIME') {
              $header_info['time'] = trim($next_line['rest']);
              $this->line_info = $this->get_line();
            } else {
              $this->line_info = $next_line;
            }
            break;
          case 'FILE':
            $header_info['filename'] = $value;
            $this->line_info = $this->get_line();
            break;
          case 'COPR':
            $header_info['copyright'] = $value;
            $this->line_info = $this->get_line();
            break;
          default:
            // Skip unknown header tags
            $this->skip_to_next_level($this->line_info['level']);
            break;
        }
      } else {
        break;
      }
    }

    $this->stats['header_info'] = $header_info;
    $this->header_info = $header_info;
  }

  /**
   * Skip to next record at same or lower level
   */
  private function skip_to_next_level($current_level)
  {
    $this->line_info = $this->get_line();
    while ($this->line_info['tag'] && $this->line_info['level'] > $current_level) {
      $this->line_info = $this->get_line();
    }
  }

  /**
   * Parse individual record (enhanced)
   */  private function parse_individual($person_id)
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
            // Save birth event to hp_events table
            $this->save_event($person_id, 'BIRT', $birth_info);
            break;

          case 'DEAT':
            $death_info = $this->parse_event($prev_level);
            $info['deathdate'] = $death_info['date'];
            $info['deathplace'] = $death_info['place'];
            // Save death event to hp_events table
            $this->save_event($person_id, 'DEAT', $death_info);
            break;

          case 'BURI':
            $burial_info = $this->parse_event($prev_level);
            $info['burialdate'] = $burial_info['date'];
            $info['burialplace'] = $burial_info['place'];
            // Save burial event to hp_events table
            $this->save_event($person_id, 'BURI', $burial_info);
            break;
          case 'RESI':
            $residence_info = $this->parse_event($prev_level);
            // Save residence event to hp_events table
            $this->save_event($person_id, 'RESI', $residence_info);
            $this->stats['events']++;
            break;
          case 'EVEN':
          case 'EDUC':
          case 'OCCU':
          case 'RELI':
          case 'BAPM':
          case 'BARM':
          case 'BASM':
          case 'CHR':
          case 'CHRA':
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
          case 'ADOP':
          case 'SLGC':
          case 'CREM':
            // Handle individual life events according to GEDCOM 5.5.1
            $event_info = $this->parse_event($prev_level);
            $this->save_event($person_id, $tag, $event_info);
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

          case 'NOTE':
            // Handle note references for individuals (TODO: implement)
            $this->skip_sub_record($prev_level);
            break;
          default:
            // Handle unknown tags as custom events if they look like events
            // Only handle if it's a proper event (not CONT, CONC, etc.)
            if (strlen($tag) >= 3 && !in_array($tag, array('CONT', 'CONC', 'SOUR', 'NOTE', 'CHAN', 'RFN', 'AFN', 'REFN', 'RIN', 'RESN', '_UID', 'OBJE', '_PHOTO', 'FAMS', 'FAMC'))) {
              $event_info = $this->parse_event($prev_level);
              $this->save_event($person_id, $tag, $event_info);
              $this->stats['events']++;
            } else {
              // Skip non-event tags
              $this->skip_sub_record($prev_level);
            }
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
   * Parse NAME field
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
            // Save marriage event to hp_events table
            $this->save_event($family_id, 'MARR', $marriage_info);
            break;
          case 'DIV':
          case 'ANUL':
          case 'ENGA':
          case 'MARB':
          case 'MARS':
          case 'SLGS':
            // Handle family events according to GEDCOM 5.5.1
            $event_info = $this->parse_event($prev_level);
            $this->save_event($family_id, $tag, $event_info);
            $this->stats['events']++;
            break;

          default:
            // Handle unknown tags as custom family events if they look like events
            if (strlen($tag) >= 3 && !in_array($tag, array('CONT', 'CONC', 'SOUR', 'NOTE', 'CHAN', 'RFN', 'AFN', 'REFN', 'RIN', 'RESN', '_UID', 'OBJE', '_PHOTO'))) {
              $event_info = $this->parse_event($prev_level);
              $this->save_event($family_id, $tag, $event_info);
              $this->stats['events']++;
            } else {
              // Skip non-event tags
              $this->skip_sub_record($prev_level);
            }
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
   * Save event to hp_events table
   */  private function save_event($persfam_id, $event_type, $event_info)
  {
    global $wpdb;

    // Skip if we're in events only mode and this isn't an event import
    if ($this->import_options['eventsonly'] && !$this->import_options['allevents']) {
      return;
    }

    $table_name = $wpdb->prefix . 'hp_events'; // Determine if this is a family or individual event
    $is_family_event = in_array($event_type, array('MARR', 'DIV', 'ANUL', 'ENGA', 'MARB', 'MARS', 'SLGS'));
    $offset_type = $is_family_event ? 'family' : 'individual';
    $parent_tag = $is_family_event ? 'FAM' : 'INDI';

    // Apply offset for append mode
    $persfam_id = $this->apply_offset($persfam_id, $offset_type);    // Get event type ID (simplified mapping)
    $event_type_id = $this->get_event_type_id($event_type);

    $event_data = array(
      'gedcom' => $this->tree_id,
      'persfamID' => $persfam_id,
      'eventtypeID' => $event_type_id,
      'eventdate' => isset($event_info['date']) ? $event_info['date'] : '',
      'eventplace' => isset($event_info['place']) ? $event_info['place'] : '',
      'info' => isset($event_info['info']) ? $event_info['info'] : '',
      'parenttag' => $parent_tag
    );

    // Handle "do not replace" option
    if ($this->import_options['del'] === 'no') {
      $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE gedcom = %s AND persfamID = %s AND eventtypeID = %d",
        $this->tree_id,
        $persfam_id,
        $event_type_id
      ));

      if ($existing) {
        return; // Don't replace existing events
      }
    }

    $result = $wpdb->insert($table_name, $event_data);

    if ($result === false) {
      $this->warnings[] = "Failed to insert event: $event_type for $persfam_id";
    } else {
      // Save citations for this event
      if (isset($event_info['sources']) && is_array($event_info['sources'])) {
        foreach ($event_info['sources'] as $citation) {
          $this->save_citation($persfam_id, '', $citation);
        }
      }
    }
  }
  /**
   * Get event type ID from eventtypes table
   */
  private function get_event_type_id($event_type)
  {
    global $wpdb;

    // Cache event types to avoid repeated database queries
    static $event_type_cache = array();

    if (isset($event_type_cache[$event_type])) {
      return $event_type_cache[$event_type];
    }

    // Query the eventtypes table for the event type ID
    $table_name = $wpdb->prefix . 'hp_eventtypes';
    $event_type_id = $wpdb->get_var($wpdb->prepare(
      "SELECT eventtypeID FROM $table_name WHERE tag = %s",
      $event_type
    ));

    // If not found, create a new event type entry
    if (!$event_type_id) {
      // Determine if it's an individual or family event
      $family_events = array('MARR', 'DIV', 'ANUL', 'ENGA', 'MARB', 'MARS', 'MARL', 'DIVF', 'SLGS');
      $type = in_array($event_type, $family_events) ? 'F' : 'I';

      // Create display name from event type
      $display_names = array(
        'BIRT' => 'Birth',
        'DEAT' => 'Death',
        'BURI' => 'Burial',
        'CREM' => 'Cremation',
        'ADOP' => 'Adoption',
        'BAPM' => 'Baptism',
        'BARM' => 'Bar Mitzvah',
        'BASM' => 'Bas Mitzvah',
        'CHR' => 'Christening',
        'CHRA' => 'Adult Christening',
        'CONF' => 'Confirmation',
        'FCOM' => 'First Communion',
        'ORDN' => 'Ordination',
        'NATU' => 'Naturalization',
        'EMIG' => 'Emigration',
        'IMMI' => 'Immigration',
        'CENS' => 'Census',
        'PROB' => 'Probate',
        'WILL' => 'Will',
        'GRAD' => 'Graduation',
        'RETI' => 'Retirement',
        'RESI' => 'Residence',
        'OCCU' => 'Occupation',
        'EDUC' => 'Education',
        'RELI' => 'Religion',
        'EVEN' => 'Event',
        'MARR' => 'Marriage',
        'DIV' => 'Divorce',
        'ANUL' => 'Annulment',
        'ENGA' => 'Engagement',
        'MARB' => 'Marriage Banns',
        'MARS' => 'Marriage Settlement',
        'MARL' => 'Marriage License',
        'DIVF' => 'Divorce Filed',
        'SLGC' => 'LDS Sealing Child',
        'SLGS' => 'LDS Sealing Spouse'
      );

      $display = isset($display_names[$event_type]) ? $display_names[$event_type] : ucfirst(strtolower($event_type));

      // Get next available order number
      $max_order = $wpdb->get_var("SELECT MAX(ordernum) FROM $table_name");
      $ordernum = ($max_order ? $max_order : 0) + 1;

      // Insert new event type using existing table structure
      $result = $wpdb->insert(
        $table_name,
        array(
          'tag' => $event_type,
          'description' => $display,
          'display' => $display,
          'keep' => 1,
          'collapse' => 0,
          'ordernum' => $ordernum,
          'ldsevent' => (strpos($event_type, 'SLG') === 0) ? 1 : 0,
          'type' => $type
        )
      );

      if ($result !== false) {
        $event_type_id = $wpdb->insert_id;
      } else {
        // Fallback: return a default ID if insert fails
        $event_type_id = 1;
      }
    }

    // Cache the result
    $event_type_cache[$event_type] = $event_type_id;

    return $event_type_id;
  }

  /**
   * Save citation to database
   */
  private function save_citation($person_or_family_id, $event_id, $citation_info)
  {
    global $wpdb;

    $table_name = $wpdb->prefix . 'hp_citations';

    // Apply offset for append mode
    $person_or_family_id = $this->apply_offset($person_or_family_id, 'individual');
    $source_id = isset($citation_info['source_id']) ? $this->apply_offset($citation_info['source_id'], 'source') : '';

    $citation_data = array(
      'gedcom' => $this->tree_id,
      'persfamID' => $person_or_family_id,
      'eventID' => $event_id,
      'sourceID' => $source_id,
      'page' => isset($citation_info['page']) ? $citation_info['page'] : '',
      'citetext' => isset($citation_info['text']) ? $citation_info['text'] : '',
      'description' => isset($citation_info['description']) ? $citation_info['description'] : '',
      'ordernum' => 1
    );

    // Handle "do not replace" option
    if ($this->import_options['del'] === 'no') {
      $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE gedcom = %s AND persfamID = %s AND sourceID = %s",
        $this->tree_id,
        $person_or_family_id,
        $source_id
      ));

      if ($existing) {
        return; // Don't replace existing citations
      }
    }

    $result = $wpdb->insert($table_name, $citation_data);

    if ($result === false) {
      $this->warnings[] = "Failed to insert citation for $person_or_family_id";
    }
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
            // Skip any sub-records of PLAC (like MAP, LATI, LONG)
            while ($this->line_info['tag'] && $this->line_info['level'] > $event_level) {
              $this->line_info = $this->get_line();
            }
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
    }    // Determine media type from file extension
    if ($media_data['form']) {
      $media_data['mediatypeID'] = $this->get_media_type($media_data['form']);
    }

    // Insert into media table (handle duplicates for append mode)
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
   * Get header information
   */
  public function get_header_info()
  {
    return $this->header_info;
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
   * Get submitters information
   */
  public function get_submitters()
  {
    return $this->submitters;
  }

  /**
   * Display import validation summary with header info
   */
  public function display_import_summary()
  {
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "  GEDCOM IMPORT VALIDATION SUMMARY\n";
    echo str_repeat("=", 70) . "\n\n";

    // Header Information
    if (!empty($this->header_info)) {
      echo "📄 SOURCE INFORMATION:\n";
      if ($this->header_info['source_program']) {
        echo "   Program: " . $this->header_info['source_program'] . "\n";
      }
      if ($this->header_info['source_name']) {
        echo "   Name: " . $this->header_info['source_name'] . "\n";
      }
      if ($this->header_info['source_version']) {
        echo "   Version: " . $this->header_info['source_version'] . "\n";
      }
      if ($this->header_info['source_corporation']) {
        echo "   Company: " . $this->header_info['source_corporation'] . "\n";
      }
      if ($this->header_info['source_address']) {
        echo "   Address: " . str_replace("\n", "\n            ", $this->header_info['source_address']) . "\n";
      }
      if ($this->header_info['source_phone']) {
        echo "   Phone: " . $this->header_info['source_phone'] . "\n";
      }
      if ($this->header_info['date']) {
        echo "   Export Date: " . $this->header_info['date'] . "\n";
      }
      if ($this->header_info['filename']) {
        echo "   Filename: " . $this->header_info['filename'] . "\n";
      }
      if ($this->header_info['character_set']) {
        echo "   Character Set: " . $this->header_info['character_set'] . "\n";
      }
      if ($this->header_info['gedcom_version']) {
        echo "   GEDCOM Version: " . $this->header_info['gedcom_version'] . "\n";
      }
      echo "\n";
    }

    // Submitter Information
    if (!empty($this->submitters)) {
      echo "👤 SUBMITTER INFORMATION:\n";
      foreach ($this->submitters as $submitter_id => $submitter) {
        if ($submitter['name']) {
          echo "   Name: " . $submitter['name'] . "\n";
        }
        if ($submitter['address']) {
          echo "   Address: " . str_replace("\n", "\n            ", $submitter['address']) . "\n";
        }
        if ($submitter['email']) {
          echo "   Email: " . $submitter['email'] . "\n";
        }
        if ($submitter['phone']) {
          echo "   Phone: " . $submitter['phone'] . "\n";
        }
      }
      echo "\n";
    }

    // Import Statistics
    echo "📊 IMPORT RESULTS:\n";
    echo "   Individuals: " . $this->stats['individuals'] . "\n";
    echo "   Families: " . $this->stats['families'] . "\n";
    echo "   Sources: " . $this->stats['sources'] . "\n";
    echo "   Events: " . $this->stats['events'] . "\n";
    echo "   Media: " . $this->stats['media'] . "\n";
    echo "   Notes: " . $this->stats['notes'] . "\n";

    // Database verification
    global $wpdb;
    if (isset($this->tree_id)) {
      echo "\n📋 DATABASE VERIFICATION:\n";
      $tables = array(
        'People' => $wpdb->prefix . 'hp_people',
        'Families' => $wpdb->prefix . 'hp_families',
        'Sources' => $wpdb->prefix . 'hp_sources',
        'Events' => $wpdb->prefix . 'hp_events',
        'Citations' => $wpdb->prefix . 'hp_citations',
        'Repositories' => $wpdb->prefix . 'hp_repositories'
      );

      foreach ($tables as $name => $table) {
        $count = $wpdb->get_var($wpdb->prepare(
          "SELECT COUNT(*) FROM $table WHERE gedcom = %s",
          $this->tree_id
        ));
        echo "   $name: $count records\n";
      }
    }

    // Errors and warnings
    if (!empty($this->errors)) {
      echo "\n❌ ERRORS:\n";
      foreach ($this->errors as $error) {
        echo "   • $error\n";
      }
    }

    if (!empty($this->warnings)) {
      echo "\n⚠️  WARNINGS:\n";
      foreach ($this->warnings as $warning) {
        echo "   • $warning\n";
      }
    }

    // Success indicator
    if (empty($this->errors)) {
      echo "\n✅ IMPORT COMPLETED SUCCESSFULLY!\n";
    } else {
      echo "\n❌ IMPORT COMPLETED WITH ERRORS\n";
    }

    echo str_repeat("=", 70) . "\n";
  }

  /**
   * Skip current record and all its sub-records
   */
  private function skip_record()
  {
    $current_level = $this->line_info['level'];
    $this->line_info = $this->get_line();

    // Skip all lines at higher level than current record
    while ($this->line_info['tag'] && $this->line_info['level'] > $current_level) {
      $this->line_info = $this->get_line();
    }
  }

  /**
   * Initialize individual record structure
   */  private function init_individual()
  {
    return array(
      'personID' => '',
      'firstname' => '',
      'lastname' => '',
      'suffix' => '',
      'title' => '',
      'sex' => '',
      'birthdate' => '',
      'birthplace' => '',
      'deathdate' => '',
      'deathplace' => '',
      'burialdate' => '',
      'burialplace' => '',
      'famc' => '',
      'changedate' => date('Y-m-d H:i:s'),
      'living' => 1,
      'private' => 0,
      'gedcom' => $this->tree_id,
      'changedby' => 'GEDCOM Import'
    );
  }

  /**
   * Initialize family record structure
   */  private function init_family()
  {
    return array(
      'familyID' => '',
      'husband' => '',
      'wife' => '',
      'marrdate' => '',
      'marrplace' => '',
      'divdate' => '',
      'divplace' => '',
      'changedate' => date('Y-m-d H:i:s'),
      'living' => 1,
      'private' => 0,
      'gedcom' => $this->tree_id,
      'changedby' => 'GEDCOM Import'
    );
  }

  /**
   * Save individual record to database
   */  private function save_individual($person_id, $info)
  {
    global $wpdb;

    // Apply offset if in append mode
    $person_id = $this->apply_offset($person_id, 'individual');

    $info['personID'] = $person_id;
    $info['gedcom'] = $this->tree_id;

    // Remove fields that don't exist in the database table
    unset($info['spouse_families']);

    // Check if record exists for matching mode
    if ($this->import_options['del'] === 'match') {
      $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT personID FROM {$wpdb->prefix}hp_people WHERE personID = %s AND gedcom = %s",
        $person_id,
        $this->tree_id
      ));

      if ($existing) {
        $wpdb->update(
          $wpdb->prefix . 'hp_people',
          $info,
          array('personID' => $person_id, 'gedcom' => $this->tree_id)
        );
        return;
      }
    }

    // Insert new record
    $wpdb->insert($wpdb->prefix . 'hp_people', $info);
  }

  /**
   * Save family record to database
   */
  private function save_family($family_id, $info, $children = array())
  {
    global $wpdb;

    // Apply offset if in append mode
    $family_id = $this->apply_offset($family_id, 'family');
    $info['familyID'] = $family_id;
    $info['gedcom'] = $this->tree_id;

    // Apply offsets to husband/wife IDs
    if ($info['husband']) {
      $info['husband'] = $this->apply_offset($info['husband'], 'individual');
    }
    if ($info['wife']) {
      $info['wife'] = $this->apply_offset($info['wife'], 'individual');
    }

    // Check if record exists for matching mode
    if ($this->import_options['del'] === 'match') {
      $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT familyID FROM {$wpdb->prefix}hp_families WHERE familyID = %s AND gedcom = %s",
        $family_id,
        $this->tree_id
      ));

      if ($existing) {
        $wpdb->update(
          $wpdb->prefix . 'hp_families',
          $info,
          array('familyID' => $family_id, 'gedcom' => $this->tree_id)
        );
        return;
      }
    }
    // Insert new record
    $wpdb->insert($wpdb->prefix . 'hp_families', $info);
  }

  /**
   * Parse repository record
   */
  private function parse_repository($repo_id)
  {
    global $wpdb;
    $repo_data = array(
      'repoID' => $repo_id,
      'gedcom' => $this->tree_id,
      'reponame' => '',
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
          default:
            $this->line_info = $this->get_line();
            break;
        }
      } else {
        break;
      }
    }

    // Check for duplicates in append mode
    $table_name = $wpdb->prefix . 'hp_repositories';

    if ($this->import_options['del'] === 'append') {
      $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT repoID FROM $table_name WHERE gedcom = %s AND repoID = %s",
        $this->tree_id,
        $repo_id
      ));

      if ($existing) {
        return; // Skip if already exists
      }
    }

    $result = $wpdb->insert($table_name, $repo_data);

    if ($result === false) {
      $this->warnings[] = "Failed to insert repository: $repo_id";
    }
  }

  /**
   * Parse submitter record
   */
  private function parse_submitter($subm_id)
  {
    $submitter = array(
      'id' => $subm_id,
      'name' => '',
      'address' => '',
      'email' => '',
      'phone' => ''
    );

    $prev_level = 1;
    $this->line_info = $this->get_line();

    while ($this->line_info['tag'] && $this->line_info['level'] >= $prev_level) {
      if ($this->line_info['level'] == $prev_level) {
        $tag = $this->line_info['tag'];

        switch ($tag) {
          case 'NAME':
            $submitter['name'] = trim($this->line_info['rest']);
            $this->line_info = $this->get_line();
            break;
          case 'ADDR':
            $submitter['address'] = trim($this->line_info['rest']);
            // Handle continuation lines
            $this->line_info = $this->get_line();
            while ($this->line_info['tag'] && $this->line_info['level'] > $prev_level) {
              if ($this->line_info['tag'] === 'CONT') {
                $submitter['address'] .= "\n" . trim($this->line_info['rest']);
              } else if ($this->line_info['tag'] === 'CONC') {
                $submitter['address'] .= " " . trim($this->line_info['rest']);
              }
              $this->line_info = $this->get_line();
            }
            break;
          case 'EMAIL':
            $submitter['email'] = trim($this->line_info['rest']);
            $this->line_info = $this->get_line();
            break;
          case 'PHON':
            $submitter['phone'] = trim($this->line_info['rest']);
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

    // Store in submitters array for header display
    $this->submitters[$subm_id] = $submitter;
  }

  /**
   * Apply offset for append mode
   */
  private function apply_offset($id, $type)
  {
    if ($this->import_options['del'] !== 'append') {
      return $id;
    }

    switch ($type) {
      case 'individual':
        return $id + $this->save_state['ioffset'];
      case 'family':
        return $id + $this->save_state['foffset'];
      case 'source':
        return $id + $this->save_state['soffset'];
      case 'note':
        return $id + $this->save_state['noffset'];
      case 'repository':
        return $id + $this->save_state['roffset'];
      default:
        return $id;
    }
  }

  /**
   * Calculate offsets for append mode
   */
  private function calculate_offsets()
  {
    global $wpdb;

    if ($this->import_options['del'] !== 'append') {
      return;
    }

    // Calculate individual offset
    $max_id = $wpdb->get_var($wpdb->prepare(
      "SELECT MAX(personID) FROM {$wpdb->prefix}hp_people WHERE gedcom = %s",
      $this->tree_id
    ));
    $this->save_state['ioffset'] = $max_id ? $max_id : 0;

    // Calculate family offset
    $max_id = $wpdb->get_var($wpdb->prepare(
      "SELECT MAX(familyID) FROM {$wpdb->prefix}hp_families WHERE gedcom = %s",
      $this->tree_id
    ));
    $this->save_state['foffset'] = $max_id ? $max_id : 0;

    // Calculate source offset
    $max_id = $wpdb->get_var($wpdb->prepare(
      "SELECT MAX(sourceID) FROM {$wpdb->prefix}hp_sources WHERE gedcom = %s",
      $this->tree_id
    ));
    $this->save_state['soffset'] = $max_id ? $max_id : 0;

    // Calculate note offset
    $max_id = $wpdb->get_var($wpdb->prepare(
      "SELECT MAX(noteID) FROM {$wpdb->prefix}hp_xnotes WHERE gedcom = %s",
      $this->tree_id
    ));
    $this->save_state['noffset'] = $max_id ? $max_id : 0;

    // Calculate repository offset
    $max_id = $wpdb->get_var($wpdb->prepare(
      "SELECT MAX(repoID) FROM {$wpdb->prefix}hp_repositories WHERE gedcom = %s",
      $this->tree_id
    ));
    $this->save_state['roffset'] = $max_id ? $max_id : 0;
  }

  /**
   * Clear all data for tree (when del = 'yes')
   */
  private function clear_all_data()
  {
    global $wpdb;

    $tables = array(
      'hp_people',
      'hp_families',
      'hp_sources',
      'hp_events',
      'hp_citations',
      'hp_media',
      'hp_xnotes',
      'hp_notelinks',
      'hp_repositories'
    );

    foreach ($tables as $table) {
      $wpdb->delete($wpdb->prefix . $table, array('gedcom' => $this->tree_id));
    }
  }

  /**
   * Process events only mode
   */
  private function process_events_only($id, $record_type)
  {
    // In events only mode, we only process events from individuals and families
    // but skip the actual individual/family creation

    $prev_level = 1;
    $this->line_info = $this->get_line();

    while ($this->line_info['tag'] && $this->line_info['level'] >= $prev_level) {
      if ($this->line_info['level'] == $prev_level) {
        $tag = $this->line_info['tag'];

        // Process event tags
        if (in_array($tag, array('BIRT', 'DEAT', 'BURI', 'MARR', 'DIV', 'EVEN', 'RESI', 'OCCU', 'EDUC', 'RELI', 'BAPM', 'CONF', 'FCOM', 'ORDN', 'NATU', 'EMIG', 'IMMI', 'CENS', 'PROB', 'WILL', 'GRAD', 'RETI', 'ADOP'))) {
          $event_info = $this->parse_event($prev_level);
          $this->save_event($id, $tag, $event_info);
          $this->stats['events']++;
        } else {
          $this->line_info = $this->get_line();
        }
      } else {
        break;
      }
    }
  }
}
