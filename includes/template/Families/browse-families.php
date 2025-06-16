<?php

/**
 * Browse Families - Complete TNG admin_families.php facsimile
 * Comprehensive family listing with search, filter, and management capabilities
 */

if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;

// Initialize variables
$search_string = isset($_GET['searchstring']) ? sanitize_text_field($_GET['searchstring']) : '';
$spouse_name = isset($_GET['spousename']) ? sanitize_text_field($_GET['spousename']) : 'husband';
$tree_filter = isset($_GET['tree']) ? sanitize_text_field($_GET['tree']) : '';
$living_only = isset($_GET['living']) ? $_GET['living'] === 'yes' : false;
$exact_match = isset($_GET['exactmatch']) ? $_GET['exactmatch'] === 'yes' : false;
$order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'fname';
$offset = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;
$per_page = 50; // Max search results per page

// Get tables
$families_table = $wpdb->prefix . 'hp_families';
$people_table = $wpdb->prefix . 'hp_people';
$trees_table = $wpdb->prefix . 'hp_trees';

// Build search query
$where_conditions = array();
$join_conditions = array();
$select_fields = array();
$orderby_clause = '';

// Base query setup
$base_query = "SELECT f.ID, f.familyID, f.husband, f.wife, f.marrdate, f.marrplace, 
               f.divdate, f.divplace, f.private, f.living, f.branch, f.gedcom, 
               f.changedby, DATE_FORMAT(f.changedate, '%d %b %Y') as changedatef, 
               f.changedate, t.treename";

// Add person fields based on spouse selection
if ($spouse_name === 'husband' || $spouse_name === 'wife') {
  $base_query .= ", p1.firstname, p1.lnprefix, p1.lastname, p1.prefix, p1.suffix, p1.title, p1.nameorder";
  if ($spouse_name === 'husband') {
    $base_query .= ", p2.firstname as p2firstname, p2.lnprefix as p2lnprefix, p2.lastname as p2lastname, 
                     p2.prefix as p2prefix, p2.suffix as p2suffix, p2.title as p2title, p2.nameorder as p2nameorder";
  } else {
    $base_query .= ", p2.firstname as p2firstname, p2.lnprefix as p2lnprefix, p2.lastname as p2lastname, 
                     p2.prefix as p2prefix, p2.suffix as p2suffix, p2.title as p2title, p2.nameorder as p2nameorder";
  }
}

$from_clause = "FROM $families_table f 
                INNER JOIN $trees_table t ON f.gedcom = t.gedcom";

// Add person joins based on search criteria
if ($spouse_name === 'husband') {
  $from_clause .= " LEFT JOIN $people_table p1 ON p1.personID = f.husband AND p1.gedcom = f.gedcom
                   LEFT JOIN $people_table p2 ON p2.personID = f.wife AND p2.gedcom = f.gedcom";
} elseif ($spouse_name === 'wife') {
  $from_clause .= " LEFT JOIN $people_table p1 ON p1.personID = f.wife AND p1.gedcom = f.gedcom
                   LEFT JOIN $people_table p2 ON p2.personID = f.husband AND p2.gedcom = f.gedcom";
} else {
  // No specific spouse search
  $from_clause .= " LEFT JOIN $people_table p1 ON p1.personID = f.husband AND p1.gedcom = f.gedcom
                   LEFT JOIN $people_table p2 ON p2.personID = f.wife AND p2.gedcom = f.gedcom";
}

// Search conditions
if (!empty($search_string)) {
  $search_conditions = array();
  
  if ($exact_match) {
    $search_conditions[] = $wpdb->prepare("f.familyID = %s", $search_string);
    $search_conditions[] = $wpdb->prepare("f.husband = %s", $search_string);
    $search_conditions[] = $wpdb->prepare("f.wife = %s", $search_string);
    if ($spouse_name !== 'none') {
      $search_conditions[] = $wpdb->prepare("CONCAT_WS(' ', TRIM(p1.firstname), TRIM(p1.lnprefix), TRIM(p1.lastname)) = %s", $search_string);
    }
  } else {
    $search_terms = explode(' ', $search_string);
    $like_conditions = array();
    
    foreach ($search_terms as $term) {
      if (!empty($term)) {
        $like_term = '%' . $wpdb->esc_like($term) . '%';
        $term_conditions = array();
        $term_conditions[] = $wpdb->prepare("f.familyID LIKE %s", $like_term);
        $term_conditions[] = $wpdb->prepare("f.husband LIKE %s", $like_term);
        $term_conditions[] = $wpdb->prepare("f.wife LIKE %s", $like_term);
        if ($spouse_name !== 'none') {
          $term_conditions[] = $wpdb->prepare("p1.firstname LIKE %s", $like_term);
          $term_conditions[] = $wpdb->prepare("p1.lastname LIKE %s", $like_term);
          if (!empty($term_conditions)) {
            $like_conditions[] = '(' . implode(' OR ', $term_conditions) . ')';
          }
        }
      }
    }
    
    if (!empty($like_conditions)) {
      $search_conditions[] = '(' . implode(' AND ', $like_conditions) . ')';
    }
  }
  
  if (!empty($search_conditions)) {
    $where_conditions[] = '(' . implode(' OR ', $search_conditions) . ')';
  }
}

