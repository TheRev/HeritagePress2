<?php
// HeritagePress: Places admin page, 
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', 'heritagepress_add_places_page');
function heritagepress_add_places_page()
{
  add_submenu_page(
    'heritagepress',
    __('Places', 'heritagepress'),
    __('Places', 'heritagepress'),
    'manage_options',
    'heritagepress-places',
    'heritagepress_render_places_page'
  );
}

function heritagepress_render_places_page()
{
  global $wpdb;
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }

  // Handle search/filter
  $searchstring = isset($_GET['searchstring']) ? sanitize_text_field($_GET['searchstring']) : '';
  $exactmatch = isset($_GET['exactmatch']) ? sanitize_text_field($_GET['exactmatch']) : '';
  $nocoords = isset($_GET['nocoords']) ? sanitize_text_field($_GET['nocoords']) : '';
  $noevents = isset($_GET['noevents']) ? sanitize_text_field($_GET['noevents']) : '';
  $nolevel = isset($_GET['nolevel']) ? sanitize_text_field($_GET['nolevel']) : '';
  $temples = isset($_GET['temples']) ? sanitize_text_field($_GET['temples']) : '';
  $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
  $per_page = 20;
  $offset = ($paged - 1) * $per_page;

  $where = [];
  if ($searchstring) {
    $like = '%' . $wpdb->esc_like($searchstring) . '%';
    if ($exactmatch === 'yes') {
      $where[] = $wpdb->prepare('(place = %s OR notes = %s)', $searchstring, $searchstring);
    } else {
      $where[] = $wpdb->prepare('(place LIKE %s OR notes LIKE %s)', $like, $like);
    }
  }
  if ($nocoords === 'yes') {
    $where[] = '(latitude IS NULL OR latitude = "" OR longitude IS NULL OR longitude = "")';
  }
  if ($nolevel === 'yes') {
    $where[] = '(placelevel IS NULL OR placelevel = "" OR placelevel = "0")';
  }
  if ($temples === 'yes') {
    $where[] = 'temple = 1';
  }
  // Note: $noevents filter is complex in HeritagePress, skipping for MVP. Add if needed.

  $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
  $places_table = $wpdb->prefix . 'HeritagePress_places';
  $total = $wpdb->get_var("SELECT COUNT(ID) FROM $places_table $where_sql");
  $results = $wpdb->get_results($wpdb->prepare(
    "SELECT ID, place, placelevel, longitude, latitude, zoom, changedby, DATE_FORMAT(changedate,'%d %b %Y') as changedatef FROM $places_table $where_sql ORDER BY place LIMIT %d OFFSET %d",
    $per_page,
    $offset
  ), ARRAY_A);

  $base_url = admin_url('admin.php?page=heritagepress-places');
