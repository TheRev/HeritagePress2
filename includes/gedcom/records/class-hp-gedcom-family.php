<?php

/**
 * HeritagePress GEDCOM Family Record Handler
 *
 * Handles processing of GEDCOM Family (FAM) records
 *
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_GEDCOM_Family extends HP_GEDCOM_Record_Base
{
  /**
   * Process a family record
   *
   * @param array $record Record data
   * @return string|false Family ID or false on failure
   */
  public function process($record)
  {
    // Check if this is a FAM record
    if (!isset($record['type']) || $record['type'] !== 'FAM') {
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

    // Extract family data
    $family_data = $this->extract_family_data($record);

    // Insert into database
    $result = $this->insert_family($family_data);

    if ($result) {
      $this->processed_ids[] = $gedcom_id;

      // Process events, relationships, and other sub-elements
      $this->process_events($gedcom_id, $record);
      $this->process_relationships($gedcom_id, $record);
      $this->process_notes($gedcom_id, $record);
      $this->process_citations($gedcom_id, $record);
      $this->process_media($gedcom_id, $record);

      return $gedcom_id;
    }

    return false;
  }

  /**
   * Extract family data from GEDCOM record
   *
   * @param array $record Record data
   * @return array Family data
   */
  private function extract_family_data($record)
  {
    $family_data = array(
      'gedcom_id' => isset($record['id']) ? $record['id'] : '',
      'husband_id' => '',
      'wife_id' => '',
      'tree_id' => $this->tree_id,
      'marriage_date' => '',
      'marriage_place' => '',
      'marriage_status' => '',
      'privacy' => 0,
      'import_timestamp' => time(),
    );

    // Extract husband and wife references
    if (!empty($record['children'])) {
      foreach ($record['children'] as $child) {
        if (isset($child['tag']) && $child['tag'] === 'HUSB' && !empty($child['value'])) {
          $family_data['husband_id'] = $child['value'];
        }

        if (isset($child['tag']) && $child['tag'] === 'WIFE' && !empty($child['value'])) {
          $family_data['wife_id'] = $child['value'];
        }
      }
    }

    return $family_data;
  }

  /**
   * Insert family record into database
   *
   * @param array $family_data Family data
   * @return string|false Family ID or false on failure
   */
  private function insert_family($family_data)
  {
    // Ensure we have a table name with proper prefix
    $families_table = $this->db->prefix . 'hp_families';

    // Check if family already exists
    $existing_family = $this->db->get_row(
      $this->db->prepare(
        "SELECT * FROM $families_table WHERE gedcom_id = %s AND tree_id = %s",
        $family_data['gedcom_id'],
        $family_data['tree_id']
      )
    );

    if ($existing_family) {
      // Update existing record
      $this->db->update(
        $families_table,
        $family_data,
        array(
          'gedcom_id' => $family_data['gedcom_id'],
          'tree_id' => $family_data['tree_id']
        )
      );

      return $family_data['gedcom_id'];
    } else {
      // Insert new record
      $result = $this->db->insert($families_table, $family_data);

      if ($result) {
        return $family_data['gedcom_id'];
      }
    }

    return false;
  }

  /**
   * Process events associated with family
   *
   * @param string $family_id Family ID
   * @param array $record Family record
   */
  private function process_events($family_id, $record)
  {
    if (empty($record['children'])) {
      return;
    }

    $events_table = $this->db->prefix . 'hp_events';

    foreach ($record['children'] as $child) {
      // Check if this is an event
      if (!isset($child['tag'])) {
        continue;
      }

      $event_tag = $child['tag'];
      $event_types = array('MARR', 'DIV', 'ENGA', 'MARB', 'MARC');

      if (in_array($event_tag, $event_types)) {
        $event_data = array(
          'entity_id' => $family_id,
          'entity_type' => 'family',
          'tree_id' => $this->tree_id,
          'event_type' => $event_tag,
          'event_date' => '',
          'event_place' => '',
          'event_details' => '',
        );

        // Extract event date and place
        if (!empty($child['children'])) {
          foreach ($child['children'] as $event_detail) {
            if (isset($event_detail['tag'])) {
              if ($event_detail['tag'] === 'DATE' && !empty($event_detail['value'])) {
                $event_data['event_date'] = $event_detail['value'];
              }

              if ($event_detail['tag'] === 'PLAC' && !empty($event_detail['value'])) {
                $event_data['event_place'] = $event_detail['value'];
              }

              if ($event_detail['tag'] === 'NOTE' && !empty($event_detail['value'])) {
                $event_data['event_details'] .= $event_detail['value'] . "\n";
              }
            }
          }
        }

        // Insert event
        $this->db->insert($events_table, $event_data);
      }
    }
  }

  /**
   * Process relationship links between individuals in this family
   *
   * @param string $family_id Family ID
   * @param array $record Family record
   */
  private function process_relationships($family_id, $record)
  {
    if (empty($record['children'])) {
      return;
    }

    $husband_id = '';
    $wife_id = '';
    $children = array();

    // Extract relationships
    foreach ($record['children'] as $child) {
      if (!isset($child['tag'])) {
        continue;
      }

      if ($child['tag'] === 'HUSB' && !empty($child['value'])) {
        $husband_id = $child['value'];
      } else if ($child['tag'] === 'WIFE' && !empty($child['value'])) {
        $wife_id = $child['value'];
      } else if ($child['tag'] === 'CHIL' && !empty($child['value'])) {
        $children[] = $child['value'];
      }
    }

    // Add appropriate relationships to the database
    $relationships_table = $this->db->prefix . 'hp_relationships';

    // Connect husband to family
    if (!empty($husband_id)) {
      $this->db->insert(
        $relationships_table,
        array(
          'person_id' => $husband_id,
          'family_id' => $family_id,
          'relationship_type' => 'husband',
          'tree_id' => $this->tree_id
        )
      );
    }

    // Connect wife to family
    if (!empty($wife_id)) {
      $this->db->insert(
        $relationships_table,
        array(
          'person_id' => $wife_id,
          'family_id' => $family_id,
          'relationship_type' => 'wife',
          'tree_id' => $this->tree_id
        )
      );
    }

    // Connect children to family
    foreach ($children as $child_id) {
      $this->db->insert(
        $relationships_table,
        array(
          'person_id' => $child_id,
          'family_id' => $family_id,
          'relationship_type' => 'child',
          'tree_id' => $this->tree_id
        )
      );
    }
  }

  /**
   * Process notes associated with family
   *
   * @param string $family_id Family ID
   * @param array $record Family record
   */
  private function process_notes($family_id, $record)
  {
    if (empty($record['children'])) {
      return;
    }

    $notes_table = $this->db->prefix . 'hp_notes';

    foreach ($record['children'] as $child) {
      if (isset($child['tag']) && $child['tag'] === 'NOTE' && !empty($child['value'])) {
        $this->db->insert(
          $notes_table,
          array(
            'entity_id' => $family_id,
            'entity_type' => 'family',
            'tree_id' => $this->tree_id,
            'note_text' => $child['value']
          )
        );
      }
    }
  }

  /**
   * Process citations associated with family
   *
   * @param string $family_id Family ID
   * @param array $record Family record
   */
  private function process_citations($family_id, $record)
  {
    if (empty($record['children'])) {
      return;
    }

    $citations_table = $this->db->prefix . 'hp_citations';

    foreach ($record['children'] as $child) {
      if (isset($child['tag']) && $child['tag'] === 'SOUR' && !empty($child['value'])) {
        $citation_data = array(
          'entity_id' => $family_id,
          'entity_type' => 'family',
          'source_id' => $child['value'],
          'tree_id' => $this->tree_id,
          'citation_text' => '',
          'citation_page' => '',
          'citation_quality' => 0
        );

        // Extract citation details
        if (!empty($child['children'])) {
          foreach ($child['children'] as $citation_detail) {
            if (isset($citation_detail['tag'])) {
              if ($citation_detail['tag'] === 'PAGE' && !empty($citation_detail['value'])) {
                $citation_data['citation_page'] = $citation_detail['value'];
              }

              if ($citation_detail['tag'] === 'TEXT' && !empty($citation_detail['value'])) {
                $citation_data['citation_text'] = $citation_detail['value'];
              }

              if ($citation_detail['tag'] === 'QUAY' && !empty($citation_detail['value'])) {
                $citation_data['citation_quality'] = intval($citation_detail['value']);
              }
            }
          }
        }

        // Insert citation
        $this->db->insert($citations_table, $citation_data);
      }
    }
  }

  /**
   * Process media associated with family
   *
   * @param string $family_id Family ID
   * @param array $record Family record
   */
  private function process_media($family_id, $record)
  {
    if (empty($record['children'])) {
      return;
    }

    $media_links_table = $this->db->prefix . 'hp_media_links';

    foreach ($record['children'] as $child) {
      if (isset($child['tag']) && $child['tag'] === 'OBJE' && !empty($child['value'])) {
        $this->db->insert(
          $media_links_table,
          array(
            'entity_id' => $family_id,
            'entity_type' => 'family',
            'media_id' => $child['value'],
            'tree_id' => $this->tree_id
          )
        );
      }
    }
  }
}
