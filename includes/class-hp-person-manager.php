<?php

/**
 * HeritagePress Person Class
 *
 * Handles individual person records and genealogy operations
 * Works with the unified database structure
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Person
{
  private $wpdb;
  private $data = array();
  private $id = null;
  private $person_id = null;
  private $gedcom = 'main';

  public function __construct($id = null, $gedcom = 'main')
  {
    global $wpdb;
    $this->wpdb = $wpdb;
    $this->gedcom = $gedcom;

    if ($id) {
      if (is_numeric($id)) {
        $this->id = $id;
        $this->load_person_by_db_id();
      } else {
        $this->person_id = $id;
        $this->load_person_by_gedcom_id();
      }
    }
  }

  /**
   * Load person data by database ID
   */
  private function load_person_by_db_id()
  {
    $table_name = HP_Database_Manager::get_table_name('people');

    $person = $this->wpdb->get_row(
      $this->wpdb->prepare(
        "SELECT * FROM $table_name WHERE ID = %d",
        $this->id
      ),
      ARRAY_A
    );

    if ($person) {
      $this->data = $person;
      $this->person_id = $person['personID'];
      $this->gedcom = $person['gedcom'];
    }
  }

  /**
   * Load person data by GEDCOM ID
   */
  private function load_person_by_gedcom_id()
  {
    $table_name = HP_Database_Manager::get_table_name('people');

    $person = $this->wpdb->get_row(
      $this->wpdb->prepare(
        "SELECT * FROM $table_name WHERE personID = %s AND gedcom = %s",
        $this->person_id,
        $this->gedcom
      ),
      ARRAY_A
    );

    if ($person) {
      $this->data = $person;
      $this->id = $person['ID'];
    }
  }

  /**
   * Get person data
   */
  public function get($key = null)
  {
    if ($key === null) {
      return $this->data;
    }

    return isset($this->data[$key]) ? $this->data[$key] : null;
  }

  /**
   * Set person data
   */
  public function set($key, $value)
  {
    $this->data[$key] = $value;
  }

  /**
   * Get full name
   */
  public function get_full_name()
  {
    $parts = array();

    if (!empty($this->data['prefix'])) {
      $parts[] = $this->data['prefix'];
    }

    if (!empty($this->data['firstname'])) {
      $parts[] = $this->data['firstname'];
    }

    if (!empty($this->data['lastname'])) {
      $parts[] = $this->data['lastname'];
    }

    if (!empty($this->data['suffix'])) {
      $parts[] = $this->data['suffix'];
    }

    return implode(' ', $parts);
  }

  /**
   * Get formatted name for display
   */
  public function get_display_name()
  {
    $name = trim($this->data['firstname'] . ' ' . $this->data['lastname']);

    if (empty($name)) {
      return 'Unknown Person';
    }

    // Add nickname if present
    if (!empty($this->data['nickname'])) {
      $name .= ' "' . $this->data['nickname'] . '"';
    }

    return $name;
  }

  /**
   * Get birth information
   */
  public function get_birth_info()
  {
    return array(
      'date' => $this->data['birthdate'] ?? '',
      'date_formatted' => $this->data['birthdatetr'] ?? '0000-00-00',
      'place' => $this->data['birthplace'] ?? ''
    );
  }

  /**
   * Get death information
   */
  public function get_death_info()
  {
    return array(
      'date' => $this->data['deathdate'] ?? '',
      'date_formatted' => $this->data['deathdatetr'] ?? '0000-00-00',
      'place' => $this->data['deathplace'] ?? ''
    );
  }

  /**
   * Check if person is living
   */
  public function is_living()
  {
    return !empty($this->data['living']) && $this->data['living'] == 1;
  }

  /**
   * Check if person is private
   */
  public function is_private()
  {
    return !empty($this->data['private']) && $this->data['private'] == 1;
  }

  /**
   * Get person's families as spouse
   */
  public function get_spouse_families()
  {
    $families_table = HP_Database_Manager::get_table_name('families');

    $families = $this->wpdb->get_results(
      $this->wpdb->prepare(
        "SELECT * FROM $families_table
         WHERE gedcom = %s AND (husband = %s OR wife = %s)
         ORDER BY marrdatetr",
        $this->gedcom,
        $this->person_id,
        $this->person_id
      ),
      ARRAY_A
    );

    return $families;
  }

  /**
   * Get person's families as child
   */
  public function get_child_families()
  {
    $children_table = HP_Database_Manager::get_table_name('children');
    $families_table = HP_Database_Manager::get_table_name('families');

    $families = $this->wpdb->get_results(
      $this->wpdb->prepare(
        "SELECT f.* FROM $families_table f
         INNER JOIN $children_table c ON f.familyID = c.familyID AND f.gedcom = c.gedcom
         WHERE c.gedcom = %s AND c.personID = %s
         ORDER BY f.marrdatetr",
        $this->gedcom,
        $this->person_id
      ),
      ARRAY_A
    );

    return $families;
  }

  /**
   * Get person's children
   */
  public function get_children()
  {
    $children = array();
    $families = $this->get_spouse_families();

    foreach ($families as $family) {
      $family_children = $this->get_family_children($family['familyID']);
      $children = array_merge($children, $family_children);
    }

    return $children;
  }

  /**
   * Get children for a specific family
   */
  private function get_family_children($family_id)
  {
    $children_table = HP_Database_Manager::get_table_name('children');
    $people_table = HP_Database_Manager::get_table_name('people');

    $children = $this->wpdb->get_results(
      $this->wpdb->prepare(
        "SELECT p.* FROM $people_table p
         INNER JOIN $children_table c ON p.personID = c.personID AND p.gedcom = c.gedcom
         WHERE c.gedcom = %s AND c.familyID = %s
         ORDER BY c.ordernum, p.birthdatetr",
        $this->gedcom,
        $family_id
      ),
      ARRAY_A
    );

    return $children;
  }

  /**
   * Get person's events
   */
  public function get_events($event_type = null)
  {
    $events_table = HP_Database_Manager::get_table_name('events');

    $where_clause = "WHERE gedcom = %s AND persfamID = %s";
    $params = array($this->gedcom, $this->person_id);

    if ($event_type) {
      $where_clause .= " AND eventtypeID = %s";
      $params[] = $event_type;
    }

    $events = $this->wpdb->get_results(
      $this->wpdb->prepare(
        "SELECT * FROM $events_table $where_clause ORDER BY eventdatetr",
        ...$params
      ),
      ARRAY_A
    );

    return $events;
  }

  /**
   * Save person to database
   */
  public function save()
  {
    $table_name = HP_Database_Manager::get_table_name('people');

    // Sanitize data
    $data = $this->sanitize_data($this->data);

    // Ensure required fields
    if (empty($data['gedcom'])) {
      $data['gedcom'] = $this->gedcom;
    }

    if (empty($data['personID'])) {
      $data['personID'] = $this->generate_person_id();
    }

    if ($this->id && $this->exists()) {
      // Update existing person
      $data['changedate'] = current_time('mysql');
      $data['changedby'] = $this->get_current_user_name();

      $result = $this->wpdb->update(
        $table_name,
        $data,
        array('ID' => $this->id),
        $this->get_data_format($data),
        array('%d')
      );

      return $result !== false;
    } else {
      // Insert new person
      $data['changedate'] = current_time('mysql');
      $data['changedby'] = $this->get_current_user_name();

      $result = $this->wpdb->insert(
        $table_name,
        $data,
        $this->get_data_format($data)
      );

      if ($result !== false) {
        $this->id = $this->wpdb->insert_id;
        $this->person_id = $data['personID'];
        return true;
      }
    }

    return false;
  }

  /**
   * Check if person exists in database
   */
  public function exists()
  {
    if (!$this->id) {
      return false;
    }

    $table_name = HP_Database_Manager::get_table_name('people');
    $count = $this->wpdb->get_var(
      $this->wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE ID = %d",
        $this->id
      )
    );

    return $count > 0;
  }

  /**
   * Delete person from database
   */
  public function delete()
  {
    if (!$this->id) {
      return false;
    }

    $table_name = HP_Database_Manager::get_table_name('people');

    $result = $this->wpdb->delete(
      $table_name,
      array('ID' => $this->id),
      array('%d')
    );

    if ($result !== false) {
      $this->cleanup_person_data();
      return true;
    }

    return false;
  }

  /**
   * Clean up related data when person is deleted
   */
  private function cleanup_person_data()
  {
    // Remove from children table
    $children_table = HP_Database_Manager::get_table_name('children');
    $this->wpdb->delete(
      $children_table,
      array('gedcom' => $this->gedcom, 'personID' => $this->person_id),
      array('%s', '%s')
    );

    // Remove events
    $events_table = HP_Database_Manager::get_table_name('events');
    $this->wpdb->delete(
      $events_table,
      array('gedcom' => $this->gedcom, 'persfamID' => $this->person_id),
      array('%s', '%s')
    );

    // Remove citations
    $citations_table = HP_Database_Manager::get_table_name('citations');
    $this->wpdb->delete(
      $citations_table,
      array('gedcom' => $this->gedcom, 'persfamID' => $this->person_id),
      array('%s', '%s')
    );
  }

  /**
   * Generate a unique person ID
   */
  private function generate_person_id()
  {
    $table_name = HP_Database_Manager::get_table_name('people');

    do {
      $new_id = 'I' . rand(1000, 9999);
      $exists = $this->wpdb->get_var(
        $this->wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name WHERE personID = %s AND gedcom = %s",
          $new_id,
          $this->gedcom
        )
      );
    } while ($exists > 0);

    return $new_id;
  }

  /**
   * Sanitize person data for database
   */
  private function sanitize_data($data)
  {
    $sanitized = array();

    $text_fields = array(
      'personID',
      'gedcom',
      'lastname',
      'firstname',
      'prefix',
      'suffix',
      'nickname',
      'sex',
      'birthdate',
      'birthplace',
      'deathdate',
      'deathplace',
      'burialdate',
      'burialplace',
      'branch',
      'mainnote',
      'notes',
      'changedby'
    );

    foreach ($text_fields as $field) {
      if (isset($data[$field])) {
        $sanitized[$field] = sanitize_text_field($data[$field]);
      }
    }

    $textarea_fields = array('mainnote', 'notes');
    foreach ($textarea_fields as $field) {
      if (isset($data[$field])) {
        $sanitized[$field] = sanitize_textarea_field($data[$field]);
      }
    }

    $int_fields = array('nameorder', 'living', 'private');
    foreach ($int_fields as $field) {
      if (isset($data[$field])) {
        $sanitized[$field] = (int) $data[$field];
      }
    }

    return $sanitized;
  }

  /**
   * Get data format array for wpdb operations
   */
  private function get_data_format($data)
  {
    $format = array();

    foreach ($data as $key => $value) {
      if (in_array($key, array('nameorder', 'living', 'private'))) {
        $format[] = '%d';
      } else {
        $format[] = '%s';
      }
    }

    return $format;
  }

  /**
   * Get current user name for change tracking
   */
  private function get_current_user_name()
  {
    $user = wp_get_current_user();
    return $user->exists() ? $user->display_name : 'System';
  }

  /**
   * Static method to find people by name
   */
  public static function find_by_name($firstname = '', $lastname = '', $gedcom = 'main')
  {
    global $wpdb;
    $table_name = HP_Database_Manager::get_table_name('people');

    $where_conditions = array('gedcom = %s');
    $params = array($gedcom);

    if (!empty($firstname)) {
      $where_conditions[] = 'firstname LIKE %s';
      $params[] = '%' . $wpdb->esc_like($firstname) . '%';
    }

    if (!empty($lastname)) {
      $where_conditions[] = 'lastname LIKE %s';
      $params[] = '%' . $wpdb->esc_like($lastname) . '%';
    }

    $where_clause = implode(' AND ', $where_conditions);

    $results = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT * FROM $table_name WHERE $where_clause ORDER BY lastname, firstname",
        ...$params
      ),
      ARRAY_A
    );

    $people = array();
    foreach ($results as $person_data) {
      $person = new self();
      $person->data = $person_data;
      $person->id = $person_data['ID'];
      $person->person_id = $person_data['personID'];
      $person->gedcom = $person_data['gedcom'];
      $people[] = $person;
    }

    return $people;
  }

  /**
   * Get person's database ID
   */
  public function get_id()
  {
    return $this->id;
  }

  /**
   * Get person's GEDCOM ID
   */
  public function get_person_id()
  {
    return $this->person_id;
  }

  /**
   * Get person's tree/gedcom
   */
  public function get_gedcom()
  {
    return $this->gedcom;
  }
}
