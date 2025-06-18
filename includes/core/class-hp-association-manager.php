<?php

/**
 * HeritagePress Association Manager
 *
 * Handles management of associations between people and families.
 * An association represents relationships like godparent, witness, etc.
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Association_Manager
{

  private $wpdb;
  private $table_prefix;

  public function __construct()
  {
    global $wpdb;
    $this->wpdb = $wpdb;
    $this->table_prefix = $wpdb->prefix . 'hp_';
  }

  /**
   * Add a new association between people/families
   *
   * @param string $gedcom Tree identifier
   * @param string $person_id Main person ID
   * @param string $associated_id Associated person/family ID
   * @param string $relationship Description of relationship
   * @param string $rel_type 'I' for Individual, 'F' for Family
   * @param bool $create_reverse Whether to create reverse association
   * @return int|false Association ID on success, false on failure
   */
  public function add_association($gedcom, $person_id, $associated_id, $relationship, $rel_type = 'I', $create_reverse = false)
  {
    // Validate inputs
    if (empty($gedcom) || empty($person_id) || empty($associated_id) || empty($relationship)) {
      return false;
    }

    // Sanitize inputs
    $gedcom = sanitize_text_field($gedcom);
    $person_id = sanitize_text_field($person_id);
    $associated_id = strtoupper(sanitize_text_field($associated_id));
    $relationship = sanitize_text_field($relationship);
    $rel_type = in_array($rel_type, ['I', 'F']) ? $rel_type : 'I';

    // Insert association
    $table_name = $this->table_prefix . 'associations';
    $result = $this->wpdb->insert(
      $table_name,
      [
        'gedcom' => $gedcom,
        'personID' => $person_id,
        'passocID' => $associated_id,
        'relationship' => $relationship,
        'reltype' => $rel_type
      ],
      ['%s', '%s', '%s', '%s', '%s']
    );

    if ($result === false) {
      error_log('HeritagePress: Failed to insert association - ' . $this->wpdb->last_error);
      return false;
    }

    $association_id = $this->wpdb->insert_id;

    // Create reverse association if requested
    if ($create_reverse && $rel_type === 'I') {
      $this->wpdb->insert(
        $table_name,
        [
          'gedcom' => $gedcom,
          'personID' => $associated_id,
          'passocID' => $person_id,
          'relationship' => $relationship,
          'reltype' => $rel_type
        ],
        ['%s', '%s', '%s', '%s', '%s']
      );
    }

    // Log the action
    $this->log_association_action('add', $association_id, $gedcom, $person_id, $associated_id, $relationship);

    return $association_id;
  }

  /**
   * Get associations for a person
   *
   * @param string $gedcom Tree identifier
   * @param string $person_id Person ID
   * @return array Array of associations
   */
  public function get_person_associations($gedcom, $person_id)
  {
    $table_name = $this->table_prefix . 'associations';

    $query = $this->wpdb->prepare(
      "SELECT * FROM {$table_name} WHERE gedcom = %s AND personID = %s ORDER BY relationship",
      $gedcom,
      $person_id
    );

    return $this->wpdb->get_results($query, ARRAY_A);
  }

  /**
   * Delete an association
   *
   * @param int $association_id Association ID to delete
   * @return bool True on success, false on failure
   */
  public function delete_association($association_id)
  {
    $table_name = $this->table_prefix . 'associations';

    // Get association details for logging
    $association = $this->wpdb->get_row(
      $this->wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE assocID = %d",
        $association_id
      ),
      ARRAY_A
    );

    if (!$association) {
      return false;
    }

    $result = $this->wpdb->delete(
      $table_name,
      ['assocID' => $association_id],
      ['%d']
    );

    if ($result !== false) {
      $this->log_association_action(
        'delete',
        $association_id,
        $association['gedcom'],
        $association['personID'],
        $association['passocID'],
        $association['relationship']
      );
    }

    return $result !== false;
  }

  /**
   * Get display name for an associated person or family
   *
   * @param string $gedcom Tree identifier
   * @param string $id Person or family ID
   * @param string $rel_type 'I' for Individual, 'F' for Family
   * @return string Display name
   */
  public function get_associated_display_name($gedcom, $id, $rel_type = 'I')
  {
    if ($rel_type === 'I') {
      return $this->get_person_display_name($gedcom, $id);
    } else {
      return $this->get_family_display_name($gedcom, $id);
    }
  }

  /**
   * Get display name for a person
   *
   * @param string $gedcom Tree identifier
   * @param string $person_id Person ID
   * @return string Display name
   */
  private function get_person_display_name($gedcom, $person_id)
  {
    $people_table = $this->table_prefix . 'people';

    $person = $this->wpdb->get_row(
      $this->wpdb->prepare(
        "SELECT personID, firstname, lnprefix, lastname, prefix, suffix, title, living, private
                 FROM {$people_table} WHERE personID = %s AND gedcom = %s",
        $person_id,
        $gedcom
      ),
      ARRAY_A
    );

    if (!$person) {
      return $person_id;
    }

    // Check privacy settings
    if ($this->is_private_person($person)) {
      return "[Private] ({$person_id})";
    }

    $name_parts = [];

    if (!empty($person['prefix'])) {
      $name_parts[] = $person['prefix'];
    }
    if (!empty($person['firstname'])) {
      $name_parts[] = $person['firstname'];
    }
    if (!empty($person['lnprefix'])) {
      $name_parts[] = $person['lnprefix'];
    }
    if (!empty($person['lastname'])) {
      $name_parts[] = $person['lastname'];
    }
    if (!empty($person['suffix'])) {
      $name_parts[] = $person['suffix'];
    }

    $name = implode(' ', $name_parts);
    return !empty($name) ? "{$name} ({$person_id})" : $person_id;
  }

  /**
   * Get display name for a family
   *
   * @param string $gedcom Tree identifier
   * @param string $family_id Family ID
   * @return string Display name
   */
  private function get_family_display_name($gedcom, $family_id)
  {
    $families_table = $this->table_prefix . 'families';

    $family = $this->wpdb->get_row(
      $this->wpdb->prepare(
        "SELECT husband, wife, familyID FROM {$families_table} WHERE familyID = %s AND gedcom = %s",
        $family_id,
        $gedcom
      ),
      ARRAY_A
    );

    if (!$family) {
      return $family_id;
    }

    $husband_name = $family['husband'] ? $this->get_person_display_name($gedcom, $family['husband']) : '';
    $wife_name = $family['wife'] ? $this->get_person_display_name($gedcom, $family['wife']) : '';

    if ($husband_name && $wife_name) {
      return "Family of {$husband_name} and {$wife_name}";
    } elseif ($husband_name) {
      return "Family of {$husband_name}";
    } elseif ($wife_name) {
      return "Family of {$wife_name}";
    }

    return $family_id;
  }

  /**
   * Check if person data should be private
   *
   * @param array $person Person data
   * @return bool True if private
   */
  private function is_private_person($person)
  {
    // Check privacy flags
    if (isset($person['private']) && $person['private'] == 1) {
      return true;
    }

    if (isset($person['living']) && $person['living'] == 1) {
      // Check if current user can view living people
      return !current_user_can('manage_options');
    }

    return false;
  }

  /**
   * Log association actions for audit trail
   *
   * @param string $action Action performed (add, delete, edit)
   * @param int $association_id Association ID
   * @param string $gedcom Tree identifier
   * @param string $person_id Person ID
   * @param string $associated_id Associated person/family ID
   * @param string $relationship Relationship description
   */
  private function log_association_action($action, $association_id, $gedcom, $person_id, $associated_id, $relationship)
  {
    $user = wp_get_current_user();
    $user_name = $user->user_login ?? 'unknown';

    $log_message = sprintf(
      '%s association: %d/%s/%s::%s (%s) by user %s',
      ucfirst($action),
      $association_id,
      $gedcom,
      $person_id,
      $associated_id,
      $relationship,
      $user_name
    );

    error_log('HeritagePress Association Log: ' . $log_message);

    // You could also store this in a dedicated log table if needed
    do_action('heritagepress_association_logged', $action, $association_id, $gedcom, $person_id, $associated_id, $relationship);
  }

  /**
   * Validate association data
   *
   * @param array $data Association data
   * @return array Validation result with 'valid' boolean and 'errors' array
   */
  public function validate_association_data($data)
  {
    $errors = [];

    if (empty($data['gedcom'])) {
      $errors[] = 'Tree identifier is required';
    }

    if (empty($data['person_id'])) {
      $errors[] = 'Person ID is required';
    }

    if (empty($data['associated_id'])) {
      $errors[] = 'Associated person/family ID is required';
    }

    if (empty($data['relationship'])) {
      $errors[] = 'Relationship description is required';
    }

    if (!empty($data['rel_type']) && !in_array($data['rel_type'], ['I', 'F'])) {
      $errors[] = 'Relationship type must be I (Individual) or F (Family)';
    }

    // Check if person exists
    if (!empty($data['gedcom']) && !empty($data['person_id'])) {
      $people_table = $this->table_prefix . 'people';
      $person_exists = $this->wpdb->get_var(
        $this->wpdb->prepare(
          "SELECT COUNT(*) FROM {$people_table} WHERE personID = %s AND gedcom = %s",
          $data['person_id'],
          $data['gedcom']
        )
      );

      if (!$person_exists) {
        $errors[] = 'Person not found in the specified tree';
      }
    }

    return [
      'valid' => empty($errors),
      'errors' => $errors
    ];
  }
}
