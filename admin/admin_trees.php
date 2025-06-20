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
  // Check if we need to refresh cached data (used after adding/editing a tree)
  $refresh = isset($_GET['refresh']) ? true : false;
  if ($refresh) {
    error_log("Force refresh requested for trees data");
    // We'll use this flag to ensure we get fresh data from the database
  }

  // Auto-populate missing created dates when page loads (run only once, not in a redirect loop)
  if (!isset($_GET['autopopulate']) && !isset($_GET['refresh'])) {
    $needs_created_update = $wpdb->get_var("SELECT COUNT(*) FROM $trees_table WHERE created IS NULL OR created = '0000-00-00 00:00:00' OR created = '1970-01-01 00:00:00'");
    if ($needs_created_update > 0) {
      error_log("Found $needs_created_update trees needing created date - auto-populating on page load");

      // Set SQL_MODE to empty to handle strict mode issues with dates
      $wpdb->query("SET SQL_MODE=''");

      $wpdb->query("UPDATE $trees_table SET created = CASE
          WHEN (created IS NULL OR created = '0000-00-00 00:00:00' OR created = '1970-01-01 00:00:00') THEN
              (CASE WHEN (lastimportdate IS NOT NULL AND lastimportdate != '0000-00-00 00:00:00' AND lastimportdate != '1970-01-01 00:00:00')
                  THEN lastimportdate
                  ELSE NOW()
              END)
          ELSE created
      END");

      $updated_count = $wpdb->get_var("SELECT COUNT(*) FROM $trees_table WHERE created IS NOT NULL AND created != '0000-00-00 00:00:00' AND created != '1970-01-01 00:00:00'");
      error_log("After silent update on page load: $updated_count trees have valid created dates");
    }
  }
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
    echo '<style id="heritagepress-browse-trees-style">
      .heritagepress-trees-table {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        overflow: hidden;
        margin-bottom: 2em;
      }
      .heritagepress-trees-table th, .heritagepress-trees-table td {
        padding: 12px 14px;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
      }
      .heritagepress-trees-table th {
        background: #f7f7fb;
        color: #333;
        font-weight: 600;
        font-size: 1.05em;
        border-bottom: 2px solid #e0e0e0;
      }
      .heritagepress-trees-table tr:last-child td {
        border-bottom: none;
      }
      .heritagepress-trees-table tr:hover {
        background: #f5f7fa;
        transition: background 0.2s;
      }
      .heritagepress-trees-table td .button-small {
        margin-right: 4px;
      }
      .heritagepress-trees-table td {
        font-size: 1.01em;
      }
      .heritagepress-trees-table td a {
        color: #3a3a8e;
        text-decoration: underline;
      }
      .heritagepress-trees-table td a:hover {
        color: #222266;
      }
      .heritagepress-trees-table .check-column {
        text-align: center;
      }
      .heritagepress-trees-table .people-col {
        text-align: right;
        min-width: 70px;
        font-variant-numeric: tabular-nums;
        font-weight: 500;
        color: #4a4a7a;
      }
      .heritagepress-trees-table .actions-col {
        white-space: nowrap;
      }
    </style>';
    echo '<form method="get" action="">';
    echo '<input type="hidden" name="page" value="heritagepress-trees">';
    echo '<input type="hidden" name="tab" value="browse">';
    echo '<input type="text" name="searchstring" value="' . esc_attr($searchstring) . '" placeholder="' . esc_attr__('Search trees...', 'heritagepress') . '" style="padding:6px 10px; border-radius:5px; border:1px solid #ccc; margin-right:8px;"> ';
    echo '<input type="submit" class="button" value="' . esc_attr__('Search', 'heritagepress') . '" style="margin-right:6px;"> ';
    echo '<a href="' . esc_url(admin_url('admin.php?page=heritagepress-trees&tab=browse')) . '" class="button">' . esc_html__('Reset', 'heritagepress') . '</a>';
    echo '</form><br />';

    echo '<form method="post">';
    wp_nonce_field('heritagepress_trees_bulk_action');
    echo '<input type="hidden" name="action" value="bulk-delete">';
    echo '<table class="heritagepress-trees-table">';
    echo '<thead><tr>';
    echo '<th class="manage-column column-cb check-column"><input type="checkbox" id="cb-select-all"></th>';
    echo '<th>' . esc_html__('ID', 'heritagepress') . '</th>';
    echo '<th>' . esc_html__('Tree Name', 'heritagepress') . '</th>';
    echo '<th>' . esc_html__('Description', 'heritagepress') . '</th>';
    echo '<th class="people-col">' . esc_html__('People', 'heritagepress') . '</th>';
    echo '<th>' . esc_html__('Owner', 'heritagepress') . '</th>';
    echo '<th>' . esc_html__('Last Import', 'heritagepress') . '</th>';
    echo '<th>' . esc_html__('Import Filename', 'heritagepress') . '</th>';
    echo '<th>' . esc_html__('Created', 'heritagepress') . '</th>';
    echo '<th class="actions-col">' . esc_html__('Actions', 'heritagepress') . '</th>';
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
    $sql = "SELECT gedcom, treename, description, owner, lastimportdate, importfilename, created FROM $trees_table $where ORDER BY treename LIMIT $per_page OFFSET $offset";
    error_log("SQL for trees: $sql");

    // If refresh is requested, clear the cache before querying
    if ($refresh) {
      $wpdb->flush();
      error_log("Cache flushed before fetching trees data");
    }

    $trees = $wpdb->get_results($sql);

    // Debug info - count how many trees have a created date set
    $created_count = $wpdb->get_var("SELECT COUNT(*) FROM $trees_table WHERE created IS NOT NULL AND created != '0000-00-00 00:00:00' AND created != '1970-01-01 00:00:00'");
    $null_count = $wpdb->get_var("SELECT COUNT(*) FROM $trees_table WHERE created IS NULL OR created = '0000-00-00 00:00:00' OR created = '1970-01-01 00:00:00'");
    error_log("Trees with created date: $created_count, Trees without created date: $null_count");

    // Also get a list of all trees with their created dates for debugging
    $all_trees = $wpdb->get_results("SELECT gedcom, created FROM $trees_table");
    foreach ($all_trees as $t) {
      error_log("Tree ID: {$t->gedcom}, Created date: " . ($t->created ?: 'NULL'));
    }

    // Debug - log the first tree's data
    if ($trees && count($trees) > 0) {
      error_log('First tree data: ' . print_r($trees[0], true));
    }

    if ($trees) {
      foreach ($trees as $tree) {
        $people_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(personID) FROM $people_table WHERE gedcom = %s", $tree->gedcom));
        // Format the date for display
        $formatted_date = '';
        if (!empty($tree->lastimportdate) && $tree->lastimportdate !== '0000-00-00 00:00:00' && strtotime($tree->lastimportdate) && strtotime($tree->lastimportdate) > 0) {
          $formatted_date = date('d M Y H:i:s', strtotime($tree->lastimportdate));
        }
        $created_date = '';
        if (!empty($tree->created) && $tree->created !== '0000-00-00 00:00:00' && strtotime($tree->created) && strtotime($tree->created) > 0) {
          $created_date = date('d M Y H:i:s', strtotime($tree->created));
        }
        echo '<tr>';
        echo '<th scope="row" class="check-column"><input type="checkbox" name="tree_ids[]" value="' . esc_attr($tree->gedcom) . '"></th>';
        echo '<td><a href="' . esc_url(admin_url('admin.php?page=heritagepress-edittree&tree=' . urlencode($tree->gedcom))) . '" style="text-decoration:underline; font-weight:600; color:#2a2a7a;">' . esc_html($tree->gedcom) . '</a></td>';
        echo '<td>' . esc_html($tree->treename) . '</td>';
        echo '<td>' . esc_html($tree->description) . '</td>';
        echo '<td class="people-col">' . number_format($people_count) . '</td>';
        echo '<td>' . esc_html($tree->owner) . '</td>';
        echo '<td>' . esc_html($formatted_date) . '</td>';
        echo '<td>' . esc_html($tree->importfilename) . '</td>';
        echo '<td>' . esc_html($created_date) . '</td>';
        echo '<td class="actions-col">';
        echo '<a href="' . esc_url(admin_url('admin.php?page=heritagepress-edittree&tree=' . urlencode($tree->gedcom))) . '" class="button button-small">' . esc_html__('Edit', 'heritagepress') . '</a> ';
        echo '<a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=heritagepress-trees&action=delete&tree=' . urlencode($tree->gedcom)), 'heritagepress_delete_tree_' . $tree->gedcom)) . '" class="button button-small" onclick="return confirm(\'' . esc_js(__('Are you sure you want to delete this tree?', 'heritagepress')) . '\');">' . esc_html__('Delete', 'heritagepress') . '</a> ';
        echo '<a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=heritagepress-trees&action=clear&tree=' . urlencode($tree->gedcom)), 'heritagepress_clear_tree_' . $tree->gedcom)) . '" class="button button-small heritagepress-clear-btn" data-treeid="' . esc_attr($tree->gedcom) . '">' . esc_html__('Clear', 'heritagepress') . '</a>';
        echo '</td>';
        echo '</tr>';
      }
    } else {
      echo '<tr><td colspan="10">' . esc_html__('No trees found.', 'heritagepress') . '</td></tr>';
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
    echo '<script>
    document.querySelectorAll(".heritagepress-clear-btn").forEach(function(btn) {
      btn.addEventListener("click", function(e) {
        e.preventDefault();
        var treeId = this.getAttribute("data-treeid");
        var url = this.getAttribute("href");
        var warning = `\n\n⚠️  WARNING!  ⚠️\n\nThis will PERMANENTLY DELETE ALL DATA related to this tree, including:\n- People\n- Families\n- Media\n- Sources\n- Repositories\n- Events\n\nThe tree record itself will remain, but all its data will be IRREVERSIBLY REMOVED.\n\nAre you absolutely sure you want to clear all data for tree: "${treeId}"?`;
        if (window.confirm(warning)) {
          window.location.href = url;
        }
      });
    });
    </script>';
  }
  echo '</div>'; // End of main wrap div
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
      // Delete all related data for this tree, but keep the tree record itself
      $tables_to_clear = [
        $wpdb->prefix . 'hp_people',
        $wpdb->prefix . 'hp_families',
        $wpdb->prefix . 'hp_sources',
        $wpdb->prefix . 'hp_repositories',
        $wpdb->prefix . 'hp_media',
        $wpdb->prefix . 'hp_events',
        $wpdb->prefix . 'hp_citations',
        $wpdb->prefix . 'hp_notes',
        $wpdb->prefix . 'hp_places',
        $wpdb->prefix . 'hp_addresses',
        $wpdb->prefix . 'hp_children',
        $wpdb->prefix . 'hp_assoc',
        $wpdb->prefix . 'hp_xnotes',
        $wpdb->prefix . 'hp_branchlinks',
        $wpdb->prefix . 'hp_temp_events',
        $wpdb->prefix . 'hp_album2entities',
        $wpdb->prefix . 'hp_medialinks',
        $wpdb->prefix . 'hp_notelinks',
        $wpdb->prefix . 'hp_eventlinks',
        $wpdb->prefix . 'hp_branch',
        $wpdb->prefix . 'hp_templelinks',
        $wpdb->prefix . 'hp_reportlinks',
        $wpdb->prefix . 'hp_saveimport',
        $wpdb->prefix . 'hp_treelinks',
        $wpdb->prefix . 'hp_mapdata',
        $wpdb->prefix . 'hp_maptrees',
        $wpdb->prefix . 'hp_maptree',
        $wpdb->prefix . 'hp_maptree2',
        $wpdb->prefix . 'hp_maptree3',
        $wpdb->prefix . 'hp_maptree4',
        $wpdb->prefix . 'hp_maptree5',
        $wpdb->prefix . 'hp_maptree6',
        $wpdb->prefix . 'hp_maptree7',
        $wpdb->prefix . 'hp_maptree8',
        $wpdb->prefix . 'hp_maptree9',
        $wpdb->prefix . 'hp_maptree10',
        // Add more tables as needed
      ];
      foreach ($tables_to_clear as $table) {
        $wpdb->delete($table, ['gedcom' => $tree]);
      }
      wp_redirect(admin_url('admin.php?page=heritagepress-trees&tab=browse&message=' . urlencode(__('All data for this tree has been cleared.', 'heritagepress'))));
      exit;
    }
  }
});

