<?php
// HeritagePress: Reports admin page, 
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', 'heritagepress_add_reports_page');
function heritagepress_add_reports_page()
{
  add_submenu_page(
    'heritagepress',
    __('Reports', 'heritagepress'),
    __('Reports', 'heritagepress'),
    'manage_options',
    'heritagepress-reports',
    'heritagepress_render_reports_page'
  );
}

function heritagepress_render_reports_page()
{
  global $wpdb;
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  $message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
  $searchstring = isset($_GET['searchstring']) ? sanitize_text_field($_GET['searchstring']) : '';
  $activeonly = isset($_GET['activeonly']) ? sanitize_text_field($_GET['activeonly']) : '';
  $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
  $per_page = 20;
  $offset = ($paged - 1) * $per_page;

  $where = [];
  if ($searchstring) {
    $like = '%' . $wpdb->esc_like($searchstring) . '%';
    $where[] = $wpdb->prepare('(reportname LIKE %s OR reportdesc LIKE %s)', $like, $like);
  }
  if ($activeonly === 'yes') {
    $where[] = 'active = 1';
  }
  $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

  $reports_table = $wpdb->prefix . 'HeritagePress_reports';
  $total = $wpdb->get_var("SELECT COUNT(reportID) FROM $reports_table $where_sql");
  $results = $wpdb->get_results($wpdb->prepare(
    "SELECT reportID, reportname, reportdesc, ranking, active FROM $reports_table $where_sql ORDER BY ranking, reportname, reportID LIMIT %d OFFSET %d",
    $per_page,
    $offset
  ), ARRAY_A);

  $base_url = admin_url('admin.php?page=heritagepress-reports');
?>
  <div class="wrap">
    <h1><?php _e('Reports', 'heritagepress'); ?></h1>
    <?php if ($message): ?>
      <div class="notice notice-success is-dismissible">
        <p><?php echo esc_html($message); ?></p>
      </div>
    <?php endif; ?>
    <form method="get" action="">
      <input type="hidden" name="page" value="heritagepress-reports">
      <table class="form-table">
        <tr>
          <th><?php _e('Search for', 'heritagepress'); ?>:</th>
          <td><input type="text" name="searchstring" value="<?php echo esc_attr($searchstring); ?>" class="regular-text"></td>
          <td>
            <input type="submit" class="button" value="<?php esc_attr_e('Search', 'heritagepress'); ?>">
            <a href="<?php echo esc_url($base_url); ?>" class="button"><?php _e('Reset', 'heritagepress'); ?></a>
          </td>
        </tr>
        <tr>
          <td></td>
          <td colspan="2">
            <label><input type="checkbox" name="activeonly" value="yes" <?php checked($activeonly, 'yes'); ?>> <?php _e('Active only', 'heritagepress'); ?></label>
          </td>
        </tr>
      </table>
    </form>
    <table class="wp-list-table widefat fixed striped">
      <thead>
        <tr>
          <th><?php _e('Action', 'heritagepress'); ?></th>
          <th><?php _e('Rank', 'heritagepress'); ?></th>
          <th><?php _e('ID', 'heritagepress'); ?></th>
          <th><?php _e('Name, Description', 'heritagepress'); ?></th>
          <th><?php _e('Active?', 'heritagepress'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if ($results): ?>
          <?php foreach ($results as $row): ?>
            <tr>
              <td>
                <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-editreport&reportID=' . urlencode($row['reportID']))); ?>" class="button button-small" title="<?php esc_attr_e('Edit', 'heritagepress'); ?>">âœï¸</a>
                <a href="#" class="button button-small delete-report" data-report-id="<?php echo esc_attr($row['reportID']); ?>" title="<?php esc_attr_e('Delete', 'heritagepress'); ?>">ðŸ—‘ï¸</a>
                <a href="<?php echo esc_url(site_url('showre.php?reportID=' . urlencode($row['reportID']) . '&test=1')); ?>" class="button button-small" target="_blank" title="<?php esc_attr_e('Test', 'heritagepress'); ?>">ðŸ§ª</a>
              </td>
              <td><?php echo esc_html($row['ranking']); ?></td>
              <td><?php echo esc_html($row['reportID']); ?></td>
              <td><strong><?php echo esc_html($row['reportname']); ?></strong><br><?php echo esc_html($row['reportdesc']); ?></td>
              <td><?php echo $row['active'] ? __('Yes', 'heritagepress') : __('No', 'heritagepress'); ?></td>
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
          'activeonly' => $activeonly
        ],
        'type' => 'array',
      ]);
      echo '<div class="tablenav"><div class="tablenav-pages">' . join(' ', $page_links) . '</div></div>';
    endif;
    ?>
  </div>
  <script>
    jQuery(document).ready(function($) {
      $('.delete-report').on('click', function(e) {
        e.preventDefault();
        if (confirm('<?php echo esc_js(__('Are you sure you want to delete this report?', 'heritagepress')); ?>')) {
          var reportID = $(this).data('report-id');
          window.location = '<?php echo esc_url(admin_url('admin-post.php?action=heritagepress_delete_report&_wpnonce=' . wp_create_nonce('heritagepress_delete_report'))); ?>&reportID=' + encodeURIComponent(reportID);
        }
      });
    });
  </script>
<?php
}

// Handle delete action
add_action('admin_post_heritagepress_delete_report', 'heritagepress_handle_delete_report');
function heritagepress_handle_delete_report()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to delete reports.', 'heritagepress'));
  }
  check_admin_referer('heritagepress_delete_report');
  global $wpdb;
  $reportID = isset($_GET['reportID']) ? sanitize_text_field($_GET['reportID']) : '';
  if ($reportID) {
    $reports_table = $wpdb->prefix . 'HeritagePress_reports';
    $wpdb->delete($reports_table, ['reportID' => $reportID]);
    wp_redirect(admin_url('admin.php?page=heritagepress-reports&message=' . urlencode(__('Report deleted.', 'heritagepress'))));
    exit;
  }
  wp_redirect(admin_url('admin.php?page=heritagepress-reports&message=' . urlencode(__('No report ID specified.', 'heritagepress'))));
  exit;
}
