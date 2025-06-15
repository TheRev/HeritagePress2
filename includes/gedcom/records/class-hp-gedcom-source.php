<?php

/**
 * HeritagePress GEDCOM Source Record Handler
 *
 * Handles processing of GEDCOM Source (SOUR) records
 *
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_GEDCOM_Source extends HP_GEDCOM_Record_Base
{
  /**
   * Process a source record
   *
   * @param array $record Record data
   * @return string|false Source ID or false on failure
   */
  public function process($record)
  {
    // Check if this is a SOUR record
    if (!isset($record['type']) || $record['type'] !== 'SOUR') {
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

    // Extract source data
    $source_data = $this->extract_source_data($record);

    // Insert into database
    $result = $this->insert_source($source_data);

    if ($result) {
      $this->processed_ids[] = $gedcom_id;

      // Process repositories and notes
      $this->process_repositories($gedcom_id, $record);
      $this->process_notes($gedcom_id, $record);
      $this->process_media($gedcom_id, $record);

      return $gedcom_id;
    }

    return false;
  }

  /**
   * Extract source data from GEDCOM record
   *
   * @param array $record Record data
   * @return array Source data
   */
  private function extract_source_data($record)
  {
    $source_data = array(
      'gedcom_id' => isset($record['id']) ? $record['id'] : '',
      'tree_id' => $this->tree_id,
      'title' => '',
      'author' => '',
      'publication' => '',
      'repository_id' => '',
      'call_number' => '',
      'text' => '',
      'import_timestamp' => time(),
    );

    // Extract source details
    if (!empty($record['children'])) {
      foreach ($record['children'] as $child) {
        if (!isset($child['tag'])) {
          continue;
        }

        switch ($child['tag']) {
          case 'TITL':
            $source_data['title'] = isset($child['value']) ? $child['value'] : '';
            break;

          case 'AUTH':
            $source_data['author'] = isset($child['value']) ? $child['value'] : '';
            break;

          case 'PUBL':
            $source_data['publication'] = isset($child['value']) ? $child['value'] : '';
            break;

          case 'TEXT':
            $source_data['text'] = isset($child['value']) ? $child['value'] : '';
            break;

          case 'REPO':
            if (!empty($child['value'])) {
              $source_data['repository_id'] = $child['value'];

              // Extract call number if present
              if (!empty($child['children'])) {
                foreach ($child['children'] as $repo_child) {
                  if (isset($repo_child['tag']) && $repo_child['tag'] === 'CALN' && !empty($repo_child['value'])) {
                    $source_data['call_number'] = $repo_child['value'];
                    break;
                  }
                }
              }
            }
            break;
        }
      }
    }

    return $source_data;
  }

  /**
   * Insert source record into database
   *
   * @param array $source_data Source data
   * @return string|false Source ID or false on failure
   */
  private function insert_source($source_data)
  {
    // Ensure we have a table name with proper prefix
    $sources_table = $this->db->prefix . 'hp_sources';

    // Check if source already exists
    $existing_source = $this->db->get_row(
      $this->db->prepare(
        "SELECT * FROM $sources_table WHERE gedcom_id = %s AND tree_id = %s",
        $source_data['gedcom_id'],
        $source_data['tree_id']
      )
    );

    if ($existing_source) {
      // Update existing record
      $this->db->update(
        $sources_table,
        $source_data,
        array(
          'gedcom_id' => $source_data['gedcom_id'],
          'tree_id' => $source_data['tree_id']
        )
      );

      return $source_data['gedcom_id'];
    } else {
      // Insert new record
      $result = $this->db->insert($sources_table, $source_data);

      if ($result) {
        return $source_data['gedcom_id'];
      }
    }

    return false;
  }

  /**
   * Process repositories associated with source
   *
   * @param string $source_id Source ID
   * @param array $record Source record
   */
  private function process_repositories($source_id, $record)
  {
    if (empty($record['children'])) {
      return;
    }

    $source_repos_table = $this->db->prefix . 'hp_source_repositories';

    foreach ($record['children'] as $child) {
      if (isset($child['tag']) && $child['tag'] === 'REPO' && !empty($child['value'])) {
        $repo_data = array(
          'source_id' => $source_id,
          'repository_id' => $child['value'],
          'tree_id' => $this->tree_id,
          'call_number' => '',
          'notes' => ''
        );

        // Extract call number and notes
        if (!empty($child['children'])) {
          foreach ($child['children'] as $repo_detail) {
            if (isset($repo_detail['tag'])) {
              if ($repo_detail['tag'] === 'CALN' && !empty($repo_detail['value'])) {
                $repo_data['call_number'] = $repo_detail['value'];
              }

              if ($repo_detail['tag'] === 'NOTE' && !empty($repo_detail['value'])) {
                $repo_data['notes'] .= $repo_detail['value'] . "\n";
              }
            }
          }
        }

        // Insert repository link
        $this->db->insert($source_repos_table, $repo_data);
      }
    }
  }

  /**
   * Process notes associated with source
   *
   * @param string $source_id Source ID
   * @param array $record Source record
   */
  private function process_notes($source_id, $record)
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
            'entity_id' => $source_id,
            'entity_type' => 'source',
            'tree_id' => $this->tree_id,
            'note_text' => $child['value']
          )
        );
      }
    }
  }

  /**
   * Process media associated with source
   *
   * @param string $source_id Source ID
   * @param array $record Source record
   */
  private function process_media($source_id, $record)
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
            'entity_id' => $source_id,
            'entity_type' => 'source',
            'media_id' => $child['value'],
            'tree_id' => $this->tree_id
          )
        );
      }
    }
  }
}