// Handle bulk delete POST action
add_action('admin_init', function () {
  if (
    isset($_POST['action']) && $_POST['action'] === 'bulk-delete' &&
    isset($_POST['tree_ids']) && is_array($_POST['tree_ids']) &&
    check_admin_referer('heritagepress_trees_bulk_action')
  ) {
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
    }
    global $wpdb;
    $trees_table = $wpdb->prefix . 'hp_trees';
    $deleted = 0;
    foreach ($_POST['tree_ids'] as $tree_id) {
      $tree_id = sanitize_text_field($tree_id);
      $result = $wpdb->delete($trees_table, ['gedcom' => $tree_id]);
      if ($result !== false) {
        $deleted++;
      }
    }
    $msg = $deleted > 0 ? sprintf(_n('%d tree deleted.', '%d trees deleted.', $deleted, 'heritagepress'), $deleted) : __('No trees deleted.', 'heritagepress');
    wp_redirect(admin_url('admin.php?page=heritagepress-trees&tab=browse&message=' . urlencode($msg)));
    exit;
  }
});

// Add this function to ensure the 'created' column exists on plugin activation
// Note: This hook should be moved to the main plugin file (heritagepress.php) with proper __FILE__ reference
register_activation_hook(__FILE__, function () {
  global $wpdb;
  $trees_table = $wpdb->prefix . 'hp_trees';

  // Check if created column exists
  $column = $wpdb->get_results("SHOW COLUMNS FROM $trees_table LIKE 'created'");
  if (empty($column)) {
    $wpdb->query("ALTER TABLE $trees_table ADD COLUMN created DATETIME NULL DEFAULT NULL AFTER importfilename");
    error_log('Added created column to ' . $trees_table);
  }

  // If the column exists but has missing values, populate them
  $needs_created_update = $wpdb->get_var("SELECT COUNT(*) FROM $trees_table WHERE created IS NULL OR created = '0000-00-00 00:00:00'");
  if ($needs_created_update > 0) {
    $wpdb->query("UPDATE $trees_table SET created = CASE WHEN (created IS NULL OR created = '0000-00-00 00:00:00') THEN (CASE WHEN (lastimportdate IS NOT NULL AND lastimportdate != '0000-00-00 00:00:00') THEN lastimportdate ELSE NOW() END) ELSE created END");
    error_log('Populated ' . $needs_created_update . ' missing created dates in ' . $trees_table);
  }
});

