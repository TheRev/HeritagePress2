<?php

/**
 * Browse People Tab
 *
 * This sub-view provides the people browsing/listing interface
 * for the HeritagePress plugin. It includes search and filter
 * options, tree selection, and pagination.
 * This file is part of the HeritagePress plugin.
 * @package HeritagePress
 *
 */

if (!defined('ABSPATH')) {
  exit;
}

// Include date utilities for enhanced date filtering
require_once __DIR__ . '/../../helpers/class-hp-date-utils.php';

// Enqueue enhanced browse people scripts and styles
wp_enqueue_script('jquery-ui-tooltip');
wp_enqueue_script('heritagepress-browse-people-enhanced', plugin_dir_url(__FILE__) . '../../../public/js/browse-people-enhanced.js', array('jquery', 'jquery-ui-tooltip'), '1.0.0', true);

global $wpdb;

// Table references
$people_table = $wpdb->prefix . 'hp_people';
$trees_table = $wpdb->prefix . 'hp_trees';
$children_table = $wpdb->prefix . 'hp_children';
$families_table = $wpdb->prefix . 'hp_families';

// Get tree data for dropdowns
$trees_result = $wpdb->get_results("SELECT * FROM $trees_table ORDER BY treename", ARRAY_A);

// Get total counts including unassigned people
$total_people_count = $wpdb->get_var("SELECT COUNT(*) FROM $people_table WHERE NOT (firstname = '** LOCKED **' AND lastname = '** RESERVED **')");
$unassigned_people_count = $wpdb->get_var("SELECT COUNT(*) FROM $people_table WHERE gedcom NOT IN (SELECT gedcom FROM $trees_table) AND NOT (firstname = '** LOCKED **' AND lastname = '** RESERVED **')");

// Check if we need to show warnings
$show_no_trees_warning = empty($trees_result);
$show_unassigned_warning = $unassigned_people_count > 0;
// Get people counts per tree (even if tree doesn't exist in trees table)
$tree_counts = array();
foreach ($trees_result as $tree) {
  $tree_counts[$tree['gedcom']] = $wpdb->get_var(
    $wpdb->prepare("SELECT COUNT(*) FROM $people_table WHERE gedcom = %s AND NOT (firstname = '** LOCKED **' AND lastname = '** RESERVED **')", $tree['gedcom'])
  );
}

// Helper function to get spouse and partner information for a person
function get_person_relationships($person_id, $gedcom, $wpdb, $families_table, $people_table)
{
  // Find families where this person is either husband or wife
  $families = $wpdb->get_results(
    $wpdb->prepare(
      "SELECT f.*,
       h.firstname as husband_firstname, h.lastname as husband_lastname, h.lnprefix as husband_lnprefix,
       w.firstname as wife_firstname, w.lastname as wife_lastname, w.lnprefix as wife_lnprefix
       FROM $families_table f
       LEFT JOIN $people_table h ON f.husband = h.personID AND f.gedcom = h.gedcom
       LEFT JOIN $people_table w ON f.wife = w.personID AND f.gedcom = w.gedcom
       WHERE f.gedcom = %s AND (f.husband = %s OR f.wife = %s)
       ORDER BY f.marrdatetr",
      $gedcom,
      $person_id,
      $person_id
    ),
    ARRAY_A
  );

  $spouses = array();
  $partners = array();

  foreach ($families as $family) {
    $is_husband = ($family['husband'] === $person_id);
    $spouse_id = $is_husband ? $family['wife'] : $family['husband'];

    if (!empty($spouse_id)) {
      $spouse_name_parts = array();
      $spouse_firstname = $is_husband ? $family['wife_firstname'] : $family['husband_firstname'];
      $spouse_lastname = $is_husband ? $family['wife_lastname'] : $family['husband_lastname'];
      $spouse_lnprefix = $is_husband ? $family['wife_lnprefix'] : $family['husband_lnprefix'];

      if (!empty($spouse_firstname)) $spouse_name_parts[] = $spouse_firstname;
      if (!empty($spouse_lnprefix)) $spouse_name_parts[] = $spouse_lnprefix;
      if (!empty($spouse_lastname)) $spouse_name_parts[] = $spouse_lastname;

      $spouse_name = implode(' ', $spouse_name_parts);

      // Determine if this is a spouse (married) or partner (unmarried/divorced)
      if (!empty($family['marrdate']) && empty($family['divdate'])) {
        $spouses[] = array(
          'name' => $spouse_name,
          'id' => $spouse_id,
          'marriage_date' => $family['marrdate'],
          'marriage_place' => $family['marrplace']
        );
      } else {
        $partners[] = array(
          'name' => $spouse_name,
          'id' => $spouse_id,
          'relationship_type' => !empty($family['divdate']) ? 'former spouse' : 'partner'
        );
      }
    }
  }

  return array('spouses' => $spouses, 'partners' => $partners);
}