?>
  <div class="wrap">
    <h1><?php _e('Places', 'heritagepress'); ?></h1>
    <form method="get" action="">
      <input type="hidden" name="page" value="heritagepress-places">
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
            <label><input type="checkbox" name="exactmatch" value="yes" <?php checked($exactmatch, 'yes'); ?>> <?php _e('Exact match', 'heritagepress'); ?></label>
            <label><input type="checkbox" name="nocoords" value="yes" <?php checked($nocoords, 'yes'); ?>> <?php _e('No coordinates', 'heritagepress'); ?></label>
            <label><input type="checkbox" name="nolevel" value="yes" <?php checked($nolevel, 'yes'); ?>> <?php _e('No level', 'heritagepress'); ?></label>
            <label><input type="checkbox" name="temples" value="yes" <?php checked($temples, 'yes'); ?>> <?php _e('Temples', 'heritagepress'); ?></label>
          </td>
        </tr>
      </table>
    </form>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <?php wp_nonce_field('heritagepress_delete_places', 'heritagepress_delete_places_nonce'); ?>
      <input type="hidden" name="action" value="heritagepress_delete_places">
      <table class="wp-list-table widefat fixed striped">
        <thead>
          <tr>
            <th><?php _e('Action', 'heritagepress'); ?></th>
            <th><?php _e('Select', 'heritagepress'); ?></th>
            <th><?php _e('Place', 'heritagepress'); ?></th>
            <th><?php _e('Level', 'heritagepress'); ?></th>
            <th><?php _e('Latitude', 'heritagepress'); ?></th>
            <th><?php _e('Longitude', 'heritagepress'); ?></th>
            <th><?php _e('Zoom', 'heritagepress'); ?></th>
            <th><?php _e('Last Modified', 'heritagepress'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if ($results): ?>
            <?php foreach ($results as $row): ?>
              <tr>
                <td>
                  <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-editplace&ID=' . urlencode($row['ID']))); ?>" class="button button-small" title="<?php esc_attr_e('Edit', 'heritagepress'); ?>">âœï¸</a>
                  <a href="#" class="button button-small delete-place" data-place-id="<?php echo esc_attr($row['ID']); ?>" title="<?php esc_attr_e('Delete', 'heritagepress'); ?>">ðŸ—‘ï¸</a>
                </td>
                <td><input type="checkbox" name="del[]" value="<?php echo esc_attr($row['ID']); ?>"></td>
                <td><?php echo esc_html($row['place']); ?></td>
                <td><?php echo esc_html($row['placelevel']); ?></td>
                <td><?php echo esc_html($row['latitude']); ?></td>
                <td><?php echo esc_html($row['longitude']); ?></td>
                <td><?php echo esc_html($row['zoom']); ?></td>
                <td><?php echo esc_html($row['changedatef']); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8"><?php _e('No records found.', 'heritagepress'); ?></td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
      <p>
        <input type="button" id="select-all-places" class="button" value="<?php esc_attr_e('Select All', 'heritagepress'); ?>">
        <input type="button" id="clear-all-places" class="button" value="<?php esc_attr_e('Clear All', 'heritagepress'); ?>">
        <input type="submit" class="button" value="<?php esc_attr_e('Delete Selected', 'heritagepress'); ?>" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete the selected places?', 'heritagepress')); ?>');">
      </p>
    </form>
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
          'exactmatch' => $exactmatch,
          'nocoords' => $nocoords,
          'nolevel' => $nolevel,
          'temples' => $temples
        ],
        'type' => 'array',
      ]);
      echo '<div class="tablenav"><div class="tablenav-pages">' . join(' ', $page_links) . '</div></div>';
    endif;
    ?>
  </div>
  <script>
    jQuery(document).ready(function($) {
      $('#select-all-places').on('click', function() {
        $('input[name="del[]"]').prop('checked', true);
      });
      $('#clear-all-places').on('click', function() {
        $('input[name="del[]"]').prop('checked', false);
      });
      $('.delete-place').on('click', function(e) {
        e.preventDefault();
        if (confirm('<?php echo esc_js(__('Are you sure you want to delete this place?', 'heritagepress')); ?>')) {
          var placeID = $(this).data('place-id');
          window.location = '<?php echo esc_url(admin_url('admin-post.php?action=heritagepress_delete_place&_wpnonce=' . wp_create_nonce('heritagepress_delete_place'))); ?>&ID=' + encodeURIComponent(placeID);
        }
      });
    });
  </script>
<?php
}

// Handle single delete action
add_action('admin_post_heritagepress_delete_place', 'heritagepress_handle_delete_place');
function heritagepress_handle_delete_place()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to delete places.', 'heritagepress'));
  }
  check_admin_referer('heritagepress_delete_place');
  global $wpdb;
  $ID = isset($_GET['ID']) ? intval($_GET['ID']) : 0;
  if ($ID) {
    $places_table = $wpdb->prefix . 'HeritagePress_places';
    $wpdb->delete($places_table, ['ID' => $ID]);
    wp_redirect(admin_url('admin.php?page=heritagepress-places&message=' . urlencode(__('Place deleted.', 'heritagepress'))));
    exit;
  }
  wp_redirect(admin_url('admin.php?page=heritagepress-places&message=' . urlencode(__('No place ID specified.', 'heritagepress'))));
  exit;
}

// Handle batch delete action
add_action('admin_post_heritagepress_delete_places', 'heritagepress_handle_delete_places');
function heritagepress_handle_delete_places()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to delete places.', 'heritagepress'));
  }
  check_admin_referer('heritagepress_delete_places');
  global $wpdb;
  $IDs = isset($_POST['del']) ? array_map('intval', (array)$_POST['del']) : [];
  if ($IDs) {
    $places_table = $wpdb->prefix . 'HeritagePress_places';
    foreach ($IDs as $ID) {
      $wpdb->delete($places_table, ['ID' => $ID]);
    }
    wp_redirect(admin_url('admin.php?page=heritagepress-places&message=' . urlencode(__('Selected places deleted.', 'heritagepress'))));
    exit;
  }
  wp_redirect(admin_url('admin.php?page=heritagepress-places&message=' . urlencode(__('No places selected.', 'heritagepress'))));
  exit;
}