// Add handler for manual autopopulate action
add_action('admin_init', function () {
  if (!isset($_GET['page']) || $_GET['page'] !== 'heritagepress-trees') return;
  if (!current_user_can('manage_options')) return;

  // Only run when explicitly requested with the autopopulate parameter
  if (isset($_GET['autopopulate']) && $_GET['autopopulate'] == '1' && check_admin_referer('heritagepress_autopopulate_created')) {
    global $wpdb;
    $trees_table = $wpdb->prefix . 'hp_trees';

    // Count trees needing updates before running the query
    $needs_update_count = $wpdb->get_var("SELECT COUNT(*) FROM $trees_table WHERE created IS NULL OR created = '0000-00-00 00:00:00' OR created = '1970-01-01 00:00:00'");
    // Set SQL_MODE to empty to handle strict mode issues with dates
    $wpdb->query("SET SQL_MODE=''");

    // Update all trees with missing or invalid dates
    $wpdb->query("UPDATE $trees_table SET created = CASE
        WHEN (created IS NULL OR created = '0000-00-00 00:00:00' OR created = '1970-01-01 00:00:00') THEN
            (CASE WHEN (lastimportdate IS NOT NULL AND lastimportdate != '0000-00-00 00:00:00' AND lastimportdate != '1970-01-01 00:00:00')
                THEN lastimportdate
                ELSE NOW()
            END)
        ELSE created
    END");

    // Verify the update worked
    $updated_count = $wpdb->get_var("SELECT COUNT(*) FROM $trees_table WHERE created IS NOT NULL AND created != '0000-00-00 00:00:00' AND created != '1970-01-01 00:00:00'");
    error_log("Autopopulate: Updated $needs_update_count trees. Now $updated_count trees have valid created dates");
    // Add refresh parameter to force data reload but don't include autopopulate
    wp_redirect(admin_url('admin.php?page=heritagepress-trees&tab=browse&message=' . urlencode(__('All missing "Created" dates have been autopopulated.', 'heritagepress'))));
    exit;
  }
});

// Add a handler for the tree creation form submission
add_action('admin_post_heritagepress_add_newtree', function () {
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }

  error_log('=== HeritagePress Add Tree Handler Called ===');

  check_admin_referer('heritagepress_newtree');
  error_log('Nonce check passed');

  global $wpdb;
  $trees_table = $wpdb->prefix . 'hp_trees';
  error_log('Using table: ' . $trees_table);

  $tree_id = isset($_POST['tree_id']) ? sanitize_text_field($_POST['tree_id']) : '';
  $tree_name = isset($_POST['tree_name']) ? sanitize_text_field($_POST['tree_name']) : '';
  $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
  $owner = isset($_POST['owner']) ? sanitize_text_field($_POST['owner']) : '';
  $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
  $address = isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '';
  $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
  $state = isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '';
  $zip = isset($_POST['zip']) ? sanitize_text_field($_POST['zip']) : '';
  $country = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '';
  $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
  $private = isset($_POST['private']) ? 1 : 0;
  $disallowgedcreate = isset($_POST['disallowgedcreate']) ? 1 : 0;
  $disallowpdf = isset($_POST['disallowpdf']) ? 1 : 0;

  error_log('Parsed data - tree_id: ' . $tree_id . ', tree_name: ' . $tree_name);

  if (empty($tree_id) || empty($tree_name)) {
    wp_redirect(admin_url('admin.php?page=heritagepress-trees&tab=add&message=' . urlencode(__('Tree ID and Tree Name are required.', 'heritagepress'))));
    exit;
  }
  // Get current MySQL timestamp - using direct SQL NOW() to avoid any PHP function issues
  $current_time = $wpdb->get_var("SELECT NOW()");
  error_log('Current time from database: ' . $current_time);

  // Insert new tree with created date
  $insert_data = [
    'gedcom' => $tree_id,
    'treename' => $tree_name,
    'description' => $description,
    'owner' => $owner,
    'email' => $email,
    'address' => $address,
    'city' => $city,
    'state' => $state,
    'zip' => $zip,
    'country' => $country,
    'phone' => $phone,
    'secret' => $private,
    'disallowgedcreate' => $disallowgedcreate,
    'disallowpdf' => $disallowpdf,
    'lastimportdate' => '1970-01-01 00:00:00',
    'importfilename' => '',
    'created' => $current_time, // Explicitly set created timestamp on insert using direct SQL NOW() value
  ];

  error_log('Insert data: ' . print_r($insert_data, true));

  $result = $wpdb->insert($trees_table, $insert_data);
  error_log('Insert result: ' . ($result !== false ? $result : 'ERROR: ' . $wpdb->last_error));

  if ($result === false) {
    $error = $wpdb->last_error;
    error_log('Insert failed: ' . $error);
    wp_die('Database error: ' . esc_html($error));
  }  // IMPORTANT: Double-check and force update the created date using direct SQL
  // This ensures it's set correctly even if the insert didn't properly set it
  // First set SQL_MODE to empty to handle strict mode issues with dates
  $wpdb->query("SET SQL_MODE=''");
  $fix_query = $wpdb->prepare("UPDATE $trees_table SET created = NOW() WHERE gedcom = %s AND (created IS NULL OR created = '0000-00-00 00:00:00' OR created = '1970-01-01 00:00:00')", $tree_id);
  $result2 = $wpdb->query($fix_query);
  error_log('Additional direct update result: ' . $result2);
  error_log('Force-updated the created field with direct SQL: ' . $fix_query);

  error_log('Insert successful, redirecting...');

  // Verify the created field was saved correctly
  $saved_record = $wpdb->get_row($wpdb->prepare("SELECT gedcom, treename, created FROM $trees_table WHERE gedcom = %s", $tree_id));
  $created_value = isset($saved_record->created) ? $saved_record->created : 'NOT SET';
  error_log('Saved record created field: ' . $created_value);

  // Double check with a direct query to be absolutely sure
  $created_direct = $wpdb->get_var($wpdb->prepare("SELECT created FROM $trees_table WHERE gedcom = %s", $tree_id));
  error_log('Direct query for created field: ' . ($created_direct ?: 'NULL'));  // Just redirect without a refresh parameter to avoid infinite loops
  wp_redirect(admin_url('admin.php?page=heritagepress-trees&tab=browse&message=' . urlencode(__('Tree added successfully.', 'heritagepress'))));
  exit;
});
