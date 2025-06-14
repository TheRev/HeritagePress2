<?php

/**
 * HeritagePress Family Manager Class
 *
 * Handles family relationship data and operations
 * Works with the unified database structure
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Family_Manager
{
  private $wpdb;
  private $data = array();
  private $id = null;
  private $family_id = null;
  private $gedcom = 'main';

  public function __construct($id = null, $gedcom = 'main')
  {
    global $wpdb;
    $this->wpdb = $wpdb;
    $this->gedcom = $gedcom;

    if ($id) {
      if (is_numeric($id)) {
        $this->id = $id;
        $this->load_family_by_db_id();
      } else {
        $this->family_id = $id;
        $this->load_family_by_gedcom_id();
      }
    }
  }

  /**
   * Load family data by database ID
   */
  private function load_family_by_db_id()
  {
    $table_name = HP_Database_Manager::get_table_name('families');

    $family = $this->wpdb->get_row(
      $this->wpdb->prepare(
        "SELECT * FROM $table_name WHERE ID = %d",
        $this->id
      ),
      ARRAY_A
    );

    if ($family) {
      $this->data = $family;
      $this->family_id = $family['familyID'];
      $this->gedcom = $family['gedcom'];
    }
  }

  /**
   * Load family data by GEDCOM ID
   */
  private function load_family_by_gedcom_id()
  {
    $table_name = HP_Database_Manager::get_table_name('families');

    $family = $this->wpdb->get_row(
      $this->wpdb->prepare(
        "SELECT * FROM $table_name WHERE familyID = %s AND gedcom = %s",
        $this->family_id,
        $this->gedcom
      ),
      ARRAY_A
    );

    if ($family) {
      $this->data = $family;
      $this->id = $family['ID'];
    }
  }

  /**
   * Get family data
   */
  public function get($key = null)
  {
    if ($key === null) {
      return $this->data;
    }

    return isset($this->data[$key]) ? $this->data[$key] : null;
  }

  /**
   * Set family data
   */
  public function set($key, $value)
  {
    $this->data[$key] = $value;
  }

  /**
   * Get husband person object
   */
  public function get_husband()
  {
    if (!empty($this->data['husband'])) {
      return new HP_Person_Manager($this->data['husband'], $this->gedcom);
    }
    return null;
  }

  /**
   * Get wife person object
   */
  public function get_wife()
  {
    if (!empty($this->data['wife'])) {
      return new HP_Person_Manager($this->data['wife'], $this->gedcom);
    }
    return null;
  }

  /**
   * Get all children of this family
   */
  public function get_children()
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
        $this->family_id
      ),
      ARRAY_A
    );

    $child_objects = array();
    foreach ($children as $child_data) {
      $child = new HP_Person_Manager();
      $child->data = $child_data;
      $child->id = $child_data['ID'];
      $child->person_id = $child_data['personID'];
      $child->gedcom = $child_data['gedcom'];
      $child_objects[] = $child;
    }

    return $child_objects;
  }

  /**
   * Add child to family
   */
  public function add_child($person_id, $order_num = 0)
  {
    $children_table = HP_Database_Manager::get_table_name('children');

    // Check if child already exists in family
    $exists = $this->wpdb->get_var(
      $this->wpdb->prepare(
        "SELECT COUNT(*) FROM $children_table
         WHERE gedcom = %s AND familyID = %s AND personID = %s",
        $this->gedcom,
        $this->family_id,
        $person_id
      )
    );

    if ($exists > 0) {
      return false; // Child already in family
    }

    // If no order specified, use next available
    if ($order_num == 0) {
      $max_order = $this->wpdb->get_var(
        $this->wpdb->prepare(
          "SELECT MAX(ordernum) FROM $children_table
           WHERE gedcom = %s AND familyID = %s",
          $this->gedcom,
          $this->family_id
        )
      );
      $order_num = ($max_order ?? 0) + 1;
    }

    $result = $this->wpdb->insert(
      $children_table,
      array(
        'gedcom' => $this->gedcom,
        'familyID' => $this->family_id,
        'personID' => $person_id,
        'ordernum' => $order_num,
        'parentorder' => 1
      ),
      array('%s', '%s', '%s', '%d', '%d')
    );

    return $result !== false;
  }

  /**
   * Remove child from family
   */
  public function remove_child($person_id)
  {
    $children_table = HP_Database_Manager::get_table_name('children');

    $result = $this->wpdb->delete(
      $children_table,
      array(
        'gedcom' => $this->gedcom,
        'familyID' => $this->family_id,
        'personID' => $person_id
      ),
      array('%s', '%s', '%s')
    );

    return $result !== false;
  }

  /**
   * Get marriage information
   */
  public function get_marriage_info()
  {
    return array(
      'date' => $this->data['marrdate'] ?? '',
      'date_formatted' => $this->data['marrdatetr'] ?? '0000-00-00',
      'place' => $this->data['marrplace'] ?? '',
      'type' => $this->data['marrtype'] ?? ''
    );
  }

  /**
   * Get divorce information
   */
  public function get_divorce_info()
  {
    return array(
      'date' => $this->data['divdate'] ?? '',
      'date_formatted' => $this->data['divdatetr'] ?? '0000-00-00',
      'place' => $this->data['divplace'] ?? ''
    );
  }

  /**
   * Check if family is living (has living members)
   */
  public function is_living()
  {
    return !empty($this->data['living']) && $this->data['living'] == 1;
  }

  /**
   * Check if family is private
   */
  public function is_private()
  {
    return !empty($this->data['private']) && $this->data['private'] == 1;
  }

  /**
   * Get family events
   */
  public function get_events($event_type = null)
  {
    $events_table = HP_Database_Manager::get_table_name('events');

    $where_clause = "WHERE gedcom = %s AND persfamID = %s";
    $params = array($this->gedcom, $this->family_id);

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
   * Save family to database
   */
  public function save()
  {
    $table_name = HP_Database_Manager::get_table_name('families');

    // Sanitize data
    $data = $this->sanitize_data($this->data);

    // Ensure required fields
    if (empty($data['gedcom'])) {
      $data['gedcom'] = $this->gedcom;
    }

    if (empty($data['familyID'])) {
      $data['familyID'] = $this->generate_family_id();
    }

    if ($this->id && $this->exists()) {
      // Update existing family
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
      // Insert new family
      $data['changedate'] = current_time('mysql');
      $data['changedby'] = $this->get_current_user_name();

      $result = $this->wpdb->insert(
        $table_name,
        $data,
        $this->get_data_format($data)
      );

      if ($result !== false) {
        $this->id = $this->wpdb->insert_id;
        $this->family_id = $data['familyID'];
        return true;
      }
    }

    return false;
  }

  /**
   * Check if family exists in database
   */
  public function exists()
  {
    if (!$this->id) {
      return false;
    }

    $table_name = HP_Database_Manager::get_table_name('families');
    $count = $this->wpdb->get_var(
      $this->wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE ID = %d",
        $this->id
      )
    );

    return $count > 0;
  }

  /**
   * Delete family from database
   */
  public function delete()
  {
    if (!$this->id) {
      return false;
    }

    $table_name = HP_Database_Manager::get_table_name('families');

    // First remove all children
    $children_table = HP_Database_Manager::get_table_name('children');
    $this->wpdb->delete(
      $children_table,
      array('gedcom' => $this->gedcom, 'familyID' => $this->family_id),
      array('%s', '%s')
    );

    // Remove family events
    $events_table = HP_Database_Manager::get_table_name('events');
    $this->wpdb->delete(
      $events_table,
      array('gedcom' => $this->gedcom, 'persfamID' => $this->family_id),
      array('%s', '%s')
    );

    // Finally delete the family record
    $result = $this->wpdb->delete(
      $table_name,
      array('ID' => $this->id),
      array('%d')
    );

    return $result !== false;
  }

  /**
   * Generate a unique family ID
   */
  private function generate_family_id()
  {
    $table_name = HP_Database_Manager::get_table_name('families');

    do {
      $new_id = 'F' . rand(1000, 9999);
      $exists = $this->wpdb->get_var(
        $this->wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name WHERE familyID = %s AND gedcom = %s",
          $new_id,
          $this->gedcom
        )
      );
    } while ($exists > 0);

    return $new_id;
  }

  /**
   * Sanitize family data for database
   */
  private function sanitize_data($data)
  {
    $sanitized = array();

    $text_fields = array(
      'familyID',
      'gedcom',
      'husband',
      'wife',
      'marrdate',
      'marrplace',
      'marrtype',
      'divdate',
      'divplace',
      'status',
      'branch',
      'changedby'
    );

    foreach ($text_fields as $field) {
      if (isset($data[$field])) {
        $sanitized[$field] = sanitize_text_field($data[$field]);
      }
    }

    $int_fields = array('living', 'private');
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
      if (in_array($key, array('living', 'private'))) {
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
   * Static method to find families by spouse
   */
  public static function find_by_spouse($person_id, $gedcom = 'main')
  {
    global $wpdb;
    $table_name = HP_Database_Manager::get_table_name('families');

    $results = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT * FROM $table_name
         WHERE gedcom = %s AND (husband = %s OR wife = %s)
         ORDER BY marrdatetr",
        $gedcom,
        $person_id,
        $person_id
      ),
      ARRAY_A
    );

    $families = array();
    foreach ($results as $family_data) {
      $family = new self();
      $family->data = $family_data;
      $family->id = $family_data['ID'];
      $family->family_id = $family_data['familyID'];
      $family->gedcom = $family_data['gedcom'];
      $families[] = $family;
    }

    return $families;
  }

  /**
   * Get family's database ID
   */
  public function get_id()
  {
    return $this->id;
  }

  /**
   * Get family's GEDCOM ID
   */
  public function get_family_id()
  {
    return $this->family_id;
  }

  /**
   * Get family's tree/gedcom
   */
  public function get_gedcom()
  {
    return $this->gedcom;
  }
}
