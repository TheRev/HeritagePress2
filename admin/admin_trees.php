<?php
// HeritagePress: Manage Trees admin page (WordPress-native, ported from TNG admin_trees.php)
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', function () {
  add_menu_page(
    __('Manage Trees', 'heritagepress'),
    __('Manage Trees', 'heritagepress'),
    'manage_options',
    'heritagepress-trees',
    'heritagepress_admin_trees_page',
    'dashicons-networking',
    56
  );
});

function heritagepress_admin_trees_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  global $wpdb;
  $trees_table = $wpdb->prefix . 'tng_trees';
  $people_table = $wpdb->prefix . 'tng_people';
  $message = isset($_GET['message']) ? sanitize_text_field(wp_unslash($_GET['message'])) : '';
  $searchstring = isset($_GET['searchstring']) ? sanitize_text_field(wp_unslash($_GET['searchstring'])) : '';
  $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
  $per_page = 20;
  $offset = ($paged - 1) * $per_page;

  // Handle bulk delete
  if (!empty($_POST['action']) && $_POST['action'] === 'bulk-delete' && !empty($_POST['tree_ids'])) {
    check_admin_referer('heritagepress_trees_bulk_action');
    $ids = array_map('sanitize_text_field', (array) $_POST['tree_ids']);
    foreach ($ids as $tree_id) {
      $wpdb->delete($trees_table, ['gedcom' => $tree_id]);
    }
    $message = sprintf(__('Deleted %d trees.', 'heritagepress'), count($ids));
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
  }

  // Search and query
  $where = '';
  $params = [];
  if ($searchstring) {
    $like = '%' . $wpdb->esc_like($searchstring) . '%';
    $where = "WHERE gedcom LIKE %s OR treename LIKE %s OR description LIKE %s OR owner LIKE %s";
    $params = [$like, $like, $like, $like];
  }
  $sql = "SELECT SQL_CALC_FOUND_ROWS gedcom, treename, description, owner, DATE_FORMAT(lastimportdate,'%d %b %Y %H:%i:%s') as lastimportdate, importfilename FROM $trees_table $where ORDER BY treename LIMIT %d OFFSET %d";
  $query_params = array_merge($params, [$per_page, $offset]);
  $trees = $wpdb->get_results($wpdb->prepare($sql, ...$query_params));
  $total = $wpdb->get_var('SELECT FOUND_ROWS()');

  // Admin page UI
  echo '<div class="wrap">';
  echo '<h1>' . esc_html__('Manage Trees', 'heritagepress') . '</h1>';
  if ($message) {
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
  }
  echo '<form method="get" action="">';
  echo '<input type="hidden" name="page" value="heritagepress-trees">';
  echo '<input type="text" name="searchstring" value="' . esc_attr($searchstring) . '" placeholder="' . esc_attr__('Search trees...', 'heritagepress') . '"> ';
  echo '<input type="submit" class="button" value="' . esc_attr__('Search', 'heritagepress') . '"> ';
  echo '<a href="' . esc_url(admin_url('admin.php?page=heritagepress-trees')) . '" class="button">' . esc_html__('Reset', 'heritagepress') . '</a>';
  echo '</form><br />';

  echo '<form method="post">';
  wp_nonce_field('heritagepress_trees_bulk_action');
  echo '<input type="hidden" name="action" value="bulk-delete">';
  echo '<table class="wp-list-table widefat fixed striped">';
  echo '<thead><tr>';
  echo '<th class="manage-column column-cb check-column"><input type="checkbox" id="cb-select-all"></th>';
  echo '<th>' . esc_html__('ID', 'heritagepress') . '</th>';
  echo '<th>' . esc_html__('Tree Name', 'heritagepress') . '</th>';
  echo '<th>' . esc_html__('Description', 'heritagepress') . '</th>';
  echo '<th>' . esc_html__('People', 'heritagepress') . '</th>';
  echo '<th>' . esc_html__('Owner', 'heritagepress') . '</th>';
  echo '<th>' . esc_html__('Last Import', 'heritagepress') . '</th>';
  echo '<th>' . esc_html__('Import Filename', 'heritagepress') . '</th>';
  echo '<th>' . esc_html__('Actions', 'heritagepress') . '</th>';
  echo '</tr></thead><tbody>';
  if ($trees) {
    foreach ($trees as $tree) {
      $people_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(personID) FROM $people_table WHERE gedcom = %s", $tree->gedcom));
      echo '<tr>';
      echo '<th scope="row" class="check-column"><input type="checkbox" name="tree_ids[]" value="' . esc_attr($tree->gedcom) . '"></th>';
      echo '<td>' . esc_html($tree->gedcom) . '</td>';
      echo '<td>' . esc_html($tree->treename) . '</td>';
      echo '<td>' . esc_html($tree->description) . '</td>';
      echo '<td style="text-align:right">' . number_format($people_count) . '</td>';
      echo '<td>' . esc_html($tree->owner) . '</td>';
      echo '<td>' . esc_html($tree->lastimportdate) . '</td>';
      echo '<td>' . esc_html($tree->importfilename) . '</td>';
      echo '<td>';
      echo '<a href="' . esc_url(admin_url('admin.php?page=heritagepress-edittree&tree=' . urlencode($tree->gedcom))) . '" class="button button-small">' . esc_html__('Edit', 'heritagepress') . '</a> ';
      echo '<a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=heritagepress-trees&action=delete&tree=' . urlencode($tree->gedcom)), 'heritagepress_delete_tree_' . $tree->gedcom)) . '" class="button button-small" onclick="return confirm(\'' . esc_js(__('Are you sure you want to delete this tree?', 'heritagepress')) . '\');">' . esc_html__('Delete', 'heritagepress') . '</a> ';
      echo '<a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=heritagepress-trees&action=clear&tree=' . urlencode($tree->gedcom)), 'heritagepress_clear_tree_' . $tree->gedcom)) . '" class="button button-small" onclick="return confirm(\'' . esc_js(__('Are you sure you want to clear this tree?', 'heritagepress')) . '\');">' . esc_html__('Clear', 'heritagepress') . '</a>';
      echo '</td>';
      echo '</tr>';
    }
  } else {
    echo '<tr><td colspan="9">' . esc_html__('No trees found.', 'heritagepress') . '</td></tr>';
  }
  echo '</tbody></table>';
  echo '<p><input type="submit" class="button action" value="' . esc_attr__('Delete Selected', 'heritagepress') . '" onclick="return confirm(\'' . esc_js(__('Are you sure you want to delete the selected trees?', 'heritagepress')) . '\');"></p>';
  echo '</form>';

  // Pagination
  $page_links = paginate_links([
    'base' => add_query_arg('paged', '%#%'),
    'format' => '',
    'prev_text' => __('&laquo; Previous'),
    'next_text' => __('Next &raquo;'),
    'total' => ceil($total / $per_page),
    'current' => $paged
  ]);
  if ($page_links) {
    echo '<div class="tablenav"><div class="tablenav-pages">' . $page_links . '</div></div>';
  }
  echo '</div>';

  // JS for select all
  echo '<script>document.getElementById("cb-select-all").addEventListener("click",function(e){var cbs=document.querySelectorAll(\"input[name=\\\"tree_ids[]\\\"]\");for(var i=0;i<cbs.length;i++){cbs[i].checked=this.checked;}});</script>';
}

// Handle single delete/clear actions
add_action('admin_init', function () {
  if (!isset($_GET['page']) || $_GET['page'] !== 'heritagepress-trees') return;
  if (!current_user_can('manage_options')) return;
  global $wpdb;
  $trees_table = $wpdb->prefix . 'tng_trees';
  if (isset($_GET['action'], $_GET['tree'])) {
    $tree = sanitize_text_field(wp_unslash($_GET['tree']));
    if ($_GET['action'] === 'delete' && check_admin_referer('heritagepress_delete_tree_' . $tree)) {
      $wpdb->delete($trees_table, ['gedcom' => $tree]);
      wp_redirect(admin_url('admin.php?page=heritagepress-trees&message=' . urlencode(__('Tree deleted.', 'heritagepress'))));
      exit;
    }
    if ($_GET['action'] === 'clear' && check_admin_referer('heritagepress_clear_tree_' . $tree)) {
      // Implement tree clearing logic here (e.g., remove all people/events for this tree)
      wp_redirect(admin_url('admin.php?page=heritagepress-trees&message=' . urlencode(__('Tree cleared (not implemented).', 'heritagepress'))));
      exit;
    }
  }
});
