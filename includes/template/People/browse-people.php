<?php

/**
 * Browse People Tab - Main People Listing Interface
 * Complete facsimile of TNG admin_people.php functionality
 */

if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;

// Table references
$people_table = $wpdb->prefix . 'hp_people';
$trees_table = $wpdb->prefix . 'hp_trees';
$children_table = $wpdb->prefix . 'hp_children';
$families_table = $wpdb->prefix . 'hp_families';

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
  'page' => isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1
);

// Pagination settings
$per_page = 25;
$offset = ($search_params['page'] - 1) * $per_page;

// Build WHERE clause
$where_conditions = array("$people_table.gedcom = $trees_table.gedcom");

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

// Count total records
$count_query = "SELECT COUNT(*) as total FROM (
  SELECT $people_table.personID $select_extra
  FROM $people_table
  INNER JOIN $trees_table ON $people_table.gedcom = $trees_table.gedcom
  $join_clause
  $where_clause
  $group_by
  $having
) as count_table";

$total_count = $wpdb->get_var($count_query);
$total_pages = ceil($total_count / $per_page);

// Main query with pagination
$main_query = "SELECT $people_table.*, $trees_table.treename,
  DATE_FORMAT($people_table.changedate, '%d %b %Y %H:%i:%s') as changedate_formatted
  $select_extra
  FROM $people_table
  INNER JOIN $trees_table ON $people_table.gedcom = $trees_table.gedcom
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
    $icon = ' <span class="dashicons dashicons-arrow-down-alt2"></span>';
  } else {
    $params['order'] = $field;
    $icon = ' <span class="dashicons dashicons-arrow-up-alt2"></span>';
  }

  return admin_url('admin.php?' . http_build_query($params)) . $icon;
}

$id_sort_link = get_sort_link('id', $search_params['order']);
$name_sort_link = get_sort_link('name', $search_params['order']);
$birth_sort_link = get_sort_link('birth', $search_params['order']);
$death_sort_link = get_sort_link('death', $search_params['order']);
$change_sort_link = get_sort_link('change', $search_params['order']);
?>

