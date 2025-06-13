<?php

/**
 * HeritagePress Family Class
 *
 * Handles family relationship data
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Family
{
  /**
   * Family ID
   */
  public $id;

  /**
   * GEDCOM ID
   */
  public $gedcom_id;

  /**
   * Tree ID
   */
  public $tree_id;

  /**
   * Husband ID
   */
  public $husband_id;

  /**
   * Wife ID
   */
  public $wife_id;

  /**
   * Marriage date
   */
  public $marriage_date;

  /**
   * Marriage place
   */
  public $marriage_place;

  /**
   * Constructor
   */
  public function __construct($data = array())
  {
    if (!empty($data)) {
      $this->populate($data);
    }
  }

  /**
   * Populate object with data
   */
  public function populate($data)
  {
    foreach ($data as $key => $value) {
      if (property_exists($this, $key)) {
        $this->$key = $value;
      }
    }
  }

  /**
   * Save family to database
   */
  public function save()
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hp_families';

    $data = array(
      'gedcom_id' => $this->gedcom_id,
      'tree_id' => $this->tree_id ?: 'main',
      'husband_id' => $this->husband_id,
      'wife_id' => $this->wife_id,
      'marriage_date' => $this->marriage_date,
      'marriage_place' => $this->marriage_place
    );

    if ($this->id) {
      $wpdb->update($table_name, $data, array('id' => $this->id));
    } else {
      $wpdb->insert($table_name, $data);
      $this->id = $wpdb->insert_id;
    }

    return $this->id;
  }

  /**
   * Get family by ID
   */
  public static function get_by_id($id)
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hp_families';

    $family_data = $wpdb->get_row(
      $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id),
      ARRAY_A
    );

    if ($family_data) {
      return new self($family_data);
    }

    return null;
  }

  /**
   * Get children of this family
   */
  public function get_children()
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hp_children';

    $children = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT child_id FROM $table_name WHERE family_id = %s ORDER BY sort_order",
        $this->gedcom_id
      )
    );

    $child_objects = array();
    foreach ($children as $child) {
      $person = HP_Person::get_by_gedcom_id($child->child_id, $this->tree_id);
      if ($person) {
        $child_objects[] = $person;
      }
    }

    return $child_objects;
  }
}
