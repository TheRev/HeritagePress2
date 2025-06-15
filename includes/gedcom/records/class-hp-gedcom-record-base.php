<?php

/**
 * HeritagePress GEDCOM Record Base Class
 *
 * Base class for all GEDCOM record handlers
 *
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

abstract class HP_GEDCOM_Record_Base
{
  /**
   * Tree ID
   *
   * @var string
   */
  protected $tree_id = 'main';

  /**
   * Processed record IDs
   *
   * @var array
   */
  protected $processed_ids = array();

  /**
   * Database object
   *
   * @var object
   */
  protected $db;

  /**
   * Constructor
   */
  public function __construct()
  {
    global $wpdb;
    $this->db = $wpdb;
  }

  /**
   * Set tree ID
   *
   * @param string $tree_id Tree ID
   */
  public function set_tree_id($tree_id)
  {
    $this->tree_id = $tree_id;
  }

  /**
   * Process a record
   *
   * @param array $record Record data
   * @return mixed Processing result
   */
  abstract public function process($record);

  /**
   * Finalize processing after all records are processed
   */
  public function finalize()
  {
    // Override in child classes if needed
  }

  /**
   * Get processed record IDs
   *
   * @return array Processed record IDs
   */
  public function get_processed_ids()
  {
    return $this->processed_ids;
  }

  /**
   * Find a value in record hierarchy
   *
   * @param array  $record Record data
   * @param string $tag    Tag to find
   * @param int    $level  Level to search (relative to record)
   * @return string|false  Value or false if not found
   */
  protected function find_value($record, $tag, $level = 1)
  {
    if (!isset($record['children'])) {
      return false;
    }

    foreach ($record['children'] as $child) {
      if ($child['tag'] === $tag) {
        return isset($child['value']) ? $child['value'] : '';
      }
    }

    return false;
  }

  /**
   * Find all occurrences of a tag in record hierarchy
   *
   * @param array  $record Record data
   * @param string $tag    Tag to find
   * @param int    $level  Level to search (relative to record)
   * @return array Found nodes
   */
  protected function find_all($record, $tag, $level = 1)
  {
    $results = array();

    if (!isset($record['children'])) {
      return $results;
    }

    foreach ($record['children'] as $child) {
      if ($child['tag'] === $tag) {
        $results[] = $child;
      }
    }

    return $results;
  }

  /**
   * Find a node in record hierarchy
   *
   * @param array  $record Record data
   * @param string $tag    Tag to find
   * @param int    $level  Level to search (relative to record)
   * @return array|false   Node or false if not found
   */
  protected function find_node($record, $tag, $level = 1)
  {
    if (!isset($record['children'])) {
      return false;
    }

    foreach ($record['children'] as $child) {
      if ($child['tag'] === $tag) {
        return $child;
      }
    }

    return false;
  }
}