<div class="people-browse-section">
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
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="search-buttons">
          <input type="submit" class="button button-primary" value="<?php _e('Search', 'heritagepress'); ?>" />
          <a href="<?php echo admin_url('admin.php?page=heritagepress-people&tab=browse'); ?>" class="button"><?php _e('Clear', 'heritagepress'); ?></a>
        </div>
      </div>

      <!-- Filter Options -->
      <div class="filter-options">
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
            <th scope="col" class="manage-column column-personid sortable">
              <a href="<?php echo esc_url($id_sort_link); ?>">
                <span><?php _e('Person ID', 'heritagepress'); ?></span>
                <span class="sorting-indicator"></span>
              </a>
            </th>
            <th scope="col" class="manage-column column-photo"><?php _e('Photo', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column column-name sortable">
              <a href="<?php echo esc_url($name_sort_link); ?>">
                <span><?php _e('Name', 'heritagepress'); ?></span>
                <span class="sorting-indicator"></span>
              </a>
            </th>
            <th scope="col" class="manage-column column-birth sortable">
              <a href="<?php echo esc_url($birth_sort_link); ?>">
                <span><?php _e('Birth', 'heritagepress'); ?></span>
                <span class="sorting-indicator"></span>
              </a>
            </th>
            <th scope="col" class="manage-column column-death sortable">
              <a href="<?php echo esc_url($death_sort_link); ?>">
                <span><?php _e('Death', 'heritagepress'); ?></span>
                <span class="sorting-indicator"></span>
              </a>
            </th>
            <?php if (count($trees_result) > 1): ?>
              <th scope="col" class="manage-column column-tree"><?php _e('Tree', 'heritagepress'); ?></th>
            <?php endif; ?>
            <th scope="col" class="manage-column column-changed sortable">
              <a href="<?php echo esc_url($change_sort_link); ?>">
                <span><?php _e('Last Changed', 'heritagepress'); ?></span>
                <span class="sorting-indicator"></span>
              </a>
            </th>
            <th scope="col" class="manage-column column-actions"><?php _e('Actions', 'heritagepress'); ?></th>
          </tr>
        </thead>

        <tbody id="the-list">
          <?php if (!empty($people_results)): ?>
            <?php foreach ($people_results as $person): ?>
              <tr id="person-<?php echo esc_attr($person['ID']); ?>" class="<?php echo (!empty($person['deathdate']) || $person['living'] == 0) ? 'person-deceased' : 'person-living'; ?>">
                <th scope="row" class="check-column">
                  <input type="checkbox" name="selected_people[]" value="<?php echo esc_attr($person['ID']); ?>" />
                </th>
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
                  </div>
                </td>
                <td class="column-photo">
                  <!-- Photo placeholder - will be enhanced with actual photo display -->
                  <div class="person-photo-placeholder">
                    <span class="dashicons dashicons-admin-users"></span>
                  </div>
                </td>
                <td class="column-name">
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

                  <div class="person-status">
                    <?php if ($person['living'] == 1): ?>
                      <span class="status-living"><?php _e('Living', 'heritagepress'); ?></span>
                    <?php endif; ?>
                    <?php if ($person['private'] == 1): ?>
                      <span class="status-private"><?php _e('Private', 'heritagepress'); ?></span>
                    <?php endif; ?>
                  </div>
                </td>
                <td class="column-birth">
                  <?php if (!empty($person['birthdate'])): ?>
                    <strong><?php echo esc_html($person['birthdate']); ?></strong>
                  <?php elseif (!empty($person['altbirthdate'])): ?>
                    <strong><?php echo esc_html($person['altbirthdate']); ?></strong> <em>(chr.)</em>
                  <?php endif; ?>

                  <?php if (!empty($person['birthplace'])): ?>
                    <br><small><?php echo esc_html($person['birthplace']); ?></small>
                  <?php elseif (!empty($person['altbirthplace'])): ?>
                    <br><small><?php echo esc_html($person['altbirthplace']); ?></small>
                  <?php endif; ?>
                </td>
                <td class="column-death">
                  <?php if (!empty($person['deathdate'])): ?>
                    <strong><?php echo esc_html($person['deathdate']); ?></strong>
                  <?php elseif (!empty($person['burialdate'])): ?>
                    <strong><?php echo esc_html($person['burialdate']); ?></strong> <em>(bur.)</em>
                  <?php endif; ?>

                  <?php if (!empty($person['deathplace'])): ?>
                    <br><small><?php echo esc_html($person['deathplace']); ?></small>
                  <?php elseif (!empty($person['burialplace'])): ?>
                    <br><small><?php echo esc_html($person['burialplace']); ?></small>
                  <?php endif; ?>
                </td>
                <?php if (count($trees_result) > 1): ?>
                  <td class="column-tree">
                    <?php echo esc_html($person['treename']); ?>
                  </td>
                <?php endif; ?>
                <td class="column-changed">
                  <?php echo esc_html($person['changedate_formatted']); ?>
                  <?php if (!empty($person['changedby'])): ?>
                    <br><small><?php echo esc_html($person['changedby']); ?></small>
                  <?php endif; ?>
                </td>
                <td class="column-actions">
                  <div class="action-buttons">
                    <a href="<?php echo admin_url('admin.php?page=heritagepress-people&tab=edit&personID=' . urlencode($person['personID']) . '&tree=' . urlencode($person['gedcom'])); ?>" class="button button-small" title="<?php _e('Edit Person', 'heritagepress'); ?>">
                      <span class="dashicons dashicons-edit"></span>
                    </a>
                    <button type="button" class="button button-small delete-person" data-person-id="<?php echo esc_attr($person['personID']); ?>" data-tree="<?php echo esc_attr($person['gedcom']); ?>" title="<?php _e('Delete Person', 'heritagepress'); ?>">
                      <span class="dashicons dashicons-trash"></span>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr class="no-items">
              <td class="colspanchange" colspan="<?php echo count($trees_result) > 1 ? '9' : '8'; ?>">
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
            <th scope="col" class="manage-column column-personid"><?php _e('Person ID', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column column-photo"><?php _e('Photo', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column column-name"><?php _e('Name', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column column-birth"><?php _e('Birth', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column column-death"><?php _e('Death', 'heritagepress'); ?></th>
            <?php if (count($trees_result) > 1): ?>
              <th scope="col" class="manage-column column-tree"><?php _e('Tree', 'heritagepress'); ?></th>
            <?php endif; ?>
            <th scope="col" class="manage-column column-changed"><?php _e('Last Changed', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column column-actions"><?php _e('Actions', 'heritagepress'); ?></th>
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
    // Toggle advanced search options
    $('#toggle-advanced').on('click', function(e) {
      e.preventDefault();
      $('#advanced-options').slideToggle();
      $(this).find('.dashicons').toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
    });

    // Select all checkboxes
    $('#cb-select-all-1, #cb-select-all-2').on('change', function() {
      var checked = $(this).is(':checked');
      $('input[name="selected_people[]"]').prop('checked', checked);
      $('#cb-select-all-1, #cb-select-all-2').prop('checked', checked);
    });

    // Individual checkbox handling
    $('input[name="selected_people[]"]').on('change', function() {
      var total = $('input[name="selected_people[]"]').length;
      var checked = $('input[name="selected_people[]"]:checked').length;
      $('#cb-select-all-1, #cb-select-all-2').prop('checked', total === checked);
    });

    // Bulk action validation
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

    // Individual delete button
    $('.delete-person').on('click', function(e) {
      e.preventDefault();
      if (confirm('<?php _e('Are you sure you want to delete this person? This action cannot be undone.', 'heritagepress'); ?>')) {
        var personId = $(this).data('person-id');
        var tree = $(this).data('tree');

        // Create and submit delete form
        var form = $('<form method="post">')
          .append($('<input type="hidden" name="action" value="delete_person">'))
          .append($('<input type="hidden" name="personID" value="' + personId + '">'))
          .append($('<input type="hidden" name="gedcom" value="' + tree + '">'))
          .append('<?php echo wp_nonce_field('heritagepress_delete_person', '_wpnonce', true, false); ?>');

        $('body').append(form);
        form.submit();
      }
    });
  });
</script>
