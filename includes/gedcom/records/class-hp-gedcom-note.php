<?php

/**
 * HeritagePress GEDCOM Note Record Handler
 *
 * Handles processing of GEDCOM Note (NOTE) records
 *
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_GEDCOM_Note extends HP_GEDCOM_Record_Base
{
  /**
   * Process a note record
   *
   * @param array $record Record data
   * @return string|false Note ID or false on failure
   */
  public function process($record)
  {
    // Check if this is a NOTE record
    if (!isset($record['type']) || $record['type'] !== 'NOTE') {
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

    // Extract note data
    $note_data = $this->extract_note_data($record);

    // Insert into database
    $result = $this->insert_note($note_data);

    if ($result) {
      $this->processed_ids[] = $gedcom_id;

      return $gedcom_id;
    }

    return false;
  }

  /**
   * Extract note data from GEDCOM record
   *
   * @param array $record Record data
   * @return array Note data
   */
  private function extract_note_data($record)
  {
    $note_data = array(
      'gedcom_id' => isset($record['id']) ? $record['id'] : '',
      'tree_id' => $this->tree_id,
      'note_text' => '',
      'entity_id' => '',
      'entity_type' => 'global', // This is a global note
      'import_timestamp' => time(),
    );

    // Get the main note text
    if (isset($record['value'])) {
      $note_data['note_text'] = $record['value'];
    }

    // Check for continuation lines
    if (!empty($record['children'])) {
      foreach ($record['children'] as $child) {
        if (isset($child['tag']) && $child['tag'] === 'CONT' && isset($child['value'])) {
          $note_data['note_text'] .= "\n" . $child['value'];
        } else if (isset($child['tag']) && $child['tag'] === 'CONC' && isset($child['value'])) {
          $note_data['note_text'] .= $child['value'];
        }
      }
    }

    return $note_data;
  }

  /**
   * Insert note record into database
   *
   * @param array $note_data Note data
   * @return string|false Note ID or false on failure
   */
  private function insert_note($note_data)
  {
    // Ensure we have a table name with proper prefix
    $notes_table = $this->db->prefix . 'hp_notes';

    // Check if note already exists
    $existing_note = $this->db->get_row(
      $this->db->prepare(
        "SELECT * FROM $notes_table WHERE gedcom_id = %s AND tree_id = %s AND entity_type = 'global'",
        $note_data['gedcom_id'],
        $note_data['tree_id']
      )
    );

    if ($existing_note) {
      // Update existing record
      $this->db->update(
        $notes_table,
        $note_data,
        array(
          'gedcom_id' => $note_data['gedcom_id'],
          'tree_id' => $note_data['tree_id'],
          'entity_type' => 'global'
        )
      );

      return $note_data['gedcom_id'];
    } else {
      // Insert new record
      $result = $this->db->insert($notes_table, $note_data);

      if ($result) {
        return $note_data['gedcom_id'];
      }
    }

    return false;
  }

  /**
   * Process inline note (not a separate record)
   *
   * @param string $entity_id ID of entity the note belongs to
   * @param string $entity_type Type of entity (individual, family, etc.)
   * @param array $note_record Note record or sub-record
   * @return int|false Database ID or false on failure
   */
  public function process_inline_note($entity_id, $entity_type, $note_record)
  {
    if (empty($entity_id) || empty($entity_type) || empty($note_record)) {
      return false;
    }

    $note_text = '';

    // Get the main note text
    if (isset($note_record['value'])) {
      $note_text = $note_record['value'];
    }

    // Check for continuation lines
    if (!empty($note_record['children'])) {
      foreach ($note_record['children'] as $child) {
        if (isset($child['tag']) && $child['tag'] === 'CONT' && isset($child['value'])) {
          $note_text .= "\n" . $child['value'];
        } else if (isset($child['tag']) && $child['tag'] === 'CONC' && isset($child['value'])) {
          $note_text .= $child['value'];
        }
      }
    }

    if (empty($note_text)) {
      return false;
    }

    // Prepare note data
    $note_data = array(
      'entity_id' => $entity_id,
      'entity_type' => $entity_type,
      'tree_id' => $this->tree_id,
      'note_text' => $note_text,
      'import_timestamp' => time(),
    );

    // Check if this is a reference to an existing note
    if (isset($note_record['pointer']) && !empty($note_record['pointer'])) {
      $note_data['gedcom_id'] = $note_record['pointer'];
    }

    // Ensure we have a table name with proper prefix
    $notes_table = $this->db->prefix . 'hp_notes';

    // Insert the note
    $result = $this->db->insert($notes_table, $note_data);

    if ($result) {
      return $this->db->insert_id;
    }

    return false;
  }
}
