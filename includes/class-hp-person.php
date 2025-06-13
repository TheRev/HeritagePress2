<?php

/**
 * HeritagePress Person Class
 *
 * Handles individual person records and operations
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Person
{

  private $wpdb;
  private $data = array();
  private $id = null;

  public function __construct($id = null)
  {
    global $wpdb;
    $this->wpdb = $wpdb;

    if ($id) {
      $this->id = $id;
      $this->load_person_data();
    }
  }

  /**
   * Load person data from database
   */
  private function load_person_data()
  {
    $table_name = $this->wpdb->prefix . 'hp_persons';

    $person = $this->wpdb->get_row(
      $this->wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $this->id
      ),
      ARRAY_A
    );

    if ($person) {
      $this->data = $person;
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
   * Save person to database
   */
  public function save()
  {
    $table_name = $this->wpdb->prefix . 'hp_persons';

    // Sanitize data
    $data = $this->sanitize_data($this->data);

    if ($this->id && $this->exists()) {
      // Update existing person
      $data['modified_date'] = current_time('mysql');
      $data['modified_by'] = get_current_user_id();

      $result = $this->wpdb->update(
        $table_name,
        $data,
        array('id' => $this->id),
        $this->get_data_format($data),
        array('%d')
      );

      return $result !== false;
    } else {
      // Insert new person
      $data['created_date'] = current_time('mysql');
      $data['created_by'] = get_current_user_id();

      $result = $this->wpdb->insert(
        $table_name,
        $data,
        $this->get_data_format($data)
      );

      if ($result) {
        $this->id = $this->wpdb->insert_id;
        $this->data['id'] = $this->id;
        return true;
      }

      return false;
    }
  }

  /**
   * Check if person exists
   */
  public function exists()
  {
    if (!$this->id) {
      return false;
    }

    $table_name = $this->wpdb->prefix . 'hp_persons';

    $count = $this->wpdb->get_var(
      $this->wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE id = %d",
        $this->id
      )
    );

    return $count > 0;
  }

  /**
   * Delete person
   */
  public function delete()
  {
    if (!$this->id) {
      return false;
    }

    $table_name = $this->wpdb->prefix . 'hp_persons';

    // Delete related records first
    $this->delete_related_records();

    $result = $this->wpdb->delete(
      $table_name,
      array('id' => $this->id),
      array('%d')
    );

    if ($result) {
      $this->id = null;
      $this->data = array();
      return true;
    }

    return false;
  }

  /**
   * Delete related records
   */
  private function delete_related_records()
  {
    // Delete events
    $events_table = $this->wpdb->prefix . 'hp_events';
    $this->wpdb->delete($events_table, array('person_id' => $this->id), array('%d'));

    // Delete citations
    $citations_table = $this->wpdb->prefix . 'hp_citations';
    $this->wpdb->delete($citations_table, array('person_id' => $this->id), array('%d'));

    // Delete media links
    $medialinks_table = $this->wpdb->prefix . 'hp_medialinks';
    $this->wpdb->delete($medialinks_table, array('person_id' => $this->id), array('%d'));

    // Delete notes
    $notes_table = $this->wpdb->prefix . 'hp_notes';
    $this->wpdb->delete($notes_table, array('person_id' => $this->id), array('%d'));
  }

  /**
   * Get formatted name
   */
  public function get_formatted_name($format = 'full')
  {
    $first = $this->get('first_name');
    $middle = $this->get('middle_name');
    $last = $this->get('last_name');
    $prefix = $this->get('prefix');
    $suffix = $this->get('suffix');
    $nickname = $this->get('nickname');

    switch ($format) {
      case 'first_last':
        return trim($first . ' ' . $last);

      case 'last_first':
        return trim($last . ', ' . $first);

      case 'full':
      default:
        $name = '';
        if ($prefix) $name .= $prefix . ' ';
        if ($first) $name .= $first . ' ';
        if ($middle) $name .= $middle . ' ';
        if ($last) $name .= $last;
        if ($suffix) $name .= ', ' . $suffix;
        if ($nickname) $name .= ' "' . $nickname . '"';

        return trim($name);
    }
  }

  /**
   * Get birth information
   */
  public function get_birth_info()
  {
    return array(
      'date' => $this->get('birth_date'),
      'place' => $this->get('birth_place'),
      'estimated' => $this->get('birth_date_estimated')
    );
  }

  /**
   * Get death information
   */
  public function get_death_info()
  {
    return array(
      'date' => $this->get('death_date'),
      'place' => $this->get('death_place'),
      'estimated' => $this->get('death_date_estimated')
    );
  }

  /**
   * Get person's age
   */
  public function get_age($on_date = null)
  {
    $birth_date = $this->get('birth_date');
    $death_date = $this->get('death_date');

    if (!$birth_date) {
      return null;
    }

    $birth = $this->parse_date($birth_date);
    if (!$birth) {
      return null;
    }

    if ($death_date) {
      $death = $this->parse_date($death_date);
      if ($death) {
        return $death->diff($birth)->y;
      }
    }

    if ($on_date) {
      $target = $this->parse_date($on_date);
      if ($target) {
        return $target->diff($birth)->y;
      }
    }

    if ($this->get('living')) {
      return date_create()->diff($birth)->y;
    }

    return null;
  }

  /**
   * Get parents
   */
  public function get_parents()
  {
    $father_id = $this->get('father_id');
    $mother_id = $this->get('mother_id');

    $parents = array();

    if ($father_id) {
      $parents['father'] = new HP_Person($father_id);
    }

    if ($mother_id) {
      $parents['mother'] = new HP_Person($mother_id);
    }

    return $parents;
  }

  /**
   * Get children
   */
  public function get_children()
  {
    $table_name = $this->wpdb->prefix . 'hp_persons';

    $children = $this->wpdb->get_results(
      $this->wpdb->prepare(
        "SELECT * FROM $table_name
                WHERE father_id = %d OR mother_id = %d
                ORDER BY birth_date ASC",
        $this->id,
        $this->id
      ),
      ARRAY_A
    );

    $child_objects = array();
    foreach ($children as $child) {
      $child_objects[] = new HP_Person($child['id']);
    }

    return $child_objects;
  }

  /**
   * Get families (as spouse)
   */
  public function get_families()
  {
    $families_table = $this->wpdb->prefix . 'hp_families';

    $families = $this->wpdb->get_results(
      $this->wpdb->prepare(
        "SELECT * FROM $families_table
                WHERE husband_id = %d OR wife_id = %d
                ORDER BY marriage_date ASC",
        $this->id,
        $this->id
      ),
      ARRAY_A
    );

    $family_objects = array();
    foreach ($families as $family) {
      $family_objects[] = new HP_Family($family['id']);
    }

    return $family_objects;
  }

  /**
   * Sanitize data for database
   */
  private function sanitize_data($data)
  {
    $sanitized = array();

    $text_fields = array(
      'gedcom_id',
      'tree_id',
      'first_name',
      'middle_name',
      'last_name',
      'maiden_name',
      'nickname',
      'prefix',
      'suffix',
      'birth_date',
      'birth_place',
      'death_date',
      'death_place',
      'burial_date',
      'burial_place',
      'occupation',
      'education',
      'religion'
    );

    $int_fields = array(
      'father_id',
      'mother_id',
      'primary_photo_id',
      'created_by',
      'modified_by'
    );

    $bool_fields = array(
      'birth_date_estimated',
      'death_date_estimated',
      'private',
      'living'
    );

    foreach ($text_fields as $field) {
      if (isset($data[$field])) {
        $sanitized[$field] = sanitize_text_field($data[$field]);
      }
    }

    foreach ($int_fields as $field) {
      if (isset($data[$field])) {
        $sanitized[$field] = intval($data[$field]);
      }
    }

    foreach ($bool_fields as $field) {
      if (isset($data[$field])) {
        $sanitized[$field] = $data[$field] ? 1 : 0;
      }
    }

    if (isset($data['gender'])) {
      $sanitized['gender'] = in_array($data['gender'], array('M', 'F', 'U')) ? $data['gender'] : 'U';
    }

    if (isset($data['notes'])) {
      $sanitized['notes'] = wp_kses_post($data['notes']);
    }

    return $sanitized;
  }

  /**
   * Get data format for wpdb operations
   */
  private function get_data_format($data)
  {
    $format = array();

    foreach ($data as $key => $value) {
      if (in_array($key, array('father_id', 'mother_id', 'primary_photo_id', 'created_by', 'modified_by', 'birth_date_estimated', 'death_date_estimated', 'private', 'living'))) {
        $format[] = '%d';
      } else {
        $format[] = '%s';
      }
    }

    return $format;
  }

  /**
   * Parse date string to DateTime object
   */
  private function parse_date($date_string)
  {
    if (!$date_string) {
      return null;
    }

    // Try various date formats
    $formats = array(
      'Y-m-d',
      'd M Y',
      'M Y',
      'Y',
      'd/m/Y',
      'm/d/Y'
    );

    foreach ($formats as $format) {
      $date = DateTime::createFromFormat($format, $date_string);
      if ($date !== false) {
        return $date;
      }
    }

    return null;
  }

  /**
   * Static method to search persons
   */
  public static function search($args = array())
  {
    global $wpdb;

    $defaults = array(
      'tree_id' => 'main',
      'search_term' => '',
      'limit' => 50,
      'offset' => 0,
      'order_by' => 'last_name',
      'order' => 'ASC'
    );

    $args = wp_parse_args($args, $defaults);

    $table_name = $wpdb->prefix . 'hp_persons';
    $where_clauses = array();
    $where_values = array();

    // Tree filter
    $where_clauses[] = "tree_id = %s";
    $where_values[] = $args['tree_id'];

    // Search term
    if (!empty($args['search_term'])) {
      $where_clauses[] = "(first_name LIKE %s OR middle_name LIKE %s OR last_name LIKE %s OR maiden_name LIKE %s OR nickname LIKE %s)";
      $search_term = '%' . $wpdb->esc_like($args['search_term']) . '%';
      $where_values = array_merge($where_values, array($search_term, $search_term, $search_term, $search_term, $search_term));
    }

    $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

    $order_sql = sprintf(
      'ORDER BY %s %s',
      sanitize_sql_orderby($args['order_by']),
      $args['order'] === 'DESC' ? 'DESC' : 'ASC'
    );

    $limit_sql = sprintf('LIMIT %d OFFSET %d', intval($args['limit']), intval($args['offset']));

    $sql = "SELECT * FROM $table_name $where_sql $order_sql $limit_sql";

    if (!empty($where_values)) {
      $sql = $wpdb->prepare($sql, $where_values);
    }

    $results = $wpdb->get_results($sql, ARRAY_A);

    $persons = array();
    foreach ($results as $result) {
      $persons[] = new HP_Person($result['id']);
    }

    return $persons;
  }
}