// Get search parameters
$search_params = array(
  'searchstring' => isset($_GET['searchstring']) ? sanitize_text_field($_GET['searchstring']) : '',
  'tree' => isset($_GET['tree']) ? sanitize_text_field($_GET['tree']) : '',
  'living' => isset($_GET['living']) ? sanitize_text_field($_GET['living']) : '',
  'private' => isset($_GET['private']) ? sanitize_text_field($_GET['private']) : '',
  'exactmatch' => isset($_GET['exactmatch']) ? sanitize_text_field($_GET['exactmatch']) : '',
  'nokids' => isset($_GET['nokids']) ? sanitize_text_field($_GET['nokids']) : '',
  'noparents' => isset($_GET['noparents']) ? sanitize_text_field($_GET['noparents']) : '',
  'nospouse' => isset($_GET['nospouse']) ? sanitize_text_field($_GET['nospouse']) : '',
  'order' => isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'name',
  'page' => isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1,
  // Enhanced date filtering
  'birth_date_range' => isset($_GET['birth_date_range']) ? sanitize_text_field($_GET['birth_date_range']) : '',
  'death_date_range' => isset($_GET['death_date_range']) ? sanitize_text_field($_GET['death_date_range']) : ''
);

// Pagination settings
$per_page = 25;
$offset = ($search_params['page'] - 1) * $per_page;

// Build WHERE clause
$where_conditions = array();

// Exclude locked/reserved records
$where_conditions[] = "NOT ($people_table.firstname = '** LOCKED **' AND $people_table.lastname = '** RESERVED **')";

if (!empty($search_params['tree'])) {
  $where_conditions[] = $wpdb->prepare("$people_table.gedcom = %s", $search_params['tree']);
}

if (!empty($search_params['searchstring'])) {
  $search_term = $search_params['searchstring'];
  $operator = ($search_params['exactmatch'] === 'yes') ? '=' : 'LIKE';

  if ($operator === 'LIKE') {
    $search_term = '%' . $wpdb->esc_like($search_term) . '%';
  }

  $search_conditions = array();
  $search_conditions[] = $wpdb->prepare("$people_table.personID $operator %s", $search_term);
  $search_conditions[] = $wpdb->prepare("CONCAT_WS(' ', TRIM($people_table.firstname), TRIM($people_table.lastname)) $operator %s", $search_term);
  $search_conditions[] = $wpdb->prepare("CONCAT_WS(' ', TRIM($people_table.lnprefix), TRIM($people_table.lastname)) $operator %s", $search_term);

  $where_conditions[] = '(' . implode(' OR ', $search_conditions) . ')';
}

if ($search_params['living'] === 'yes') {
  $where_conditions[] = "$people_table.living = 1";
}

if ($search_params['private'] === 'yes') {
  $where_conditions[] = "$people_table.private = 1";
}

// Build JOIN clauses for advanced filters
$join_clauses = array();

if ($search_params['noparents'] === 'yes') {
  $join_clauses[] = "LEFT JOIN $children_table as noparents ON $people_table.personID = noparents.personID AND $people_table.gedcom = noparents.gedcom";
  $where_conditions[] = "noparents.familyID IS NULL";
}

if ($search_params['nospouse'] === 'yes') {
  $join_clauses[] = "LEFT JOIN $families_table as nospousef ON $people_table.personID = nospousef.husband AND $people_table.gedcom = nospousef.gedcom";
  $join_clauses[] = "LEFT JOIN $families_table as nospousem ON $people_table.personID = nospousem.wife AND $people_table.gedcom = nospousem.gedcom";
  $where_conditions[] = "nospousef.familyID IS NULL AND nospousem.familyID IS NULL";
}

if ($search_params['nokids'] === 'yes') {
  $join_clauses[] = "LEFT OUTER JOIN $families_table AS familiesH ON $people_table.gedcom=familiesH.gedcom AND $people_table.personID=familiesH.husband";
  $join_clauses[] = "LEFT OUTER JOIN $families_table AS familiesW ON $people_table.gedcom=familiesW.gedcom AND $people_table.personID=familiesW.wife";
  $join_clauses[] = "LEFT OUTER JOIN $children_table AS childrenH ON familiesH.gedcom=childrenH.gedcom AND familiesH.familyID=childrenH.familyID";
  $join_clauses[] = "LEFT OUTER JOIN $children_table AS childrenW ON familiesW.gedcom=childrenW.gedcom AND familiesW.familyID=childrenW.familyID";
}

