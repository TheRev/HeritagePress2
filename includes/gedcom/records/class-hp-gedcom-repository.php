<?php

/**
 * HeritagePress GEDCOM Repository Record Handler
 *
 * Handles processing of GEDCOM Repository (REPO) records
 *
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_GEDCOM_Repository extends HP_GEDCOM_Record_Base
{
  /**
   * Process a repository record
   *
   * @param array $record Record data
   * @return string|false Repository ID or false on failure
   */
  public function process($record)
  {
    // Check if this is a REPO record
    if (!isset($record['type']) || $record['type'] !== 'REPO') {
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

    // Extract repository data
    $repo_data = $this->extract_repository_data($record);

    // Insert into database
    $result = $this->insert_repository($repo_data);

    if ($result) {
      $this->processed_ids[] = $gedcom_id;

      // Process notes
      $this->process_notes($gedcom_id, $record);

      return $gedcom_id;
    }

    return false;
  }

  /**
   * Extract repository data from GEDCOM record
   *
   * @param array $record Record data
   * @return array Repository data
   */
  private function extract_repository_data($record)
  {
    $repo_data = array(
      'gedcom_id' => isset($record['id']) ? $record['id'] : '',
      'tree_id' => $this->tree_id,
      'name' => '',
      'address' => '',
      'city' => '',
      'state' => '',
      'postal_code' => '',
      'country' => '',
      'phone' => '',
      'email' => '',
      'website' => '',
      'import_timestamp' => time(),
    );

    // Extract repository details
    if (!empty($record['children'])) {
      foreach ($record['children'] as $child) {
        if (!isset($child['tag'])) {
          continue;
        }

        switch ($child['tag']) {
          case 'NAME':
            $repo_data['name'] = isset($child['value']) ? $child['value'] : '';
            break;

          case 'ADDR':
            if (isset($child['value'])) {
              $repo_data['address'] = $child['value'];
            }

            // Extract address components if present as sub-elements
            if (!empty($child['children'])) {
              foreach ($child['children'] as $addr_part) {
                if (isset($addr_part['tag'])) {
                  switch ($addr_part['tag']) {
                    case 'CITY':
                      $repo_data['city'] = isset($addr_part['value']) ? $addr_part['value'] : '';
                      break;

                    case 'STAE':
                      $repo_data['state'] = isset($addr_part['value']) ? $addr_part['value'] : '';
                      break;

                    case 'POST':
                      $repo_data['postal_code'] = isset($addr_part['value']) ? $addr_part['value'] : '';
                      break;

                    case 'CTRY':
                      $repo_data['country'] = isset($addr_part['value']) ? $addr_part['value'] : '';
                      break;
                  }
                }
              }
            }
            break;

          case 'PHON':
            $repo_data['phone'] = isset($child['value']) ? $child['value'] : '';
            break;

          case 'EMAIL':
            $repo_data['email'] = isset($child['value']) ? $child['value'] : '';
            break;

          case 'WWW':
            $repo_data['website'] = isset($child['value']) ? $child['value'] : '';
            break;
        }
      }
    }

    return $repo_data;
  }

  /**
   * Insert repository record into database
   *
   * @param array $repo_data Repository data
   * @return string|false Repository ID or false on failure
   */
  private function insert_repository($repo_data)
  {
    // Ensure we have a table name with proper prefix
    $repositories_table = $this->db->prefix . 'hp_repositories';

    // Check if repository already exists
    $existing_repo = $this->db->get_row(
      $this->db->prepare(
        "SELECT * FROM $repositories_table WHERE gedcom_id = %s AND tree_id = %s",
        $repo_data['gedcom_id'],
        $repo_data['tree_id']
      )
    );

    if ($existing_repo) {
      // Update existing record
      $this->db->update(
        $repositories_table,
        $repo_data,
        array(
          'gedcom_id' => $repo_data['gedcom_id'],
          'tree_id' => $repo_data['tree_id']
        )
      );

      return $repo_data['gedcom_id'];
    } else {
      // Insert new record
      $result = $this->db->insert($repositories_table, $repo_data);

      if ($result) {
        return $repo_data['gedcom_id'];
      }
    }

    return false;
  }

  /**
   * Process notes associated with repository
   *
   * @param string $repo_id Repository ID
   * @param array $record Repository record
   */
  private function process_notes($repo_id, $record)
  {
    if (empty($record['children'])) {
      return;
    }

    $notes_table = $this->db->prefix . 'hp_xnotes';

    foreach ($record['children'] as $child) {
      if (isset($child['tag']) && $child['tag'] === 'NOTE' && !empty($child['value'])) {
        $this->db->insert(
          $notes_table,
          array(
            'entity_id' => $repo_id,
            'entity_type' => 'repository',
            'tree_id' => $this->tree_id,
            'note_text' => $child['value']
          )
        );
      }
    }
  }
}
