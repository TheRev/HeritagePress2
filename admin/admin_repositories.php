<?php
// HeritagePress: Repositories admin page, ported from TNG admin_repositories.php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', 'heritagepress_add_repositories_page');
function heritagepress_add_repositories_page()
{
  add_submenu_page(
    'heritagepress',
    __('Repositories', 'heritagepress'),
    __('Repositories', 'heritagepress'),
    'manage_options',
    'heritagepress-repositories',
    'heritagepress_render_repositories_page'
  );
}

function heritagepress_render_repositories_page()
{
  global $wpdb;
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }

  // Handle search/filter
  $searchstring = isset($_GET['searchstring']) ? sanitize_text_field($_GET['searchstring']) : '';
  $tree = isset($_GET['tree']) ? sanitize_text_field($_GET['tree']) : '';
  $exactmatch = isset($_GET['exactmatch']) ? sanitize_text_field($_GET['exactmatch']) : '';
  $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
  $per_page = 20;
  $offset = ($paged - 1) * $per_page;

  $where = [];
  if ($tree) {
    $where[] = $wpdb->prepare('gedcom = %s', $tree);
  }
  if ($searchstring) {
    $like = '%' . $wpdb->esc_like($searchstring) . '%';
    if ($exactmatch === 'yes') {
      $where[] = $wpdb->prepare('(repoID = %s OR reponame = %s)', $searchstring, $searchstring);
    } else {
      $where[] = $wpdb->prepare('(repoID LIKE %s OR reponame LIKE %s)', $like, $like);
    }
  }
  $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
  $repositories_table = $wpdb->prefix . 'tng_repositories';
  $trees_table = $wpdb->prefix . 'tng_trees';
  $total = $wpdb->get_var("SELECT COUNT(ID) FROM $repositories_table $where_sql");
  $results = $wpdb->get_results($wpdb->prepare(
    "SELECT ID, repoID, reponame, gedcom, changedby, DATE_FORMAT(changedate,'%d %b %Y') as changedatef FROM $repositories_table $where_sql ORDER BY repoID LIMIT %d OFFSET %d",
    $per_page,
    $offset
  ), ARRAY_A);

  // Get trees for filter dropdown
  $trees = $wpdb->get_results("SELECT gedcom, treename FROM $trees_table ORDER BY treename");

  $base_url = admin_url('admin.php?page=heritagepress-repositories');
?>
  <div class="wrap">
    <h1><?php _e('Repositories', 'heritagepress'); ?></h1>
    <form method="get" action="">
      <input type="hidden" name="page" value="heritagepress-repositories">
      <table class="form-table">
        <tr>
          <th><?php _e('Search for', 'heritagepress'); ?>:</th>
          <td><input type="text" name="searchstring" value="<?php echo esc_attr($searchstring); ?>" class="regular-text"></td>
          <td>
            <select name="tree">
              <option value=""><?php _e('All Trees', 'heritagepress'); ?></option>
              <?php foreach ($trees as $t): ?>
                <option value="<?php echo esc_attr($t->gedcom); ?>" <?php selected($tree, $t->gedcom); ?>><?php echo esc_html($t->treename); ?></option>
              <?php endforeach; ?>
            </select>
            <label><input type="checkbox" name="exactmatch" value="yes" <?php checked($exactmatch, 'yes'); ?>> <?php _e('Exact match', 'heritagepress'); ?></label>
            <input type="submit" class="button" value="<?php esc_attr_e('Search', 'heritagepress'); ?>">
            <a href="<?php echo esc_url($base_url); ?>" class="button"><?php _e('Reset', 'heritagepress'); ?></a>
          </td>
        </tr>
      </table>
    </form>
    <table class="wp-list-table widefat fixed striped">
      <thead>
        <tr>
          <th><?php _e('Action', 'heritagepress'); ?></th>
          <th><?php _e('ID', 'heritagepress'); ?></th>
          <th><?php _e('Name', 'heritagepress'); ?></th>
          <th><?php _e('Tree', 'heritagepress'); ?></th>
          <th><?php _e('Last Modified', 'heritagepress'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if ($results): ?>
          <?php foreach ($results as $row): ?>
            <tr>
              <td>
                <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-editrepo&repoID=' . urlencode($row['repoID']) . '&tree=' . urlencode($row['gedcom']))); ?>" class="button button-small" title="<?php esc_attr_e('Edit', 'heritagepress'); ?>">✏️</a>
                <a href="#" class="button button-small delete-repo" data-repo-id="<?php echo esc_attr($row['ID']); ?>" title="<?php esc_attr_e('Delete', 'heritagepress'); ?>">🗑️</a>
                <a href="<?php echo esc_url(site_url('showrepo.php?repoID=' . urlencode($row['repoID']) . '&tree=' . urlencode($row['gedcom']))); ?>" class="button button-small" target="_blank" title="<?php esc_attr_e('Test', 'heritagepress'); ?>">🧪</a>
              </td>
              <td><?php echo esc_html($row['repoID']); ?></td>
              <td><?php echo esc_html($row['reponame']); ?></td>
              <td><?php echo esc_html($row['gedcom']); ?></td>
              <td><?php echo esc_html($row['changedatef']); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5"><?php _e('No records found.', 'heritagepress'); ?></td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
    <?php
    // Pagination
    $total_pages = ceil($total / $per_page);
    if ($total_pages > 1):
      $page_links = paginate_links([
        'base' => add_query_arg('paged', '%#%'),
        'format' => '',
        'current' => $paged,
        'total' => $total_pages,
        'add_args' => [
          'searchstring' => $searchstring,
          'tree' => $tree,
          'exactmatch' => $exactmatch
        ],
        'type' => 'array',
      ]);
      echo '<div class="tablenav"><div class="tablenav-pages">' . join(' ', $page_links) . '</div></div>';
    endif;
    ?>
  </div>
  <script>
    jQuery(document).ready(function($) {
      $('.delete-repo').on('click', function(e) {
        e.preventDefault();
        if (confirm('<?php echo esc_js(__('Are you sure you want to delete this repository?', 'heritagepress')); ?>')) {
          var repoID = $(this).data('repo-id');
          window.location = '<?php echo esc_url(admin_url('admin-post.php?action=heritagepress_delete_repo&_wpnonce=' . wp_create_nonce('heritagepress_delete_repo'))); ?>&ID=' + encodeURIComponent(repoID);
        }
      });
    });
  </script>
<?php
}

// Handle delete action
add_action('admin_post_heritagepress_delete_repo', 'heritagepress_handle_delete_repo');
function heritagepress_handle_delete_repo()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to delete repositories.', 'heritagepress'));
  }
  check_admin_referer('heritagepress_delete_repo');
  global $wpdb;
  $ID = isset($_GET['ID']) ? intval($_GET['ID']) : 0;
  if ($ID) {
    $repositories_table = $wpdb->prefix . 'tng_repositories';
    $wpdb->delete($repositories_table, ['ID' => $ID]);
    wp_redirect(admin_url('admin.php?page=heritagepress-repositories&message=' . urlencode(__('Repository deleted.', 'heritagepress'))));
    exit;
  }
  wp_redirect(admin_url('admin.php?page=heritagepress-repositories&message=' . urlencode(__('No repository ID specified.', 'heritagepress'))));
  exit;
}