// Build ORDER BY clause
$order_by = 'lastname, firstname';
switch ($search_params['order']) {
  case 'id':
    $order_by = 'personID';
    break;
  case 'idup':
    $order_by = 'personID DESC';
    break;
  case 'name':
    $order_by = 'lastname, firstname';
    break;
  case 'nameup':
    $order_by = 'lastname DESC, firstname DESC';
    break;
  case 'birth':
    $order_by = 'birthdatetr, lastname, firstname';
    break;
  case 'birthup':
    $order_by = 'birthdatetr DESC, lastname, firstname';
    break;
  case 'death':
    $order_by = 'deathdatetr, lastname, firstname';
    break;
  case 'deathup':
    $order_by = 'deathdatetr DESC, lastname, firstname';
    break;
  case 'change':
    $order_by = 'changedate, lastname, firstname';
    break;
  case 'changeup':
    $order_by = 'changedate DESC, lastname, firstname';
    break;
}

// Get group by clause for nokids filter
$group_by = '';
$having = '';
$select_extra = '';

if ($search_params['nokids'] === 'yes') {
  $select_extra = ', SUM((childrenH.familyID IS NOT NULL) + (childrenW.familyID IS NOT NULL)) AS ChildrenCount';
  $group_by = " GROUP BY $people_table.personID, $people_table.lastname, $people_table.firstname, $people_table.lnprefix, $people_table.prefix, $people_table.suffix, $people_table.nameorder, $people_table.birthdate, $people_table.altbirthdate, $people_table.gedcom, $trees_table.treename, $people_table.birthdatetr, $people_table.altbirthdatetr";
  $having = ' HAVING ChildrenCount = 0';
}

// Build main query
$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
$join_clause = !empty($join_clauses) ? implode(' ', $join_clauses) : '';

// Count total records - also use LEFT JOIN for count
$count_query = "SELECT COUNT(*) as total FROM (
  SELECT $people_table.personID $select_extra
  FROM $people_table
  LEFT JOIN $trees_table ON $people_table.gedcom = $trees_table.gedcom
  $join_clause
  $where_clause
  $group_by
  $having
) as count_table";

$total_count = $wpdb->get_var($count_query);
$total_pages = ceil($total_count / $per_page);

// Main query with pagination - LEFT JOIN to handle people without trees
$main_query = "SELECT $people_table.*, COALESCE($trees_table.treename, '[No Tree Assigned]') as treename,
  DATE_FORMAT($people_table.changedate, '%d %b %Y %H:%i:%s') as changedate_formatted
  $select_extra
  FROM $people_table
  LEFT JOIN $trees_table ON $people_table.gedcom = $trees_table.gedcom
  $join_clause
  $where_clause
  $group_by
  $having
  ORDER BY $order_by
  LIMIT $per_page OFFSET $offset";

$people_results = $wpdb->get_results($main_query, ARRAY_A);

// Pagination info
$start_item = ($search_params['page'] - 1) * $per_page + 1;
$end_item = min($search_params['page'] * $per_page, $total_count);

// Build sort links
function get_sort_link($field, $current_order)
{
  $params = $_GET;
  unset($params['paged']); // Reset to first page when sorting

  if ($current_order === $field) {
    $params['order'] = $field . 'up';
  } elseif ($current_order === $field . 'up') {
    $params['order'] = $field;
  } else {
    $params['order'] = $field;
  }

  return admin_url('admin.php?' . http_build_query($params));
}

function get_column_class($field, $current_order)
{
  if ($current_order === $field || $current_order === $field . 'up') {
    return 'manage-column column-' . $field . ' sorted';
  }
  return 'manage-column column-' . $field . ' sortable';
}

function get_sort_indicator($field, $current_order)
{
  if ($current_order === $field) {
    return '<span class="sorting-indicator dashicons dashicons-arrow-up-alt2" title="Sorted ascending - click to sort descending"></span>';
  } elseif ($current_order === $field . 'up') {
    return '<span class="sorting-indicator dashicons dashicons-arrow-down-alt2" title="Sorted descending - click to sort ascending"></span>';
  }
  return '<span class="sorting-indicator dashicons dashicons-sort" title="Click to sort"></span>';
}

