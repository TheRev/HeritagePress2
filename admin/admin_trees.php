<?php
// HeritagePress: Manage Trees admin page (Tabbed interface)
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
  $trees_table = $wpdb->prefix . 'hp_trees';
  $people_table = $wpdb->prefix . 'hp_people';
  $message = isset($_GET['message']) ? sanitize_text_field(wp_unslash($_GET['message'])) : '';
  $searchstring = isset($_GET['searchstring']) ? sanitize_text_field(wp_unslash($_GET['searchstring'])) : '';
  $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
  $per_page = 20;
  $offset = ($paged - 1) * $per_page;

  // Tab logic
  $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'browse';

  echo '<div class="wrap">';
  echo '<h1>' . esc_html__('Manage Trees', 'heritagepress') . '</h1>';
  if ($message) {
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
  }
  // Tab navigation
  $tabs = [
    'browse' => __('Browse Trees', 'heritagepress'),
    'add' => __('Add New Tree', 'heritagepress'),
  ];
  echo '<nav class="nav-tab-wrapper">';
  foreach ($tabs as $tab => $label) {
    $class = ($active_tab === $tab) ? ' nav-tab-active' : '';
    echo '<a href="' . esc_url(add_query_arg('tab', $tab)) . '" class="nav-tab' . $class . '">' . esc_html($label) . '</a>';
  }
  echo '</nav>';

  if ($active_tab === 'add') {
    $nonce = wp_create_nonce('heritagepress_newtree');
    echo '<div style="margin-top:2em;">';
    echo '<h2>' . esc_html__('Add New Tree', 'heritagepress') . '</h2>';
    echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post">';
    echo '<input type="hidden" name="action" value="heritagepress_add_newtree">';
    echo '<input type="hidden" name="_wpnonce" value="' . esc_attr($nonce) . '">';
    echo '<table class="form-table">';
    echo '<tr><th><label for="tree_id">' . esc_html__('Tree ID', 'heritagepress') . '</label></th>';
    echo '<td><input type="text" name="tree_id" id="tree_id" value="" class="regular-text" maxlength="20" required></td></tr>';
    echo '<tr><th><label for="tree_name">' . esc_html__('Tree Name', 'heritagepress') . '</label></th>';
    echo '<td><input type="text" name="tree_name" id="tree_name" value="" class="regular-text" required></td></tr>';
    echo '<tr><th><label for="description">' . esc_html__('Description', 'heritagepress') . '</label></th>';
    echo '<td><textarea name="description" id="description" rows="3" cols="40"></textarea></td></tr>';
    echo '<tr><th><label for="owner">' . esc_html__('Owner', 'heritagepress') . '</label></th>';
    echo '<td><input type="text" name="owner" id="owner" value="" class="regular-text"></td></tr>';
    echo '<tr><th><label for="email">' . esc_html__('Email', 'heritagepress') . '</label></th>';
    echo '<td><input type="email" name="email" id="email" value="" class="regular-text"></td></tr>';
    echo '<tr><th><label for="address">' . esc_html__('Address', 'heritagepress') . '</label></th>';
    echo '<td><input type="text" name="address" id="address" value="" class="regular-text"></td></tr>';
    echo '<tr><th><label for="city">' . esc_html__('City', 'heritagepress') . '</label></th>';
    echo '<td><input type="text" name="city" id="city" value="" class="regular-text"></td></tr>';
    echo '<tr><th><label for="state">' . esc_html__('State/Province', 'heritagepress') . '</label></th>';
    echo '<td><input type="text" name="state" id="state" value="" class="regular-text"></td></tr>';
    echo '<tr><th><label for="zip">' . esc_html__('Zip', 'heritagepress') . '</label></th>';
    echo '<td><input type="text" name="zip" id="zip" value="" class="regular-text"></td></tr>';
    echo '<tr><th><label for="country">' . esc_html__('Country', 'heritagepress') . '</label></th>';
    echo '<td><input type="text" name="country" id="country" value="" class="regular-text"></td></tr>';
    echo '<tr><th><label for="phone">' . esc_html__('Phone', 'heritagepress') . '</label></th>';
    echo '<td><input type="text" name="phone" id="phone" value="" class="regular-text"></td></tr>';
    echo '<tr><th><label for="private">' . esc_html__('Keep Owner/Contact Info Private', 'heritagepress') . '</label></th>';
    echo '<td><input type="checkbox" name="private" id="private" value="1"></td></tr>';
    echo '<tr><th><label for="disallowgedcreate">' . esc_html__('Disallow GEDCOM Download', 'heritagepress') . '</label></th>';
    echo '<td><input type="checkbox" name="disallowgedcreate" id="disallowgedcreate" value="1"></td></tr>';
    echo '<tr><th><label for="disallowpdf">' . esc_html__('Disallow PDF Generation', 'heritagepress') . '</label></th>';
    echo '<td><input type="checkbox" name="disallowpdf" id="disallowpdf" value="1"></td></tr>';
    echo '</table>';
    echo '<p class="submit">';
    echo '<input type="submit" class="button-primary" value="' . esc_attr__('Save and Continue', 'heritagepress') . '"> ';
    echo '<a href="' . esc_url(admin_url('admin.php?page=heritagepress-trees&tab=browse')) . '" class="button">' . esc_html__('Cancel', 'heritagepress') . '</a>';
    echo '</p>';
    echo '</form>';
    echo '</div>';
  } else {
    // Browse Trees tab
    echo '<form method="get" action="">';
    echo '<input type="hidden" name="page" value="heritagepress-trees">';
    echo '<input type="hidden" name="tab" value="browse">';
    echo '<input type="text" name="searchstring" value="' . esc_attr($searchstring) . '" placeholder="' . esc_attr__('Search trees...', 'heritagepress') . '"> ';
    echo '<input type="submit" class="button" value="' . esc_attr__('Search', 'heritagepress') . '"> ';
    echo '<a href="' . esc_url(admin_url('admin.php?page=heritagepress-trees&tab=browse')) . '" class="button">' . esc_html__('Reset', 'heritagepress') . '</a>';
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
    $where = '';
    if ($searchstring) {
      $like = '%' . $wpdb->esc_like($searchstring) . '%';
      $where = $wpdb->prepare("WHERE gedcom LIKE %s OR treename LIKE %s OR description LIKE %s OR owner LIKE %s", $like, $like, $like, $like);
    }

    // Get total count first
    $count_sql = "SELECT COUNT(*) FROM $trees_table $where";
    $total = $wpdb->get_var($count_sql);

    // Get the trees for this page
    $sql = "SELECT gedcom, treename, description, owner, lastimportdate, importfilename FROM $trees_table $where ORDER BY treename LIMIT $per_page OFFSET $offset";
    $trees = $wpdb->get_results($sql);
    if ($trees) {
      foreach ($trees as $tree) {
        $people_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(personID) FROM $people_table WHERE gedcom = %s", $tree->gedcom));
        // Format the date for display
        $formatted_date = '';
        if ($tree->lastimportdate && $tree->lastimportdate !== '0000-00-00 00:00:00') {
          $formatted_date = date('d M Y H:i:s', strtotime($tree->lastimportdate));
        }
        echo '<tr>';
        echo '<th scope="row" class="check-column"><input type="checkbox" name="tree_ids[]" value="' . esc_attr($tree->gedcom) . '"></th>';
        echo '<td>' . esc_html($tree->gedcom) . '</td>';
        echo '<td>' . esc_html($tree->treename) . '</td>';
        echo '<td>' . esc_html($tree->description) . '</td>';
        echo '<td style="text-align:right">' . number_format($people_count) . '</td>';
        echo '<td>' . esc_html($tree->owner) . '</td>';
        echo '<td>' . esc_html($formatted_date) . '</td>';
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
      'base' => add_query_arg(['paged' => '%#%', 'tab' => 'browse']),
      'format' => '',
      'prev_text' => __('&laquo; Previous'),
      'next_text' => __('Next &raquo;'),
      'total' => ceil($total / $per_page),
      'current' => $paged
    ]);
    if ($page_links) {
      echo '<div class="tablenav"><div class="tablenav-pages">' . $page_links . '</div></div>';
    }
    // JS for select all
    echo '<script>document.getElementById("cb-select-all").addEventListener("click",function(e){var cbs=document.querySelectorAll(\"input[name=\\\"tree_ids[]\\\"]\");for(var i=0;i<cbs.length;i++){cbs[i].checked=this.checked;}});</script>';
  }
  echo '</div>';
}

// Handle single delete/clear actions
add_action('admin_init', function () {
  if (!isset($_GET['page']) || $_GET['page'] !== 'heritagepress-trees') return;
  if (!current_user_can('manage_options')) return;
  global $wpdb;
  $trees_table = $wpdb->prefix . 'hp_trees';
  if (isset($_GET['action'], $_GET['tree'])) {
    $tree = sanitize_text_field(wp_unslash($_GET['tree']));
    if ($_GET['action'] === 'delete' && check_admin_referer('heritagepress_delete_tree_' . $tree)) {
      $wpdb->delete($trees_table, ['gedcom' => $tree]);
      wp_redirect(admin_url('admin.php?page=heritagepress-trees&tab=browse&message=' . urlencode(__('Tree deleted.', 'heritagepress'))));
      exit;
    }
    if ($_GET['action'] === 'clear' && check_admin_referer('heritagepress_clear_tree_' . $tree)) {
      // Implement tree clearing logic here (e.g., remove all people/events for this tree)
      wp_redirect(admin_url('admin.php?page=heritagepress-trees&tab=browse&message=' . urlencode(__('Tree cleared (not implemented).', 'heritagepress'))));
      exit;
    }
  }
});
