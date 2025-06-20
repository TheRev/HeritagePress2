<?php
// HeritagePress: Manage Sources admin page (WordPress-native, )
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', function () {
  add_menu_page(
    __('Manage Sources', 'heritagepress'),
    __('Manage Sources', 'heritagepress'),
    'manage_options',
    'heritagepress-sources',
    'heritagepress_admin_sources_page',
    'dashicons-book-alt',
    57
  );
});

function heritagepress_admin_sources_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  global $wpdb;
  $sources_table = $wpdb->prefix . 'HeritagePress_sources';
  $trees_table = $wpdb->prefix . 'HeritagePress_trees';
  $users_table = $wpdb->prefix . 'HeritagePress_users';
  $message = isset($_GET['message']) ? sanitize_text_field(wp_unslash($_GET['message'])) : '';
  $searchstring = isset($_GET['searchstring']) ? sanitize_text_field(wp_unslash($_GET['searchstring'])) : '';
  $tree = isset($_GET['tree']) ? sanitize_text_field(wp_unslash($_GET['tree'])) : '';
  $exactmatch = isset($_GET['exactmatch']) && $_GET['exactmatch'] === 'yes' ? 'yes' : '';
  $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'title';
  $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
  $per_page = 20;
  $offset = ($paged - 1) * $per_page;

  // Handle bulk delete
  if (!empty($_POST['action']) && $_POST['action'] === 'bulk-delete' && !empty($_POST['source_ids'])) {
    check_admin_referer('heritagepress_sources_bulk_action');
    $ids = array_map('intval', (array) $_POST['source_ids']);
    foreach ($ids as $id) {
      $wpdb->delete($sources_table, ['ID' => $id]);
    }
    $message = sprintf(__('Deleted %d sources.', 'heritagepress'), count($ids));
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
  }

  // Build WHERE clause
  $where = [];
  $params = [];
  if ($tree) {
    $where[] = "$sources_table.gedcom = %s";
    $params[] = $tree;
  }
  if ($searchstring) {
    $like = $exactmatch === 'yes' ? $searchstring : '%' . $wpdb->esc_like($searchstring) . '%';
    $op = $exactmatch === 'yes' ? '=' : 'LIKE';
    $where[] = "($sources_table.sourceID $op %s OR $sources_table.shorttitle $op %s OR $sources_table.title $op %s OR $sources_table.author $op %s OR $sources_table.callnum $op %s OR $sources_table.publisher $op %s OR $sources_table.actualtext $op %s)";
    for ($i = 0; $i < 7; $i++) $params[] = $like;
  }
  $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

  // Sorting
  $order_map = [
    'id' => '$sources_table.sourceID ASC',
    'idup' => '$sources_table.sourceID DESC',
    'title' => '$sources_table.shorttitle ASC, $sources_table.title ASC',
    'titleup' => '$sources_table.shorttitle DESC, $sources_table.title DESC',
    'change' => '$sources_table.changedate ASC',
    'changeup' => '$sources_table.changedate DESC',
  ];
  $orderstr = isset($order_map[$order]) ? $order_map[$order] : '$sources_table.shorttitle ASC, $sources_table.title ASC';

  // Query sources
  $sql = "SELECT $sources_table.ID, $sources_table.sourceID, $sources_table.shorttitle, $sources_table.title, $sources_table.gedcom, $sources_table.changedby, DATE_FORMAT($sources_table.changedate,'%d %b %Y') as changedatef, $trees_table.treename FROM $sources_table LEFT JOIN $trees_table ON $sources_table.gedcom = $trees_table.gedcom $where_sql ORDER BY $orderstr LIMIT %d OFFSET %d";
  $query_params = array_merge($params, [$per_page, $offset]);
  $sources = $wpdb->get_results($wpdb->prepare($sql, ...$query_params));
  $total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $sources_table LEFT JOIN $trees_table ON $sources_table.gedcom = $trees_table.gedcom $where_sql", ...$params));

  // Get number of trees and users
  $numtrees = $wpdb->get_var("SELECT COUNT(*) FROM $trees_table");
  $numusers = $wpdb->get_var("SELECT COUNT(*) FROM $users_table WHERE allow_living != '-1'");

  // Admin page UI
  echo '<div class="wrap">';
  echo '<h1>' . esc_html__('Manage Sources', 'heritagepress') . '</h1>';
  if ($message) {
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
  }
  // Search form
  echo '<form method="get" action="">';
  echo '<input type="hidden" name="page" value="heritagepress-sources">';
  echo '<input type="text" name="searchstring" value="' . esc_attr($searchstring) . '" placeholder="' . esc_attr__('Search sources...', 'heritagepress') . '" class="regular-text"> ';
  echo '<select name="tree">';
  echo '<option value="">' . esc_html__('All Trees', 'heritagepress') . '</option>';
  $trees = $wpdb->get_results("SELECT gedcom, treename FROM $trees_table ORDER BY treename");
  foreach ($trees as $t) {
    echo '<option value="' . esc_attr($t->gedcom) . '"' . selected($tree, $t->gedcom, false) . '>' . esc_html($t->treename) . '</option>';
  }
  echo '</select> ';
  echo '<label><input type="checkbox" name="exactmatch" value="yes"' . checked($exactmatch, 'yes', false) . '> ' . esc_html__('Exact match', 'heritagepress') . '</label> ';
  echo '<input type="submit" class="button" value="' . esc_attr__('Search', 'heritagepress') . '"> ';
  echo '<a href="' . esc_url(admin_url('admin.php?page=heritagepress-sources')) . '" class="button">' . esc_html__('Reset', 'heritagepress') . '</a>';
  echo '</form><br />';

  // Bulk delete form
  echo '<form method="post">';
  wp_nonce_field('heritagepress_sources_bulk_action');
  echo '<input type="hidden" name="action" value="bulk-delete">';
  echo '<table class="wp-list-table widefat fixed striped">';
  echo '<thead><tr>';
  echo '<th class="manage-column column-cb check-column"><input type="checkbox" id="cb-select-all"></th>';
  echo '<th>' . esc_html__('ID', 'heritagepress') . '</th>';
  echo '<th>' . esc_html__('Short Title', 'heritagepress') . '</th>';
  echo '<th>' . esc_html__('Title', 'heritagepress') . '</th>';
  if ($numtrees > 1) echo '<th>' . esc_html__('Tree', 'heritagepress') . '</th>';
  echo '<th>' . esc_html__('Last Modified', 'heritagepress') . '</th>';
  echo '<th>' . esc_html__('Actions', 'heritagepress') . '</th>';
  echo '</tr></thead><tbody>';
  if ($sources) {
    foreach ($sources as $source) {
      echo '<tr>';
      echo '<th scope="row" class="check-column"><input type="checkbox" name="source_ids[]" value="' . esc_attr($source->ID) . '"></th>';
      echo '<td>' . esc_html($source->sourceID) . '</td>';
      echo '<td>' . esc_html($source->shorttitle) . '</td>';
      echo '<td>' . esc_html($source->title) . '</td>';
      if ($numtrees > 1) echo '<td>' . esc_html($source->treename) . '</td>';
      $changedby = $numusers > 1 ? esc_html($source->changedby) . ': ' : '';
      echo '<td>' . $changedby . esc_html($source->changedatef) . '</td>';
      echo '<td>';
      echo '<a href="' . esc_url(admin_url('admin.php?page=heritagepress-editsource&sourceID=' . urlencode($source->sourceID) . '&tree=' . urlencode($source->gedcom))) . '" class="button button-small">' . esc_html__('Edit', 'heritagepress') . '</a> ';
      echo '<a href="#" class="button button-small delete-source" data-id="' . esc_attr($source->ID) . '">' . esc_html__('Delete', 'heritagepress') . '</a> ';
      echo '<a href="' . esc_url(site_url('?showsource=1&sourceID=' . urlencode($source->sourceID) . '&tree=' . urlencode($source->gedcom))) . '" target="_blank" class="button button-small">' . esc_html__('Test', 'heritagepress') . '</a>';
      echo '</td>';
      echo '</tr>';
    }
  } else {
    echo '<tr><td colspan="7">' . esc_html__('No sources found.', 'heritagepress') . '</td></tr>';
  }
  echo '</tbody></table>';
  echo '<p><input type="submit" class="button action" value="' . esc_attr__('Delete Selected', 'heritagepress') . '" onclick="return confirm(\'' . esc_js(__('Are you sure you want to delete the selected sources?', 'heritagepress')) . '\');"></p>';
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
  echo '<script>document.getElementById("cb-select-all").addEventListener("click",function(e){var cbs=document.querySelectorAll(\"input[name=\\\"source_ids[]\\\"]\");for(var i=0;i<cbs.length;i++){cbs[i].checked=this.checked;}});jQuery(document).on("click",".delete-source",function(e){e.preventDefault();if(confirm("' . esc_js(__('Are you sure you want to delete this source?', 'heritagepress')) . '")){var id=jQuery(this).data("id");jQuery(this).closest("tr").find("input[type=checkbox]").prop("checked",true);jQuery(this).closest("form").submit();}});</script>';
}