$id_sort_link = get_sort_link('id', $search_params['order']);
$name_sort_link = get_sort_link('name', $search_params['order']);
$birth_sort_link = get_sort_link('birth', $search_params['order']);
$death_sort_link = get_sort_link('death', $search_params['order']);
$change_sort_link = get_sort_link('change', $search_params['order']);

// Get column classes for sort state indication
$id_column_class = get_column_class('id', $search_params['order']);
$name_column_class = get_column_class('name', $search_params['order']);
$birth_column_class = get_column_class('birth', $search_params['order']);
$death_column_class = get_column_class('death', $search_params['order']);
$change_column_class = get_column_class('change', $search_params['order']);

// Get sort indicators
$id_sort_indicator = get_sort_indicator('id', $search_params['order']);
$name_sort_indicator = get_sort_indicator('name', $search_params['order']);
$birth_sort_indicator = get_sort_indicator('birth', $search_params['order']);
$death_sort_indicator = get_sort_indicator('death', $search_params['order']);
$change_sort_indicator = get_sort_indicator('change', $search_params['order']);
?>

<div class="people-browse-section">

  <!-- Tree and People Status Warnings -->
  <?php if ($show_no_trees_warning): ?>
    <div class="notice notice-warning">
      <p>
        <strong><?php _e('No Family Trees Found!', 'heritagepress'); ?></strong>
        <?php printf(
          __('You need to create at least one family tree before organizing your people. <a href="%s">Create your first tree here</a>.', 'heritagepress'),
          admin_url('admin.php?page=heritagepress-trees&tab=add')
        ); ?>
      </p>
    </div>
  <?php elseif ($show_unassigned_warning): ?>
    <div class="notice notice-info">
      <p>
        <strong><?php printf(__('%d people are not assigned to any tree.', 'heritagepress'), $unassigned_people_count); ?></strong>
        <?php printf(
          __('Consider <a href="%s">creating additional trees</a> or editing people to assign them to existing trees.', 'heritagepress'),
          admin_url('admin.php?page=heritagepress-trees&tab=add')
        ); ?>
      </p>
    </div>
  <?php endif; ?>

  <?php if ($total_people_count == 0): ?>
    <div class="notice notice-info">
      <p>
        <strong><?php _e('No People Found!', 'heritagepress'); ?></strong>
        <?php printf(
          __('Get started by <a href="%s">adding your first person</a> to begin building your family tree.', 'heritagepress'),
          admin_url('admin.php?page=heritagepress-people&tab=add')
        ); ?>
      </p>
    </div>
  <?php endif; ?>

  <!-- Search Form -->
  <div class="search-form-card">
    <form method="get" id="people-search-form" class="people-search-form">
      <input type="hidden" name="page" value="heritagepress-people">
      <input type="hidden" name="tab" value="browse">

      <div class="search-controls">
        <div class="search-field">
          <label for="searchstring"><?php _e('Search People:', 'heritagepress'); ?></label>
          <input type="text" id="searchstring" name="searchstring" value="<?php echo esc_attr($search_params['searchstring']); ?>" placeholder="<?php _e('Enter name or person ID...', 'heritagepress'); ?>" />
        </div>

        <div class="search-field">
          <label for="tree"><?php _e('Tree:', 'heritagepress'); ?></label>
          <select id="tree" name="tree">
            <option value=""><?php _e('All Trees', 'heritagepress'); ?></option>
            <?php foreach ($trees_result as $tree_row): ?>
              <option value="<?php echo esc_attr($tree_row['gedcom']); ?>" <?php selected($search_params['tree'], $tree_row['gedcom']); ?>>
                <?php echo esc_html($tree_row['treename']); ?> (<?php echo number_format($tree_counts[$tree_row['gedcom']]); ?>)
              </option> <?php endforeach; ?>
          </select>
        </div>

        <div class="search-field search-buttons">
          <input type="submit" class="button button-primary" value="<?php _e('Search', 'heritagepress'); ?>" />
          <a href="<?php echo admin_url('admin.php?page=heritagepress-people&tab=browse'); ?>" class="button"><?php _e('Clear', 'heritagepress'); ?></a>
        </div>
      </div>

      <!-- Advanced Search Options -->
      <div class="advanced-search-toggle">
        <button type="button" class="button button-link" id="toggle-advanced"><?php _e('Advanced Options', 'heritagepress'); ?> <span class="dashicons dashicons-arrow-down-alt2"></span></button>
      </div>

      <div class="advanced-search-options" id="advanced-options" style="display: none;">
        <div class="option-row">
          <label>
            <input type="checkbox" name="exactmatch" value="yes" <?php checked($search_params['exactmatch'], 'yes'); ?> />
            <?php _e('Exact match only', 'heritagepress'); ?>
          </label>

          <label>
            <input type="checkbox" name="living" value="yes" <?php checked($search_params['living'], 'yes'); ?> />
            <?php _e('Living people only', 'heritagepress'); ?>
          </label>

          <label>
            <input type="checkbox" name="private" value="yes" <?php checked($search_params['private'], 'yes'); ?> />
            <?php _e('Private people only', 'heritagepress'); ?>
          </label>
        </div>

        <div class="option-row">
          <label>
            <input type="checkbox" name="noparents" value="yes" <?php checked($search_params['noparents'], 'yes'); ?> />
            <?php _e('No parents', 'heritagepress'); ?>
          </label>

          <label>
            <input type="checkbox" name="nospouse" value="yes" <?php checked($search_params['nospouse'], 'yes'); ?> />
            <?php _e('No spouse', 'heritagepress'); ?>
          </label>

          <label>
            <input type="checkbox" name="nokids" value="yes" <?php checked($search_params['nokids'], 'yes'); ?> />
            <?php _e('No children', 'heritagepress'); ?>
          </label>
        </div>

        <div class="option-row date-range-options">
          <label for="birth_date_range"><?php _e('Birth Date Range:', 'heritagepress'); ?></label>
          <input type="text" id="birth_date_range" name="birth_date_range" value="<?php echo esc_attr($search_params['birth_date_range']); ?>" class="date-range-picker" placeholder="<?php _e('YYYY-MM-DD to YYYY-MM-DD', 'heritagepress'); ?>" />

          <label for="death_date_range"><?php _e('Death Date Range:', 'heritagepress'); ?></label>
          <input type="text" id="death_date_range" name="death_date_range" value="<?php echo esc_attr($search_params['death_date_range']); ?>" class="date-range-picker" placeholder="<?php _e('YYYY-MM-DD to YYYY-MM-DD', 'heritagepress'); ?>" />
        </div>
      </div>
    </form>
  </div>

  <!-- Results Section -->
  <div class="people-results">
    <!-- Bulk Actions -->
    <form method="post" id="bulk-action-form">
      <?php wp_nonce_field('heritagepress_bulk_people', '_wpnonce'); ?>

      <div class="tablenav top">
        <div class="alignleft actions bulkactions">
          <label for="bulk-action-selector-top" class="screen-reader-text"><?php _e('Select bulk action', 'heritagepress'); ?></label>
          <select name="action" id="bulk-action-selector-top">
            <option value="-1"><?php _e('Bulk Actions', 'heritagepress'); ?></option>
            <option value="delete"><?php _e('Delete', 'heritagepress'); ?></option>
            <option value="make_private"><?php _e('Mark as Private', 'heritagepress'); ?></option>
            <option value="make_public"><?php _e('Mark as Public', 'heritagepress'); ?></option>
          </select>
          <input type="submit" id="doaction" class="button action" value="<?php _e('Apply', 'heritagepress'); ?>">
        </div>

        <div class="alignright">
          <span class="displaying-num">
            <?php printf(_n('%s item', '%s items', $total_count, 'heritagepress'), number_format_i18n($total_count)); ?>
          </span>
        </div>

        <div class="tablenav-pages">
          <?php if ($total_pages > 1): ?>
            <?php
            $page_links = paginate_links(array(
              'base' => add_query_arg('paged', '%#%'),
              'format' => '',
              'prev_text' => __('&laquo;'),
              'next_text' => __('&raquo;'),
              'total' => $total_pages,
              'current' => $search_params['page']
            ));
            echo $page_links;
            ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- People Table -->
      <table class="wp-list-table widefat fixed striped people-table">
        <thead>
          <tr>
            <td id="cb" class="manage-column column-cb check-column">
              <label class="screen-reader-text" for="cb-select-all-1"><?php _e('Select All', 'heritagepress'); ?></label>
              <input id="cb-select-all-1" type="checkbox" />
            </td>
            <th scope="col" class="manage-column column-photo"><?php _e('Photo', 'heritagepress'); ?></th>
            <?php if (count($trees_result) > 1 || $show_no_trees_warning || $show_unassigned_warning): ?>
              <th scope="col" class="manage-column column-tree"><?php _e('Tree', 'heritagepress'); ?></th>
            <?php endif; ?>
            <th scope="col" class="<?php echo esc_attr($id_column_class); ?>">
              <a href="<?php echo esc_url($id_sort_link); ?>">
                <span><?php _e('Person ID', 'heritagepress'); ?></span>
                <?php echo $id_sort_indicator; ?>
              </a>
            </th>
            <th scope="col" class="<?php echo esc_attr($name_column_class); ?>">
              <a href="<?php echo esc_url($name_sort_link); ?>">
                <span><?php _e('Name', 'heritagepress'); ?></span>
                <?php echo $name_sort_indicator; ?>
              </a>
            </th>
            <th scope="col" class="<?php echo esc_attr($birth_column_class); ?>">
              <a href="<?php echo esc_url($birth_sort_link); ?>">
                <span><?php _e('Birth', 'heritagepress'); ?></span>
                <?php echo $birth_sort_indicator; ?>
              </a>
            </th>
            <th scope="col" class="<?php echo esc_attr($death_column_class); ?>">
              <a href="<?php echo esc_url($death_sort_link); ?>">
                <span><?php _e('Death', 'heritagepress'); ?></span>
                <?php echo $death_sort_indicator; ?>
              </a>
            </th>
            <th scope="col" class="manage-column column-spouse"><?php _e('Spouse', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column column-partner"><?php _e('Partner', 'heritagepress'); ?></th>
            <th scope="col" class="<?php echo esc_attr($change_column_class); ?>">
              <a href="<?php echo esc_url($change_sort_link); ?>">
                <span><?php _e('Last Changed', 'heritagepress'); ?></span>
                <?php echo $change_sort_indicator; ?>
              </a>
            </th>
          </tr>
        </thead>
        <tbody id="the-list">
          <?php if (!empty($people_results)): ?><?php foreach ($people_results as $person): ?> <tr id="person-<?php echo esc_attr($person['ID']); ?>" data-person-id="<?php echo esc_attr($person['personID']); ?>" data-gedcom="<?php echo esc_attr($person['gedcom']); ?>">
            <th scope="row" class="check-column">
              <input type="checkbox" name="selected_people[]" value="<?php echo esc_attr($person['ID']); ?>" />
            </th>
            <td class="column-photo">
              <!-- Photo placeholder - will be enhanced with actual photo display -->
              <div class="person-photo-placeholder">
                <span class="dashicons dashicons-admin-users"></span>
              </div>
            </td> <?php if (count($trees_result) > 1 || $show_no_trees_warning || $show_unassigned_warning): ?>
              <td class="column-tree">
                <?php if ($person['treename'] === '[No Tree Assigned]'): ?>
                  <span style="color: #d63384; font-weight: bold;">
                    <?php _e('[No Tree Assigned]', 'heritagepress'); ?>
                  </span>
                <?php else: ?>
                  <span class="tree-name">
                    <?php echo esc_html($person['treename']); ?>
                  </span>
                <?php endif; ?>
              </td>
            <?php endif; ?>
            <td class="column-personid">
              <strong>
                <a href="<?php echo admin_url('admin.php?page=heritagepress-people&tab=edit&personID=' . urlencode($person['personID']) . '&tree=' . urlencode($person['gedcom'])); ?>" class="row-title">
                  <?php echo esc_html($person['personID']); ?>
                </a>
              </strong>
              <div class="row-actions">
                <span class="edit">
                  <a href="<?php echo admin_url('admin.php?page=heritagepress-people&tab=edit&personID=' . urlencode($person['personID']) . '&tree=' . urlencode($person['gedcom'])); ?>"><?php _e('Edit', 'heritagepress'); ?></a>
                </span>
                <span class="view">
                  | <a href="#"
                    onclick="alert('Person view page coming soon!'); return false;"
                    title="<?php _e('View this person on frontend', 'heritagepress'); ?>"><?php _e('View', 'heritagepress'); ?></a>
                </span>
                <span class="delete">
                  | <a href="#"
                    onclick="if(confirm('<?php printf(__('Are you sure you want to delete %s? This action cannot be undone.', 'heritagepress'), esc_js($person['firstname'] . ' ' . $person['lastname'])); ?>')) { deletePerson('<?php echo esc_js($person['personID']); ?>', '<?php echo esc_js($person['gedcom']); ?>'); } return false;"
                    title="<?php _e('Delete this person', 'heritagepress'); ?>"
                    class="delete-link"><?php _e('Delete', 'heritagepress'); ?></a>
                </span>
              </div>
            </td>
            <td class="column-name">
              <div class="person-name">
                <?php
                                                  $name_parts = array();
                                                  if (!empty($person['prefix'])) $name_parts[] = $person['prefix'];
                                                  if (!empty($person['firstname'])) $name_parts[] = $person['firstname'];
                                                  if (!empty($person['lnprefix'])) $name_parts[] = $person['lnprefix'];
                                                  if (!empty($person['lastname'])) $name_parts[] = '<strong>' . $person['lastname'] . '</strong>';
                                                  if (!empty($person['suffix'])) $name_parts[] = $person['suffix'];

                                                  echo implode(' ', $name_parts);

                                                  if (!empty($person['nickname'])) {
                                                    echo ' <em>"' . esc_html($person['nickname']) . '"</em>';
                                                  }
                ?>
              </div>

              <div class="person-status">
                <?php if ($person['living'] == 1): ?>
                  <span class="status-living"><?php _e('Living', 'heritagepress'); ?></span>
                <?php endif; ?>
                <?php if ($person['private'] == 1): ?>
                  <span class="status-private"><?php _e('Private', 'heritagepress'); ?></span> <?php endif; ?>
              </div>
            </td>
            <td class="column-birth">
              <?php
                                                  // Fallback: display raw birthdate if HP_Date_Utils is missing
                                                  $birth_display = isset($person['birthdate']) ? HP_Date_Utils::format_date($person['birthdate']) : '';
                                                  if (!empty($birth_display)):
              ?>
                <strong><?php echo $birth_display; ?></strong>
              <?php endif; ?>

              <?php if (!empty($person['birthplace'])): ?>
                <br><small><?php echo esc_html($person['birthplace']); ?></small>
              <?php elseif (!empty($person['altbirthplace'])): ?>
                <br><small><?php echo esc_html($person['altbirthplace']); ?></small>
              <?php endif; ?>
            </td>
            <td class="column-death">
              <?php
                                                  // Fallback: display raw deathdate if HP_Date_Utils is missing
                                                  $death_display = isset($person['deathdate']) ? HP_Date_Utils::format_date($person['deathdate']) : '';
                                                  if (!empty($death_display)):
              ?>
                <strong><?php echo $death_display; ?></strong>
              <?php endif; ?>

              <?php if (!empty($person['deathplace'])): ?>
                <br><small><?php echo esc_html($person['deathplace']); ?></small>
              <?php elseif (!empty($person['burialplace'])): ?>
                <br><small><?php echo esc_html($person['burialplace']); ?></small> <?php endif; ?>
            </td>
            <td class="column-spouse">
              <?php
                                                  // Get relationship information for this person
                                                  $relationships = get_person_relationships($person['personID'], $person['gedcom'], $wpdb, $families_table, $people_table);

                                                  if (!empty($relationships['spouses'])) {
                                                    $spouse_links = array();
                                                    foreach ($relationships['spouses'] as $spouse) {
                                                      $spouse_link = '<a href="' . admin_url('admin.php?page=heritagepress-people&tab=edit&personID=' . urlencode($spouse['id']) . '&tree=' . urlencode($person['gedcom'])) . '" title="' . esc_attr($spouse['name']) . '">' . esc_html($spouse['name']) . '</a>';
                                                      $spouse_links[] = $spouse_link;
                                                    }
                                                    echo implode('<br>', $spouse_links);
                                                  } else {
                                                    echo '<span class="no-data">—</span>';
                                                  }
              ?>
            </td>
            <td class="column-partner">
              <?php
                                                  if (!empty($relationships['partners'])) {
                                                    $partner_links = array();
                                                    foreach ($relationships['partners'] as $partner) {
                                                      $partner_link = '<a href="' . admin_url('admin.php?page=heritagepress-people&tab=edit&personID=' . urlencode($partner['id']) . '&tree=' . urlencode($person['gedcom'])) . '" title="' . esc_attr($partner['name'] . ' (' . $partner['relationship_type'] . ')') . '">' . esc_html($partner['name']) . '</a>';
                                                      if ($partner['relationship_type'] !== 'partner') {
                                                        $partner_link .= ' <small>(' . esc_html($partner['relationship_type']) . ')</small>';
                                                      }
                                                      $partner_links[] = $partner_link;
                                                    }
                                                    echo implode('<br>', $partner_links);
                                                  } else {
                                                    echo '<span class="no-data">—</span>';
                                                  }
              ?>
            </td>
            <td class="column-changed">
              <?php echo esc_html($person['changedate_formatted']); ?>
              <?php if (!empty($person['changedby'])): ?>
                <br><small><?php echo esc_html($person['changedby']); ?></small>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?> <?php else: ?> <tr class="no-items">
          <td class="colspanchange" colspan="<?php echo count($trees_result) > 1 ? '10' : '9'; ?>">
            <?php _e('No people found.', 'heritagepress'); ?>
          </td>
        </tr>
      <?php endif; ?>
        </tbody>

        <tfoot>
          <tr>
            <td class="manage-column column-cb check-column">
              <label class="screen-reader-text" for="cb-select-all-2"><?php _e('Select All', 'heritagepress'); ?></label>
              <input id="cb-select-all-2" type="checkbox" />
            </td>
            <th scope="col" class="manage-column column-photo"><?php _e('Photo', 'heritagepress'); ?></th>
            <?php if (count($trees_result) > 1): ?>
              <th scope="col" class="manage-column column-tree"><?php _e('Tree', 'heritagepress'); ?></th>
            <?php endif; ?> <th scope="col" class="manage-column column-personid"><?php _e('Person ID', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column column-name"><?php _e('Name', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column column-birth"><?php _e('Birth', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column column-death"><?php _e('Death', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column column-spouse"><?php _e('Spouse', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column column-partner"><?php _e('Partner', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column column-changed"><?php _e('Last Changed', 'heritagepress'); ?></th>
          </tr>
        </tfoot>
      </table>

      <!-- Bottom Navigation -->
      <div class="tablenav bottom">
        <div class="alignleft actions bulkactions">
          <label for="bulk-action-selector-bottom" class="screen-reader-text"><?php _e('Select bulk action', 'heritagepress'); ?></label>
          <select name="action2" id="bulk-action-selector-bottom">
            <option value="-1"><?php _e('Bulk Actions', 'heritagepress'); ?></option>
            <option value="delete"><?php _e('Delete', 'heritagepress'); ?></option>
            <option value="make_private"><?php _e('Mark as Private', 'heritagepress'); ?></option>
            <option value="make_public"><?php _e('Mark as Public', 'heritagepress'); ?></option>
          </select>
          <input type="submit" id="doaction2" class="button action" value="<?php _e('Apply', 'heritagepress'); ?>">
        </div>

        <div class="tablenav-pages">
          <?php if ($total_pages > 1): ?>
            <?php echo $page_links; ?>
          <?php endif; ?>
        </div>
      </div>
    </form>
  </div>
</div>

<script type="text/javascript">
  jQuery(document).ready(function($) {
    console.log('Browse people page ready');

    // Initialize HeritagePress People functionality
    if (typeof HeritagePressPeople !== 'undefined') {
      console.log('Initializing HeritagePressPeople');
      HeritagePressPeople.init();
    } else {
      console.log('HeritagePressPeople not found');
    }

    // Bulk action validation (keep this as it's specific to this page)
    $('#bulk-action-form').on('submit', function(e) {
      var action = $('select[name="action"]').val();
      if (action === '-1') {
        action = $('select[name="action2"]').val();
      }

      if (action === '-1') {
        alert('<?php _e('Please select an action.', 'heritagepress'); ?>');
        return false;
      }

      var selected = $('input[name="selected_people[]"]:checked').length;
      if (selected === 0) {
        alert('<?php _e('Please select at least one person.', 'heritagepress'); ?>');
        return false;
      }

      if (action === 'delete') {
        return confirm('<?php _e('Are you sure you want to delete the selected people? This action cannot be undone.', 'heritagepress'); ?>');
      }

      return true;
    });
  });

  // Global delete function called from row actions
  function deletePerson(personID, gedcom) {
    if (!personID || !gedcom) {
      alert('Invalid person ID or tree');
      return;
    }

    // Show loading state
    const deleteLinks = document.querySelectorAll(`a[onclick*="${personID}"]`);
    deleteLinks.forEach(link => {
      link.style.opacity = '0.5';
      link.style.pointerEvents = 'none';
    }); // Create and submit delete form
    var form = jQuery('<form method="post">')
      .append(jQuery('<input type="hidden" name="action" value="delete_person">'))
      .append(jQuery('<input type="hidden" name="personID" value="' + personID + '">'))
      .append(jQuery('<input type="hidden" name="gedcom" value="' + gedcom + '">'))
      .append('<?php echo wp_nonce_field('heritagepress_delete_person', '_wpnonce', true, false); ?>');
    jQuery('body').append(form);
    form.submit();
  }
  });
</script>
