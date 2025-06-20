<?php

/**
 * HeritagePress Entity Transfer Controller
 *
 * Handles moving/transferring genealogy entities (people, sources, repositories) between trees.
 * Replicates functionality from TNG admin_changetree.php with modern WordPress patterns.
 *
 * @package HeritagePress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Entity_Transfer_Controller
{
  private $wpdb;
  private $table_prefix;

  public function __construct()
  {
    global $wpdb;
    $this->wpdb = $wpdb;
    $this->table_prefix = $wpdb->prefix . 'hp_';

    // Register AJAX handlers
    add_action('wp_ajax_hp_transfer_entity', array($this, 'ajax_transfer_entity'));
    add_action('wp_ajax_hp_check_entity_id', array($this, 'ajax_check_entity_id'));
    add_action('wp_ajax_hp_get_entity_info', array($this, 'ajax_get_entity_info'));
    add_action('wp_ajax_hp_generate_entity_id', array($this, 'ajax_generate_entity_id'));
  }

  /**
   * Transfer an entity between trees
   *
   * @param string $entity_type Entity type: 'person', 'source', 'repository'
   * @param string $entity_id Current entity ID
   * @param string $old_tree Current tree ID
   * @param string $new_tree Destination tree ID
   * @param string $new_id New entity ID (optional)
   * @param int $operation 0=move/update, 1=copy
   * @return bool Success status
   */
  public function transfer_entity($entity_type, $entity_id, $old_tree, $new_tree, $new_id = null, $operation = 0)
  {
    // Validate inputs
    if (empty($entity_type) || empty($entity_id) || empty($old_tree) || empty($new_tree)) {
      return false;
    }

    // Set new ID to current ID if not specified
    if (empty($new_id)) {
      $new_id = $entity_id;
    }

    // Check if target entity ID already exists in destination tree
    if ($new_id !== $entity_id && $this->entity_exists($entity_type, $new_id, $new_tree)) {
      return false;
    }

    // Start transaction
    $this->wpdb->query('START TRANSACTION');

    try {
      switch ($entity_type) {
        case 'person':
          $result = $this->transfer_person($entity_id, $old_tree, $new_tree, $new_id, $operation);
          break;
        case 'source':
          $result = $this->transfer_source($entity_id, $old_tree, $new_tree, $new_id, $operation);
          break;
        case 'repository':
          $result = $this->transfer_repository($entity_id, $old_tree, $new_tree, $new_id, $operation);
          break;
        default:
          throw new Exception('Invalid entity type');
      }

      if ($result) {
        // Transfer common associated data for all entity types
        $this->transfer_associated_data($entity_type, $entity_id, $old_tree, $new_tree, $new_id, $operation);

        $this->wpdb->query('COMMIT');
        return true;
      } else {
        $this->wpdb->query('ROLLBACK');
        return false;
      }
    } catch (Exception $e) {
      $this->wpdb->query('ROLLBACK');
      error_log('HeritagePress Entity Transfer Error: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * Transfer a person between trees
   */
  private function transfer_person($person_id, $old_tree, $new_tree, $new_id, $operation)
  {
    $people_table = $this->table_prefix . 'people';
    $families_table = $this->table_prefix . 'families';
    $children_table = $this->table_prefix . 'children';
    $mostwanted_table = $this->table_prefix . 'mostwanted';
    $temp_events_table = $this->table_prefix . 'temp_events';
    $branchlinks_table = $this->table_prefix . 'branchlinks';
    $citations_table = $this->table_prefix . 'citations';
    $assoc_table = $this->table_prefix . 'associations';

    if ($operation == 0) {
      // Move/Update operation

      // Update person record
      $result = $this->wpdb->update(
        $people_table,
        array('gedcom' => $new_tree, 'personID' => $new_id),
        array('gedcom' => $old_tree, 'personID' => $person_id),
        array('%s', '%s'),
        array('%s', '%s')
      );

      if ($result === false) {
        throw new Exception('Failed to update person record');
      }

      // Update most wanted (if table exists)
      if ($this->table_exists($mostwanted_table)) {
        $this->wpdb->update(
          $mostwanted_table,
          array('gedcom' => $new_tree, 'personID' => $new_id),
          array('gedcom' => $old_tree, 'personID' => $person_id),
          array('%s', '%s'),
          array('%s', '%s')
        );
      }

      // Update temp events (if table exists)
      if ($this->table_exists($temp_events_table)) {
        $this->wpdb->update(
          $temp_events_table,
          array('gedcom' => $new_tree, 'personID' => $new_id),
          array('gedcom' => $old_tree, 'personID' => $person_id),
          array('%s', '%s'),
          array('%s', '%s')
        );
      }

      // Update user assignments (WordPress users table integration)
      $users_table = $this->wpdb->users;
      $usermeta_table = $this->wpdb->usermeta;

      // Update user meta for genealogy assignments
      $this->wpdb->query($this->wpdb->prepare(
        "UPDATE {$usermeta_table} SET meta_value = %s
         WHERE meta_key = 'heritagepress_person_id' AND meta_value = %s",
        $new_tree . ':' . $new_id,
        $old_tree . ':' . $person_id
      ));

      // Clear family spouse links (will be re-established if needed)
      $this->wpdb->update(
        $families_table,
        array('husband' => ''),
        array('gedcom' => $old_tree, 'husband' => $person_id),
        array('%s'),
        array('%s', '%s')
      );

      $this->wpdb->update(
        $families_table,
        array('wife' => ''),
        array('gedcom' => $old_tree, 'wife' => $person_id),
        array('%s'),
        array('%s', '%s')
      );

      // Delete branch links (will be re-established if needed)
      $this->wpdb->delete(
        $branchlinks_table,
        array('gedcom' => $old_tree, 'persfamID' => $person_id),
        array('%s', '%s')
      );

      // Delete citations (will be transferred separately)
      $this->wpdb->delete(
        $citations_table,
        array('gedcom' => $old_tree, 'persfamID' => $person_id),
        array('%s', '%s')
      );

      // Delete child relationships (will be re-established if needed)
      $this->wpdb->delete(
        $children_table,
        array('gedcom' => $old_tree, 'personID' => $person_id),
        array('%s', '%s')
      );

      // Delete associations
      $this->wpdb->delete(
        $assoc_table,
        array('gedcom' => $old_tree, 'personID' => $person_id),
        array('%s', '%s')
      );

      $this->wpdb->delete(
        $assoc_table,
        array('gedcom' => $old_tree, 'passocID' => $person_id),
        array('%s', '%s')
      );
    } else {
      // Copy operation
      $person_data = $this->wpdb->get_row($this->wpdb->prepare(
        "SELECT * FROM {$people_table} WHERE gedcom = %s AND personID = %s",
        $old_tree,
        $person_id
      ), ARRAY_A);

      if (!$person_data) {
        throw new Exception('Person not found in source tree');
      }

      // Prepare data for insertion
      $person_data['personID'] = $new_id;
      $person_data['gedcom'] = $new_tree;
      $person_data['branch'] = ''; // Clear branch assignment
      $person_data['changedby'] = wp_get_current_user()->user_login;
      $person_data['changedate'] = current_time('mysql');
      unset($person_data['ID']); // Remove auto-increment field

      $result = $this->wpdb->insert($people_table, $person_data);

      if ($result === false) {
        throw new Exception('Failed to copy person record');
      }
    }

    return true;
  }

  /**
   * Transfer a source between trees
   */
  private function transfer_source($source_id, $old_tree, $new_tree, $new_id, $operation)
  {
    $sources_table = $this->table_prefix . 'sources';
    $citations_table = $this->table_prefix . 'citations';

    if ($operation == 0) {
      // Move/Update operation
      $result = $this->wpdb->update(
        $sources_table,
        array('gedcom' => $new_tree, 'sourceID' => $new_id),
        array('gedcom' => $old_tree, 'sourceID' => $source_id),
        array('%s', '%s'),
        array('%s', '%s')
      );

      if ($result === false) {
        throw new Exception('Failed to update source record');
      }      // Delete related citations (will be transferred separately)
      $this->wpdb->delete(
        $citations_table,
        array('gedcom' => $old_tree, 'sourceID' => $source_id),
        array('%s', '%s')
      );
    } else {
      // Copy operation
      $source_data = $this->wpdb->get_row($this->wpdb->prepare(
        "SELECT * FROM {$sources_table} WHERE gedcom = %s AND sourceID = %s",
        $old_tree,
        $source_id
      ), ARRAY_A);

      if (!$source_data) {
        throw new Exception('Source not found in source tree');
      }

      // Prepare data for insertion
      $source_data['sourceID'] = $new_id;
      $source_data['gedcom'] = $new_tree;
      $source_data['changedby'] = wp_get_current_user()->user_login;
      $source_data['changedate'] = current_time('mysql');
      unset($source_data['ID']); // Remove auto-increment field

      $result = $this->wpdb->insert($sources_table, $source_data);

      if ($result === false) {
        throw new Exception('Failed to copy source record');
      }
    }

    return true;
  }

  /**
   * Transfer a repository between trees
   */
  private function transfer_repository($repo_id, $old_tree, $new_tree, $new_id, $operation)
  {
    $repositories_table = $this->table_prefix . 'repositories';

    if ($operation == 0) {
      // Move/Update operation
      $result = $this->wpdb->update(
        $repositories_table,
        array('gedcom' => $new_tree, 'repoID' => $new_id),
        array('gedcom' => $old_tree, 'repoID' => $repo_id),
        array('%s', '%s'),
        array('%s', '%s')
      );

      if ($result === false) {
        throw new Exception('Failed to update repository record');
      }
    } else {
      // Copy operation
      $repo_data = $this->wpdb->get_row($this->wpdb->prepare(
        "SELECT * FROM {$repositories_table} WHERE gedcom = %s AND repoID = %s",
        $old_tree,
        $repo_id
      ), ARRAY_A);

      if (!$repo_data) {
        throw new Exception('Repository not found in source tree');
      }

      // Prepare data for insertion
      $repo_data['repoID'] = $new_id;
      $repo_data['gedcom'] = $new_tree;
      $repo_data['changedby'] = wp_get_current_user()->user_login;
      $repo_data['changedate'] = current_time('mysql');
      unset($repo_data['ID']); // Remove auto-increment field

      $result = $this->wpdb->insert($repositories_table, $repo_data);

      if ($result === false) {
        throw new Exception('Failed to copy repository record');
      }
    }

    return true;
  }

  /**
   * Check if an entity exists in the specified tree
   */
  private function entity_exists($entity_type, $entity_id, $tree_id)
  {
    $table = $this->get_entity_table($entity_type);

    if (!$table) {
      return false;
    }

    $exists = $this->wpdb->get_var($this->wpdb->prepare(
      "SELECT COUNT(*) FROM {$table} WHERE gedcom = %s AND " . $entity_type . "ID = %s",
      $tree_id,
      $entity_id
    ));

    return $exists > 0;
  }

  /**
   * Get the database table for the given entity type
   */
  private function get_entity_table($entity_type)
  {
    switch ($entity_type) {
      case 'person':
        return $this->table_prefix . 'people';
      case 'source':
        return $this->table_prefix . 'sources';
      case 'repository':
        return $this->table_prefix . 'repositories';
      default:
        return null;
    }
  }

  /**
   * Check if a table exists in the database
   */
  private function table_exists($table)
  {
    return $this->wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;
  }

  /**
   * AJAX: Transfer entity
   */
  public function ajax_transfer_entity()
  {
    // Security check
    if (!wp_verify_nonce($_POST['nonce'], 'hp_transfer_entity')) {
      wp_send_json_error('Security check failed');
    }

    if (!current_user_can('edit_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    // Sanitize input data
    $entity_type = sanitize_text_field($_POST['entity_type']);
    $entity_id = sanitize_text_field($_POST['entity_id']);
    $old_tree = sanitize_text_field($_POST['old_tree']);
    $new_tree = sanitize_text_field($_POST['new_tree']);
    $new_id = sanitize_text_field($_POST['new_id']);
    $operation = intval($_POST['operation']);

    // Validate required fields
    if (empty($entity_type) || empty($entity_id) || empty($old_tree) || empty($new_tree)) {
      wp_send_json_error('Missing required fields');
    }

    // Perform the transfer
    $result = $this->transfer_entity($entity_type, $entity_id, $old_tree, $new_tree, $new_id, $operation);

    if ($result) {
      // Build redirect URL for successful transfer
      $redirect_url = admin_url('admin.php?page=heritagepress-' . $entity_type . 's&tree=' . $new_tree);

      wp_send_json_success([
        'message' => 'Entity transferred successfully',
        'redirect_url' => $redirect_url
      ]);
    } else {
      wp_send_json_error('Failed to transfer entity');
    }
  }

  /**
   * AJAX: Check if entity ID exists in tree
   */
  public function ajax_check_entity_id()
  {
    // Security check
    if (!wp_verify_nonce($_POST['nonce'], 'hp_transfer_entity')) {
      wp_send_json_error('Security check failed');
    }

    if (!current_user_can('read')) {
      wp_send_json_error('Insufficient permissions');
    }

    // Sanitize input data
    $entity_type = sanitize_text_field($_POST['entity_type']);
    $entity_id = sanitize_text_field($_POST['entity_id']);
    $tree_id = sanitize_text_field($_POST['tree_id']);

    // Check if entity exists
    $exists = $this->entity_exists($entity_type, $entity_id, $tree_id);

    wp_send_json_success(['exists' => $exists]);
  }

  /**
   * AJAX: Get entity information
   */
  public function ajax_get_entity_info()
  {
    // Security check
    if (!wp_verify_nonce($_POST['nonce'], 'hp_transfer_entity')) {
      wp_send_json_error('Security check failed');
    }

    if (!current_user_can('read')) {
      wp_send_json_error('Insufficient permissions');
    }

    // Sanitize input data
    $entity_type = sanitize_text_field($_POST['entity_type']);
    $entity_id = sanitize_text_field($_POST['entity_id']);
    $tree_id = sanitize_text_field($_POST['tree_id']);

    // Get entity info based on type
    $entity_info = $this->get_entity_info($entity_type, $entity_id, $tree_id);

    if ($entity_info) {
      wp_send_json_success($entity_info);
    } else {
      wp_send_json_error('Entity not found');
    }
  }

  /**
   * AJAX: Generate new entity ID
   */
  public function ajax_generate_entity_id()
  {
    // Security check
    if (!wp_verify_nonce($_POST['nonce'], 'hp_transfer_entity')) {
      wp_send_json_error('Security check failed');
    }

    if (!current_user_can('edit_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    // Sanitize input data
    $entity_type = sanitize_text_field($_POST['entity_type']);
    $tree_id = sanitize_text_field($_POST['tree_id']);

    // Generate new ID based on entity type
    $new_id = $this->generate_entity_id($entity_type, $tree_id);

    if ($new_id) {
      wp_send_json_success(['id' => $new_id]);
    } else {
      wp_send_json_error('Failed to generate ID');
    }
  }

  /**
   * Get entity information for display
   */
  private function get_entity_info($entity_type, $entity_id, $tree_id)
  {
    switch ($entity_type) {
      case 'person':
        return $this->get_person_info($entity_id, $tree_id);
      case 'source':
        return $this->get_source_info($entity_id, $tree_id);
      case 'repository':
        return $this->get_repository_info($entity_id, $tree_id);
      default:
        return null;
    }
  }

  /**
   * Get person information
   */
  private function get_person_info($person_id, $tree_id)
  {
    $people_table = $this->table_prefix . 'people';

    $person = $this->wpdb->get_row($this->wpdb->prepare(
      "SELECT * FROM {$people_table} WHERE personID = %s AND gedcom = %s",
      $person_id,
      $tree_id
    ));

    if (!$person) {
      return null;
    }

    $name = trim($person->firstname . ' ' . $person->lastname);
    $birth = $person->birthdate ? date('Y', strtotime($person->birthdate)) : '';
    $death = $person->deathdate ? date('Y', strtotime($person->deathdate)) : '';

    return [
      'type' => 'person',
      'id' => $person->personID,
      'name' => $name,
      'birth' => $birth,
      'death' => $death
    ];
  }

  /**
   * Get source information
   */
  private function get_source_info($source_id, $tree_id)
  {
    $sources_table = $this->table_prefix . 'sources';

    $source = $this->wpdb->get_row($this->wpdb->prepare(
      "SELECT * FROM {$sources_table} WHERE sourceID = %s AND gedcom = %s",
      $source_id,
      $tree_id
    ));

    if (!$source) {
      return null;
    }

    return [
      'type' => 'source',
      'id' => $source->sourceID,
      'title' => $source->title,
      'author' => $source->author
    ];
  }

  /**
   * Get repository information
   */
  private function get_repository_info($repo_id, $tree_id)
  {
    $repositories_table = $this->table_prefix . 'repositories';

    $repo = $this->wpdb->get_row($this->wpdb->prepare(
      "SELECT * FROM {$repositories_table} WHERE repoID = %s AND gedcom = %s",
      $repo_id,
      $tree_id
    ));

    if (!$repo) {
      return null;
    }

    return [
      'type' => 'repository',
      'id' => $repo->repoID,
      'name' => $repo->reponame,
      'address' => $repo->address
    ];
  }

  /**
   * Generate new entity ID
   */
  private function generate_entity_id($entity_type, $tree_id)
  {
    switch ($entity_type) {
      case 'person':
        return $this->generate_person_id($tree_id);
      case 'source':
        return $this->generate_source_id($tree_id);
      case 'repository':
        return $this->generate_repository_id($tree_id);
      default:
        return null;
    }
  }

  /**
   * Generate new person ID
   */
  private function generate_person_id($tree_id)
  {
    $people_table = $this->table_prefix . 'people';

    // Get the highest numbered person ID for this tree
    $last_id = $this->wpdb->get_var($this->wpdb->prepare(
      "SELECT personID FROM {$people_table} WHERE gedcom = %s AND personID REGEXP '^I[0-9]+$' ORDER BY CAST(SUBSTRING(personID, 2) AS UNSIGNED) DESC LIMIT 1",
      $tree_id
    ));

    if ($last_id) {
      $number = intval(substr($last_id, 1));
      $new_number = $number + 1;
    } else {
      $new_number = 1;
    }

    return 'I' . $new_number;
  }

  /**
   * Generate new source ID
   */
  private function generate_source_id($tree_id)
  {
    $sources_table = $this->table_prefix . 'sources';

    // Get the highest numbered source ID for this tree
    $last_id = $this->wpdb->get_var($this->wpdb->prepare(
      "SELECT sourceID FROM {$sources_table} WHERE gedcom = %s AND sourceID REGEXP '^S[0-9]+$' ORDER BY CAST(SUBSTRING(sourceID, 2) AS UNSIGNED) DESC LIMIT 1",
      $tree_id
    ));

    if ($last_id) {
      $number = intval(substr($last_id, 1));
      $new_number = $number + 1;
    } else {
      $new_number = 1;
    }

    return 'S' . $new_number;
  }

  /**
   * Generate new repository ID
   */
  private function generate_repository_id($tree_id)
  {
    $repositories_table = $this->table_prefix . 'repositories';

    // Get the highest numbered repository ID for this tree
    $last_id = $this->wpdb->get_var($this->wpdb->prepare(
      "SELECT repoID FROM {$repositories_table} WHERE gedcom = %s AND repoID REGEXP '^R[0-9]+$' ORDER BY CAST(SUBSTRING(repoID, 2) AS UNSIGNED) DESC LIMIT 1",
      $tree_id
    ));

    if ($last_id) {
      $number = intval(substr($last_id, 1));
      $new_number = $number + 1;
    } else {
      $new_number = 1;
    }

    return 'R' . $new_number;
  }
}
