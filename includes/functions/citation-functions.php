<?php

/**
 * Citation Modal Functions
 *
 * Global functions to handle citation modal functionality across HeritagePress.
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
  exit;
}

/**
 * Output citation modal trigger
 *
 * @param string $persfamID Person/Family ID
 * @param string $tree Tree identifier
 * @param string $eventID Optional event ID
 * @param string $noteID Optional note ID
 * @param string $text Button text
 */
function heritagepress_citation_modal_trigger($persfamID, $tree, $eventID = '', $noteID = '', $text = 'Manage Citations')
{
  // Ensure all parameters are strings
  $persfamID = is_string($persfamID) ? $persfamID : '';
  $tree = is_string($tree) ? $tree : '';
  $eventID = is_string($eventID) ? $eventID : '';
  $noteID = is_string($noteID) ? $noteID : '';
  $text = is_string($text) ? $text : 'Manage Citations';

  printf(
    '<a href="#" class="citation-modal-trigger" data-persfamid="%s" data-tree="%s" data-eventid="%s" data-noteid="%s">%s</a>',
    esc_attr($persfamID),
    esc_attr($tree),
    esc_attr($eventID),
    esc_attr($noteID),
    esc_html($text)
  );
}

/**
 * Output citation modal button
 *
 * @param string $persfamID Person/Family ID
 * @param string $tree Tree identifier
 * @param string $eventID Optional event ID
 * @param string $noteID Optional note ID
 * @param string $text Button text
 * @param string $class Button CSS class
 */
function heritagepress_citation_modal_button($persfamID, $tree, $eventID = '', $noteID = '', $text = 'Citations', $class = 'button')
{
  // Ensure all parameters are strings
  $persfamID = is_string($persfamID) ? $persfamID : '';
  $tree = is_string($tree) ? $tree : '';
  $eventID = is_string($eventID) ? $eventID : '';
  $noteID = is_string($noteID) ? $noteID : '';
  $text = is_string($text) ? $text : 'Citations';
  $class = is_string($class) ? $class : 'button';

  printf(
    '<button type="button" class="citation-modal-trigger %s" data-persfamid="%s" data-tree="%s" data-eventid="%s" data-noteid="%s">%s</button>',
    esc_attr($class),
    esc_attr($persfamID),
    esc_attr($tree),
    esc_attr($eventID),
    esc_attr($noteID),
    esc_html($text)
  );
}

/**
 * Output citation count with modal link
 *
 * @param string $persfamID Person/Family ID
 * @param string $tree Tree identifier
 * @param string $eventID Optional event ID
 * @param string $noteID Optional note ID
 */
function heritagepress_citation_count_link($persfamID, $tree, $eventID = '', $noteID = '')
{
  global $wpdb;

  // Ensure all parameters are strings
  $persfamID = is_string($persfamID) ? $persfamID : '';
  $tree = is_string($tree) ? $tree : '';
  $eventID = is_string($eventID) ? $eventID : '';
  $noteID = is_string($noteID) ? $noteID : '';

  $citations_table = $wpdb->prefix . 'hp_citations';
  $where_clause = "WHERE gedcom = %s AND persfamID = %s";
  $params = array($tree, $persfamID);

  if (!empty($eventID)) {
    $where_clause .= " AND eventID = %s";
    $params[] = $eventID;
  }

  if (!empty($noteID)) {
    $where_clause .= " OR persfamID = %s";
    $params[] = $noteID;
  }

  $query = $wpdb->prepare("SELECT COUNT(*) FROM $citations_table $where_clause", $params);
  $count = (int)$wpdb->get_var($query);

  if ($count > 0) {
    printf(
      '<a href="#" class="citation-modal-trigger" data-persfamid="%s" data-tree="%s" data-eventid="%s" data-noteid="%s">%s</a>',
      esc_attr($persfamID),
      esc_attr($tree),
      esc_attr($eventID),
      esc_attr($noteID),
      esc_html(sprintf(_n('%d Citation', '%d Citations', $count, 'heritagepress'), $count))
    );
  } else {
    printf(
      '<a href="#" class="citation-modal-trigger" data-persfamid="%s" data-tree="%s" data-eventid="%s" data-noteid="%s">%s</a>',
      esc_attr($persfamID),
      esc_attr($tree),
      esc_attr($eventID),
      esc_attr($noteID),
      esc_html__('Add Citation', 'heritagepress')
    );
  }
}

/**
 * Check if current page should load citation modal assets
 *
 * @return bool True if citation modal assets should be loaded
 */
function heritagepress_should_load_citation_modal()
{
  $current_screen = get_current_screen();
  if (!$current_screen) {
    return false;
  }

  // Ensure we have a valid screen ID
  $screen_id = isset($current_screen->id) ? (string)$current_screen->id : '';
  if (empty($screen_id)) {
    return false;
  }

  // Check if we're on a HeritagePress admin page using string operation with guaranteed string values
  return strpos($screen_id, 'hp-') !== false ||
    strpos($screen_id, 'heritagepress') !== false;
}
