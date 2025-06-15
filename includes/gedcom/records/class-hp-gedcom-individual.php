<?php

/**
 * HeritagePress GEDCOM Individual Record Handler
 *
 * Handles processing of GEDCOM Individual (INDI) records
 *
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_GEDCOM_Individual extends HP_GEDCOM_Record_Base
{
  /**
   * Process an individual record
   *
   * @param array $record Record data
   * @return string|false Individual ID or false on failure
   */
  public function process($record)
  {
    // Check if this is an INDI record
    if (!isset($record['type']) || $record['type'] !== 'INDI') {
      return false;
    }

    // Get the ID
    $gedcom_id = isset($record['id']) ? $record['id'] : '';
    if (empty($gedcom_id)) {
      return false;
    }

    // Check if already processed
    if (in_array($gedcom_id, $this->processed_ids)) {
      return $gedcom_id;
    }

    // Extract person data
    $person_data = $this->extract_person_data($record);

    // Insert into database
    $result = $this->insert_person($person_data);

    if ($result) {
      $this->processed_ids[] = $gedcom_id;

      // Process events and other sub-elements
      $this->process_events($gedcom_id, $record);
      $this->process_notes($gedcom_id, $record);
      $this->process_media($gedcom_id, $record);
      $this->process_sources($gedcom_id, $record);

      return $gedcom_id;
    }

    return false;
  }

  /**
   * Extract person data from record
   *
   * @param array $record Record data
   * @return array Person data
   */
  private function extract_person_data($record)
  {
    $utils = new HP_GEDCOM_Utils();

    $data = array(
      'personID' => isset($record['id']) ? $record['id'] : '',
      'gedcom' => $this->tree_id,
      'firstname' => '',
      'lastname' => '',
      'sex' => '',
      'birthdate' => '',
      'birthplace' => '',
      'deathdate' => '',
      'deathplace' => '',
      'living' => 1,
      'importID' => isset($record['id']) ? $record['id'] : '',
    );

    // Get name
    $name_node = $this->find_node($record, 'NAME');
    if ($name_node && isset($name_node['value'])) {
      $name_parts = $utils->process_gedcom_name($name_node['value']);
      $data['firstname'] = $name_parts['given'];
      $data['lastname'] = $name_parts['surname'];
    }

    // Get sex
    $sex = $this->find_value($record, 'SEX');
    if ($sex) {
      $data['sex'] = strtoupper(substr($sex, 0, 1));
    }

    // Get birth information
    $birth_node = $this->find_node($record, 'BIRT');
    if ($birth_node) {
      $date = $this->find_value($birth_node, 'DATE');
      if ($date) {
        $parsed_date = $utils->convert_gedcom_date($date);
        $data['birthdate'] = $parsed_date['date'];
      }

      $place = $this->find_value($birth_node, 'PLAC');
      if ($place) {
        $data['birthplace'] = $place;
      }
    }

    // Get death information
    $death_node = $this->find_node($record, 'DEAT');
    if ($death_node) {
      $date = $this->find_value($death_node, 'DATE');
      if ($date) {
        $parsed_date = $utils->convert_gedcom_date($date);
        $data['deathdate'] = $parsed_date['date'];
      }

      $place = $this->find_value($death_node, 'PLAC');
      if ($place) {
        $data['deathplace'] = $place;
      }

      // If there's a death record, the person is not living
      $data['living'] = 0;
    } else {
      // Determine living status based on birth date
      if (!empty($data['birthdate'])) {
        $year_only = substr($data['birthdate'], 0, 4);
        if ($year_only && is_numeric($year_only)) {
          $birth_year = (int) $year_only;
          $threshold_year = date('Y') - 100; // Assume deceased if born more than 100 years ago

          if ($birth_year < $threshold_year) {
            $data['living'] = 0;
          }
        }
      }
    }

    return $data;
  }

  /**
   * Insert person data into database
   *
   * @param array $data Person data
   * @return bool Success
   */
  private function insert_person($data)
  {
    $table = $this->db->prefix . 'hp_people';

    // Check if person already exists
    $existing = $this->db->get_var(
      $this->db->prepare(
        "SELECT personID FROM $table WHERE personID = %s AND gedcom = %s",
        $data['personID'],
        $data['gedcom']
      )
    );

    if ($existing) {
      // Update
      $result = $this->db->update($table, $data, array(
        'personID' => $data['personID'],
        'gedcom' => $data['gedcom']
      ));
    } else {
      // Insert
      $result = $this->db->insert($table, $data);
    }

    return $result !== false;
  }

  /**
   * Process events for a person
   *
   * @param string $gedcom_id Person ID
   * @param array  $record    Record data
   */
  private function process_events($gedcom_id, $record)
  {
    $utils = new HP_GEDCOM_Utils();
    $events_table = $this->db->prefix . 'hp_events';

    // Process all events (excluding birth and death which are in person record)
    foreach ($record['children'] as $child) {
      // Skip non-event nodes
      if (!isset($child['tag']) || in_array($child['tag'], array('NAME', 'SEX', 'FAMC', 'FAMS', 'NOTE', 'SOUR', 'OBJE'))) {
        continue;
      }

      // Skip birth and death which are already processed
      if ($child['tag'] === 'BIRT' || $child['tag'] === 'DEAT') {
        continue;
      }

      // Only process events
      if (!$this->is_event_tag($child['tag'])) {
        continue;
      }

      // Extract event data
      $event_date = $this->find_value($child, 'DATE');
      $event_place = $this->find_value($child, 'PLAC');

      if (empty($event_date) && empty($event_place)) {
        continue; // Skip events with no date or place
      }

      $event_data = array(
        'gedcom' => $this->tree_id,
        'persfamID' => $gedcom_id,
        'eventID' => strtolower($child['tag']),
        'eventdate' => '',
        'eventplace' => $event_place ? $event_place : '',
        'eventtag' => $child['tag'],
      );

      // Process date
      if ($event_date) {
        $parsed_date = $utils->convert_gedcom_date($event_date);
        $event_data['eventdate'] = $parsed_date['date'];
        $event_data['eventdatetr'] = $parsed_date['year'];
      }

      // Insert event
      $this->db->insert($events_table, $event_data);
    }
  }

  /**
   * Process notes for a person
   *
   * @param string $gedcom_id Person ID
   * @param array  $record    Record data
   */
  private function process_notes($gedcom_id, $record)
  {
    $note_nodes = $this->find_all($record, 'NOTE');
    $notelinks_table = $this->db->prefix . 'hp_notelinks';
    $xnotes_table = $this->db->prefix . 'hp_xnotes';

    foreach ($note_nodes as $note_node) {
      $note_id = '';
      $note_text = '';

      // Check if this is a pointer to an external note
      if (isset($note_node['pointer'])) {
        $note_id = $note_node['pointer'];
      } else {
        // This is an inline note
        $note_text = isset($note_node['value']) ? $note_node['value'] : '';

        // Add continuation lines
        if (isset($note_node['children'])) {
          foreach ($note_node['children'] as $child) {
            if ($child['tag'] === 'CONT') {
              $note_text .= "\n" . (isset($child['value']) ? $child['value'] : '');
            } elseif ($child['tag'] === 'CONC') {
              $note_text .= (isset($child['value']) ? $child['value'] : '');
            }
          }
        }

        // Generate a unique ID for this note
        $note_id = 'N' . substr(md5($note_text), 0, 8);

        // Insert into xnotes table
        $this->db->insert($xnotes_table, array(
          'gedcom' => $this->tree_id,
          'noteID' => $note_id,
          'note' => $note_text,
        ));
      }

      // Insert link
      $this->db->insert($notelinks_table, array(
        'gedcom' => $this->tree_id,
        'persfamID' => $gedcom_id,
        'noteID' => $note_id,
        'xnoteID' => $note_id,
      ));
    }
  }

  /**
   * Process media for a person
   *
   * @param string $gedcom_id Person ID
   * @param array  $record    Record data
   */
  private function process_media($gedcom_id, $record)
  {
    $media_nodes = $this->find_all($record, 'OBJE');
    // Media processing would be implemented here
  }

  /**
   * Process sources for a person
   *
   * @param string $gedcom_id Person ID
   * @param array  $record    Record data
   */
  private function process_sources($gedcom_id, $record)
  {
    $source_nodes = $this->find_all($record, 'SOUR');
    // Source processing would be implemented here
  }

  /**
   * Check if a tag represents an event
   *
   * @param string $tag Tag
   * @return bool True if event
   */
  private function is_event_tag($tag)
  {
    $event_tags = array(
      'BIRT',
      'CHR',
      'DEAT',
      'BURI',
      'CREM',
      'ADOP',
      'BAPM',
      'BARM',
      'BASM',
      'BLES',
      'CHRA',
      'CONF',
      'FCOM',
      'ORDN',
      'NATU',
      'EMIG',
      'IMMI',
      'CENS',
      'PROB',
      'WILL',
      'GRAD',
      'RETI',
      'EVEN',
    );

    // Custom events start with an underscore
    if (substr($tag, 0, 1) === '_') {
      return true;
    }

    return in_array($tag, $event_tags);
  }
}
