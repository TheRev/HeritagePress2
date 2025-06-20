<?php
// HeritagePress: People admin page, 
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', 'heritagepress_add_people_page');
function heritagepress_add_people_page()
{
  add_submenu_page(
    'heritagepress',
    __('People', 'heritagepress'),
    __('People', 'heritagepress'),
    'manage_options',
    'heritagepress-people',
    'heritagepress_render_people_page'
  );
}

function heritagepress_render_people_page()
{
  global $wpdb;
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  $message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
  $searchstring = isset($_GET['searchstring']) ? sanitize_text_field($_GET['searchstring']) : '';
  $tree = isset($_GET['tree']) ? sanitize_text_field($_GET['tree']) : '';
  $living = isset($_GET['living']) ? sanitize_text_field($_GET['living']) : '';
  $private = isset($_GET['private']) ? sanitize_text_field($_GET['private']) : '';
  $exactmatch = isset($_GET['exactmatch']) ? sanitize_text_field($_GET['exactmatch']) : '';
  $nokids = isset($_GET['nokids']) ? sanitize_text_field($_GET['nokids']) : '';
  $noparents = isset($_GET['noparents']) ? sanitize_text_field($_GET['noparents']) : '';
  $nospouse = isset($_GET['nospouse']) ? sanitize_text_field($_GET['nospouse']) : '';
  $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
  $per_page = 20;
  $offset = ($paged - 1) * $per_page;

  $where = [];
  if ($searchstring) {
    $like = '%' . $wpdb->esc_like($searchstring) . '%';
    $where[] = $wpdb->prepare('(personID LIKE %s OR lastname LIKE %s OR firstname LIKE %s)', $like, $like, $like);
  }
  if ($tree) {
    $where[] = $wpdb->prepare('gedcom = %s', $tree);
  }
  if ($living === 'yes') {
    $where[] = 'living = 1';
  }
  if ($private === 'yes') {
    $where[] = 'private = 1';
  }
  if ($nokids === 'yes') {
    $where[] = 'children = 0';
  }
  if ($noparents === 'yes') {
    $where[] = 'father = "" AND mother = ""';
  }
  if ($nospouse === 'yes') {
    $where[] = 'spouse = ""';
  }
  $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

  $people_table = $wpdb->prefix . 'HeritagePress_people';
  $total = $wpdb->get_var("SELECT COUNT(personID) FROM $people_table $where_sql");
  $results = $wpdb->get_results($wpdb->prepare(
    "SELECT personID, lastname, firstname, birthdate, deathdate, living, private FROM $people_table $where_sql ORDER BY lastname, firstname, personID LIMIT %d OFFSET %d",
    $per_page,
    $offset
  ), ARRAY_A);

  $base_url = admin_url('admin.php?page=heritagepress-people');
?>
  <div class="wrap">
    <h1><?php _e('People', 'heritagepress'); ?></h1>
    <?php if ($message): ?>
      <div class="notice notice-success is-dismissible">
        <p><?php echo esc_html($message); ?></p>
      </div>
    <?php endif; ?>
    <form method="get" action="">
      <input type="hidden" name="page" value="heritagepress-people">
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
          <th><?php _e('Tree', 'heritagepress'); ?>:</th>
          <td colspan="2"><input type="text" name="tree" value="<?php echo esc_attr($tree); ?>" class="regular-text"></td>
        </tr>
        <tr>
          <td colspan="3">
            <label><input type="checkbox" name="living" value="yes" <?php checked($living, 'yes'); ?>> <?php _e('Living', 'heritagepress'); ?></label>
            <label><input type="checkbox" name="private" value="yes" <?php checked($private, 'yes'); ?>> <?php _e('Private', 'heritagepress'); ?></label>
            <label><input type="checkbox" name="exactmatch" value="yes" <?php checked($exactmatch, 'yes'); ?>> <?php _e('Exact Match', 'heritagepress'); ?></label>
            <label><input type="checkbox" name="nokids" value="yes" <?php checked($nokids, 'yes'); ?>> <?php _e('No Kids', 'heritagepress'); ?></label>
            <label><input type="checkbox" name="noparents" value="yes" <?php checked($noparents, 'yes'); ?>> <?php _e('No Parents', 'heritagepress'); ?></label>
            <label><input type="checkbox" name="nospouse" value="yes" <?php checked($nospouse, 'yes'); ?>> <?php _e('No Spouse', 'heritagepress'); ?></label>
          </td>
        </tr>
      </table>
    </form>
    <table class="wp-list-table widefat fixed striped">
      <thead>
        <tr>
          <th><?php _e('Action', 'heritagepress'); ?></th>
          <th><?php _e('ID', 'heritagepress'); ?></th>
          <th><?php _e('Last Name', 'heritagepress'); ?></th>
          <th><?php _e('First Name', 'heritagepress'); ?></th>
          <th><?php _e('Birth Date', 'heritagepress'); ?></th>
          <th><?php _e('Death Date', 'heritagepress'); ?></th>
          <th><?php _e('Living', 'heritagepress'); ?></th>
          <th><?php _e('Private', 'heritagepress'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if ($results): ?>
          <?php foreach ($results as $row): ?>
            <tr>
              <td>
                <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-editperson&personID=' . urlencode($row['personID']))); ?>" class="button button-small" title="<?php esc_attr_e('Edit', 'heritagepress'); ?>">âœï¸</a>
                <a href="#" class="button button-small delete-person" data-person-id="<?php echo esc_attr($row['personID']); ?>" title="<?php esc_attr_e('Delete', 'heritagepress'); ?>">ðŸ—‘ï¸</a>
                <a href="<?php echo esc_url(site_url('person.php?personID=' . urlencode($row['personID']))); ?>" class="button button-small" target="_blank" title="<?php esc_attr_e('View', 'heritagepress'); ?>">ðŸ‘ï¸</a>
              </td>
              <td><?php echo esc_html($row['personID']); ?></td>
              <td><?php echo esc_html($row['lastname']); ?></td>
              <td><?php echo esc_html($row['firstname']); ?></td>
              <td><?php echo esc_html($row['birthdate']); ?></td>
              <td><?php echo esc_html($row['deathdate']); ?></td>
              <td><?php echo $row['living'] ? __('Yes', 'heritagepress') : __('No', 'heritagepress'); ?></td>
              <td><?php echo $row['private'] ? __('Yes', 'heritagepress') : __('No', 'heritagepress'); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="8"><?php _e('No records found.', 'heritagepress'); ?></td>
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
          'living' => $living,
          'private' => $private,
          'exactmatch' => $exactmatch,
          'nokids' => $nokids,
          'noparents' => $noparents,
          'nospouse' => $nospouse
        ],
        'type' => 'array',
      ]);
      echo '<div class="tablenav"><div class="tablenav-pages">' . join(' ', $page_links) . '</div></div>';
    endif;
    ?>
  </div>
  <script>
    jQuery(document).ready(function($) {
      $('.delete-person').on('click', function(e) {
        e.preventDefault();
        if (confirm('<?php echo esc_js(__('Are you sure you want to delete this person?', 'heritagepress')); ?>')) {
          var personID = $(this).data('person-id');
          window.location = '<?php echo esc_url(admin_url('admin-post.php?action=heritagepress_delete_person&_wpnonce=' . wp_create_nonce('heritagepress_delete_person'))); ?>&personID=' + encodeURIComponent(personID);
        }
      });
    });
  </script>
<?php
}

// Handle delete action
add_action('admin_post_heritagepress_delete_person', 'heritagepress_handle_delete_person');
function heritagepress_handle_delete_person()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to delete people.', 'heritagepress'));
  }
  check_admin_referer('heritagepress_delete_person');
  global $wpdb;
  $personID = isset($_GET['personID']) ? sanitize_text_field($_GET['personID']) : '';
  if ($personID) {
    $people_table = $wpdb->prefix . 'HeritagePress_people';
    $wpdb->delete($people_table, ['personID' => $personID]);
    wp_redirect(admin_url('admin.php?page=heritagepress-people&message=' . urlencode(__('Person deleted.', 'heritagepress'))));
    exit;
  }
  wp_redirect(admin_url('admin.php?page=heritagepress-people&message=' . urlencode(__('No person ID specified.', 'heritagepress'))));
  exit;
}