// Tree filter
if (!empty($tree_filter)) {
  $where_conditions[] = $wpdb->prepare("f.gedcom = %s", $tree_filter);
}

// Living filter
if ($living_only) {
  $where_conditions[] = "f.living = 1";
}

// Build WHERE clause
$where_clause = '';
if (!empty($where_conditions)) {
  $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Build ORDER BY clause
switch ($order) {
  case 'id':
    $orderby_clause = "ORDER BY CAST(f.familyID AS UNSIGNED), p1.lastname, p1.firstname";
    break;
  case 'idup':
    $orderby_clause = "ORDER BY CAST(f.familyID AS UNSIGNED) DESC, p1.lastname, p1.firstname";
    break;
  case 'fname':
    $orderby_clause = "ORDER BY p1.lastname, p1.firstname";
    break;
  case 'fnameup':
    $orderby_clause = "ORDER BY p1.lastname DESC, p1.firstname DESC";
    break;
  case 'mname':
    $orderby_clause = "ORDER BY p2.lastname, p2.firstname";
    break;
  case 'mnameup':
    $orderby_clause = "ORDER BY p2.lastname DESC, p2.firstname DESC";
    break;
  case 'marr':
    $orderby_clause = "ORDER BY f.marrdate, p1.lastname, p1.firstname";
    break;
  case 'marrup':
    $orderby_clause = "ORDER BY f.marrdate DESC, p1.lastname, p1.firstname";
    break;
  case 'change':
    $orderby_clause = "ORDER BY f.changedate, p1.lastname, p1.firstname";
    break;
  case 'changeup':
    $orderby_clause = "ORDER BY f.changedate DESC, p1.lastname, p1.firstname";
    break;
  default:
    $orderby_clause = "ORDER BY p1.lastname, p1.firstname";
    break;
}

// Get total count
$count_query = "SELECT COUNT(DISTINCT f.ID) $from_clause $where_clause";
$total_families = $wpdb->get_var($count_query);

// Get families for current page
$limit_clause = $wpdb->prepare("LIMIT %d, %d", $offset, $per_page);
$families_query = "$base_query $from_clause $where_clause $orderby_clause $limit_clause";
$families = $wpdb->get_results($families_query, ARRAY_A);

// Helper function to format person name
function format_person_name($person_data) {
  if (empty($person_data['firstname']) && empty($person_data['lastname'])) {
    return '';
  }
  
  $name_parts = array();
  
  if (!empty($person_data['prefix'])) {
    $name_parts[] = $person_data['prefix'];
  }
  if (!empty($person_data['firstname'])) {
    $name_parts[] = $person_data['firstname'];
  }
  if (!empty($person_data['lnprefix'])) {
    $name_parts[] = $person_data['lnprefix'];
  }
  if (!empty($person_data['lastname'])) {
    $name_parts[] = $person_data['lastname'];
  }
  if (!empty($person_data['suffix'])) {
    $name_parts[] = $person_data['suffix'];
  }
  if (!empty($person_data['title'])) {
    $name_parts[] = $person_data['title'];
  }
  
  return implode(' ', $name_parts);
}

// Helper function to get sort links
function get_sort_link($field, $current_order, $label) {
  $base_url = admin_url('admin.php?page=heritagepress-families&tab=browse');
  $params = $_GET;
  
  if ($current_order === $field) {
    $params['order'] = $field . 'up';
    $icon = ' ↓';
  } else {
    $params['order'] = $field;
    $icon = ($current_order === $field . 'up') ? ' ↑' : ' ↕';
  }
  
  unset($params['page'], $params['tab']);
  $query_string = http_build_query($params);
  
  return "<a href=\"$base_url&$query_string\">$label$icon</a>";
}

// Pagination
$current_page = floor($offset / $per_page) + 1;
$total_pages = ceil($total_families / $per_page);
?>

<div class="families-browse-container">
  
  <!-- Search Form -->
  <div class="search-form-container">
    <form method="get" action="" class="families-search-form">
      <input type="hidden" name="page" value="heritagepress-families">
      <input type="hidden" name="tab" value="browse">
      
      <table class="form-table">
        <tr>
          <th scope="row"><?php _e('Search for:', 'heritagepress'); ?></th>
          <td>
            <input type="text" name="searchstring" value="<?php echo esc_attr($search_string); ?>" class="regular-text" placeholder="<?php _e('Family ID, spouse name, etc.', 'heritagepress'); ?>">
          </td>
          <td>
            <input type="submit" name="submit" value="<?php _e('Search', 'heritagepress'); ?>" class="button button-primary">
            <input type="button" name="reset" value="<?php _e('Reset', 'heritagepress'); ?>" class="button" 
                   onclick="window.location.href='<?php echo admin_url('admin.php?page=heritagepress-families&tab=browse'); ?>';">
          </td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Options:', 'heritagepress'); ?></th>
          <td colspan="2">
            <select name="spousename">
              <option value="husband" <?php selected($spouse_name, 'husband'); ?>><?php _e('Husband Name', 'heritagepress'); ?></option>
              <option value="wife" <?php selected($spouse_name, 'wife'); ?>><?php _e('Wife Name', 'heritagepress'); ?></option>
              <option value="none" <?php selected($spouse_name, 'none'); ?>><?php _e('No Name Filter', 'heritagepress'); ?></option>
            </select>
            
            <?php if (count($trees_result) > 1): ?>
            <select name="tree">
              <option value=""><?php _e('All Trees', 'heritagepress'); ?></option>
              <?php foreach ($trees_result as $tree_row): ?>
                <option value="<?php echo esc_attr($tree_row['gedcom']); ?>" <?php selected($tree_filter, $tree_row['gedcom']); ?>>
                  <?php echo esc_html($tree_row['treename']); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php endif; ?>
            
            <label>
              <input type="checkbox" name="living" value="yes" <?php checked($living_only); ?>>
              <?php _e('Living Only', 'heritagepress'); ?>
            </label>
            
            <label>
              <input type="checkbox" name="exactmatch" value="yes" <?php checked($exact_match); ?>>
              <?php _e('Exact Match', 'heritagepress'); ?>
            </label>
          </td>
        </tr>
      </table>
    </form>
  </div>

  <!-- Results Info -->
  <div class="results-info">
    <p>
      <?php
      if ($total_families > 0) {
        $start = $offset + 1;
        $end = min($offset + $per_page, $total_families);
        printf(__('Showing %d-%d of %d families', 'heritagepress'), $start, $end, $total_families);
      } else {
        _e('No families found', 'heritagepress');
      }
      ?>
    </p>
  </div>

  <?php if (!empty($families)): ?>
  
  <!-- Bulk Actions -->
  <div class="bulk-actions">
    <form method="post" action="" id="bulk-actions-form">
      <?php wp_nonce_field('heritagepress_bulk_families', 'bulk_families_nonce'); ?>
      <input type="hidden" name="action" value="bulk_delete_families">
      
      <div class="alignleft actions">
        <select name="bulk_action">
          <option value=""><?php _e('Bulk Actions', 'heritagepress'); ?></option>
          <option value="delete"><?php _e('Delete', 'heritagepress'); ?></option>
        </select>
        <input type="submit" name="doaction" class="button" value="<?php _e('Apply', 'heritagepress'); ?>" 
               onclick="return confirm('<?php _e('Are you sure you want to delete the selected families?', 'heritagepress'); ?>');">
      </div>
      
      <div class="alignleft actions">
        <input type="button" name="select_all" value="<?php _e('Select All', 'heritagepress'); ?>" class="button" onclick="toggleAllCheckboxes(true);">
        <input type="button" name="clear_all" value="<?php _e('Clear All', 'heritagepress'); ?>" class="button" onclick="toggleAllCheckboxes(false);">
      </div>
    </form>
  </div>

  <!-- Families Table -->
  <table class="wp-list-table widefat fixed striped families">
    <thead>
      <tr>
        <td class="manage-column column-cb check-column">
          <input type="checkbox" id="cb-select-all">
        </td>
        <th scope="col" class="manage-column"><?php _e('Actions', 'heritagepress'); ?></th>
        <th scope="col" class="manage-column column-primary">
          <?php echo get_sort_link('id', $order, __('Family ID', 'heritagepress')); ?>
        </th>
        <th scope="col" class="manage-column"><?php _e('Husband ID', 'heritagepress'); ?></th>
        <th scope="col" class="manage-column">
          <?php echo get_sort_link('fname', $order, __('Husband Name', 'heritagepress')); ?>
        </th>
        <th scope="col" class="manage-column"><?php _e('Wife ID', 'heritagepress'); ?></th>
        <th scope="col" class="manage-column">
          <?php echo get_sort_link('mname', $order, __('Wife Name', 'heritagepress')); ?>
        </th>
        <th scope="col" class="manage-column">
          <?php echo get_sort_link('marr', $order, __('Marriage', 'heritagepress')); ?>
        </th>
        <?php if (count($trees_result) > 1): ?>
        <th scope="col" class="manage-column"><?php _e('Tree', 'heritagepress'); ?></th>
        <?php endif; ?>
        <th scope="col" class="manage-column">
          <?php echo get_sort_link('change', $order, __('Last Modified', 'heritagepress')); ?>
        </th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($families as $family): ?>
      <tr id="family-<?php echo esc_attr($family['ID']); ?>">
        <th scope="row" class="check-column">
          <input type="checkbox" name="family_ids[]" value="<?php echo esc_attr($family['ID']); ?>" form="bulk-actions-form">
        </th>
        <td class="actions">
          <div class="row-actions">
            <span class="edit">
              <a href="?page=heritagepress-families&tab=edit&familyID=<?php echo urlencode($family['familyID']); ?>&tree=<?php echo urlencode($family['gedcom']); ?>" 
                 title="<?php _e('Edit this family', 'heritagepress'); ?>">
                <?php _e('Edit', 'heritagepress'); ?>
              </a>
            </span> |
            <span class="delete">
              <a href="#" onclick="deleteFamily('<?php echo esc_js($family['ID']); ?>', '<?php echo esc_js($family['familyID']); ?>');" 
                 title="<?php _e('Delete this family', 'heritagepress'); ?>" class="delete-family">
                <?php _e('Delete', 'heritagepress'); ?>
              </a>
            </span> |
            <span class="view">
              <a href="#" onclick="viewFamily('<?php echo esc_js($family['familyID']); ?>', '<?php echo esc_js($family['gedcom']); ?>');" 
                 title="<?php _e('View family group sheet', 'heritagepress'); ?>" target="_blank">
                <?php _e('View', 'heritagepress'); ?>
              </a>
            </span>
          </div>
        </td>
        <td class="column-primary">
          <strong>
            <a href="?page=heritagepress-families&tab=edit&familyID=<?php echo urlencode($family['familyID']); ?>&tree=<?php echo urlencode($family['gedcom']); ?>">
              <?php echo esc_html($family['familyID']); ?>
            </a>
          </strong>
        </td>
        <td><?php echo esc_html($family['husband']); ?></td>
        <td>
          <?php 
          if ($spouse_name === 'husband' || $spouse_name === 'none') {
            echo esc_html(format_person_name($family));
          } else {
            // Wife was primary, so husband info is in p2 fields
            $husband_data = array(
              'firstname' => $family['p2firstname'] ?? '',
              'lastname' => $family['p2lastname'] ?? '',
              'lnprefix' => $family['p2lnprefix'] ?? '',
              'prefix' => $family['p2prefix'] ?? '',
              'suffix' => $family['p2suffix'] ?? '',
              'title' => $family['p2title'] ?? ''
            );
            echo esc_html(format_person_name($husband_data));
          }
          ?>
        </td>
        <td><?php echo esc_html($family['wife']); ?></td>
        <td>
          <?php 
          if ($spouse_name === 'wife' || $spouse_name === 'none') {
            echo esc_html(format_person_name($family));
          } else {
            // Husband was primary, so wife info is in p2 fields
            $wife_data = array(
              'firstname' => $family['p2firstname'] ?? '',
              'lastname' => $family['p2lastname'] ?? '',
              'lnprefix' => $family['p2lnprefix'] ?? '',
              'prefix' => $family['p2prefix'] ?? '',
              'suffix' => $family['p2suffix'] ?? '',
              'title' => $family['p2title'] ?? ''
            );
            echo esc_html(format_person_name($wife_data));
          }
          ?>
        </td>
        <td>
          <div><?php echo esc_html($family['marrdate']); ?></div>
          <div><?php echo esc_html($family['marrplace']); ?></div>
          <?php if (!empty($family['divdate'])): ?>
          <div><em><?php printf(__('Div: %s', 'heritagepress'), esc_html($family['divdate'])); ?></em></div>
          <?php endif; ?>
        </td>
        <?php if (count($trees_result) > 1): ?>
        <td><?php echo esc_html($family['treename']); ?></td>
        <?php endif; ?>
        <td>
          <?php if (!empty($family['changedby'])): ?>
          <div><small><?php echo esc_html($family['changedby']); ?></small></div>
          <?php endif; ?>
          <div><?php echo esc_html($family['changedatef']); ?></div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Pagination -->
  <?php if ($total_pages > 1): ?>
  <div class="tablenav">
    <div class="tablenav-pages">
      <?php
      $base_url = admin_url('admin.php?page=heritagepress-families&tab=browse');
      $params = $_GET;
      unset($params['page'], $params['tab'], $params['offset']);
      $query_string = http_build_query($params);
      $query_string = !empty($query_string) ? '&' . $query_string : '';
      
      echo paginate_links(array(
        'base' => $base_url . $query_string . '&offset=%#%',
        'format' => '',
        'current' => $current_page,
        'total' => $total_pages,
        'prev_text' => '‹ ' . __('Previous', 'heritagepress'),
        'next_text' => __('Next', 'heritagepress') . ' ›',
        'type' => 'plain',
      ));
      ?>
    </div>
  </div>
  <?php endif; ?>

  <?php else: ?>
  
  <div class="no-families">
    <p><?php _e('No families found. Try adjusting your search criteria.', 'heritagepress'); ?></p>
  </div>
  
  <?php endif; ?>

</div>

<script type="text/javascript">
function toggleAllCheckboxes(checked) {
  var checkboxes = document.querySelectorAll('input[name="family_ids[]"]');
  var selectAll = document.getElementById('cb-select-all');
  
  checkboxes.forEach(function(checkbox) {
    checkbox.checked = checked;
  });
  selectAll.checked = checked;
}

function deleteFamily(familyId, familyName) {
  if (confirm('<?php _e('Are you sure you want to delete family', 'heritagepress'); ?> ' + familyName + '?')) {
    // AJAX delete functionality would go here
    // For now, redirect to delete action
    window.location.href = '?page=heritagepress-families&action=delete&family_id=' + familyId + '&_wpnonce=<?php echo wp_create_nonce('delete_family'); ?>';
  }
}

function viewFamily(familyId, tree) {
  // Open family group sheet in new window
  // This would link to the public family view
  alert('<?php _e('Family group sheet functionality to be implemented', 'heritagepress'); ?>');
}

// Handle select all checkbox
document.addEventListener('DOMContentLoaded', function() {
  var selectAll = document.getElementById('cb-select-all');
  if (selectAll) {
    selectAll.addEventListener('change', function() {
      toggleAllCheckboxes(this.checked);
    });
  }
});
</script>

<style>
.families-browse-container {
  margin-top: 20px;
}

.search-form-container {
  background: #fff;
  border: 1px solid #c3c4c7;
  padding: 20px;
  margin-bottom: 20px;
}

.families-search-form .form-table th {
  width: 120px;
  padding: 10px 0;
}

.families-search-form .form-table td {
  padding: 10px 0;
}

.results-info {
  margin-bottom: 10px;
}

.bulk-actions {
  margin-bottom: 10px;
}

.bulk-actions .actions {
  display: inline-block;
  margin-right: 20px;
}

.families th, .families td {
  vertical-align: top;
  padding: 8px;
}

.families .column-primary {
  width: 10%;
}

.families .actions {
  width: 120px;
}

.families .row-actions {
  visibility: hidden;
}

.families tr:hover .row-actions {
  visibility: visible;
}

.no-families {
  text-align: center;
  padding: 40px;
  background: #fff;
  border: 1px solid #c3c4c7;
}
</style>
