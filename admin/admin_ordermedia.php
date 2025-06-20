<?php
// HeritagePress: 
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', 'heritagepress_add_ordermedia_page');
function heritagepress_add_ordermedia_page()
{
  add_submenu_page(
    'heritagepress',
    __('Order Media', 'heritagepress'),
    __('Order Media', 'heritagepress'),
    'manage_options',
    'heritagepress-ordermedia',
    'heritagepress_render_ordermedia_page'
  );
}

function heritagepress_render_ordermedia_page()
{
  global $wpdb;
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  $message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
  $tree = isset($_GET['tree1']) ? sanitize_text_field($_GET['tree1']) : '';
  $linktype = isset($_GET['linktype1']) ? sanitize_text_field($_GET['linktype1']) : '';
  $mediatypeID = isset($_GET['mediatypeID']) ? sanitize_text_field($_GET['mediatypeID']) : '';
  $personID = isset($_GET['newlink1']) ? sanitize_text_field($_GET['newlink1']) : '';
  $eventID = isset($_GET['event1']) ? sanitize_text_field($_GET['event1']) : '';
  $nonce = wp_create_nonce('heritagepress_ordermedia');

  // Handle reorder POST
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ordermedia_nonce']) && wp_verify_nonce($_POST['ordermedia_nonce'], 'heritagepress_ordermedia')) {
    $order = isset($_POST['order']) ? $_POST['order'] : array();
    foreach ($order as $ordernum => $medialinkID) {
      $wpdb->update(
        $wpdb->prefix . 'HeritagePress_medialinks',
        array('ordernum' => intval($ordernum) + 1),
        array('medialinkID' => intval($medialinkID)),
        array('%d'),
        array('%d')
      );
    }
    $redirect_url = add_query_arg(array('message' => __('Order updated.', 'heritagepress'), 'tree1' => $tree, 'linktype1' => $linktype, 'mediatypeID' => $mediatypeID, 'newlink1' => $personID, 'event1' => $eventID), menu_page_url('heritagepress-ordermedia', false));
    wp_redirect($redirect_url);
    exit;
  }

  // Fetch media links for the selected entity
  $medialinks_table = $wpdb->prefix . 'HeritagePress_medialinks';
  $media_table = $wpdb->prefix . 'HeritagePress_media';
  $where = $wpdb->prepare("$medialinks_table.personID = %s AND $medialinks_table.gedcom = %s AND $media_table.mediaID = $medialinks_table.mediaID AND $medialinks_table.eventID = %s AND $media_table.mediatypeID = %s", $personID, $tree, $eventID, $mediatypeID);
  $query = "SELECT $media_table.*, $medialinks_table.medialinkID, $medialinks_table.ordernum, $medialinks_table.defphoto, $medialinks_table.dontshow FROM $medialinks_table, $media_table WHERE $where ORDER BY $medialinks_table.ordernum ASC";
  $media_items = $wpdb->get_results($query, ARRAY_A);

?>
  <div class="wrap">
    <h1><?php _e('Order Media', 'heritagepress'); ?></h1>
    <?php if ($message): ?>
      <div class="notice notice-success is-dismissible">
        <p><?php echo esc_html($message); ?></p>
      </div>
    <?php endif; ?>
    <?php if (!$personID || !$tree || !$mediatypeID): ?>
      <div class="notice notice-warning">
        <p><?php _e('Please select a Tree, Link Type, Media Type, and ID using the form.', 'heritagepress'); ?></p>
      </div>
    <?php else: ?>
      <?php if (empty($media_items)): ?>
        <div class="notice notice-warning">
          <p><?php _e('No media items found for this entity.', 'heritagepress'); ?></p>
        </div>
      <?php else: ?>
        <form method="post">
          <input type="hidden" name="ordermedia_nonce" value="<?php echo esc_attr($nonce); ?>">
          <table class="wp-list-table widefat fixed striped">
            <thead>
              <tr>
                <th><?php _e('Order', 'heritagepress'); ?></th>
                <th><?php _e('Thumbnail', 'heritagepress'); ?></th>
                <th><?php _e('Description', 'heritagepress'); ?></th>
                <th><?php _e('Show', 'heritagepress'); ?></th>
                <th><?php _e('Date Taken', 'heritagepress'); ?></th>
              </tr>
            </thead>
            <tbody id="media-sortable">
              <?php foreach ($media_items as $i => $item): ?>
                <tr>
                  <td>
                    <input type="hidden" name="order[]" value="<?php echo esc_attr($item['medialinkID']); ?>">
                    <?php echo esc_html($i + 1); ?>
                  </td>
                  <td>
                    <?php if (!empty($item['thumbpath'])): ?>
                      <img src="<?php echo esc_url(heritagepress_get_media_thumb_url($item)); ?>" alt="" style="max-width:60px;max-height:60px;" />
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php echo esc_html($item['description']); ?>
                  </td>
                  <td>
                    <input type="checkbox" disabled <?php checked(!$item['dontshow']); ?> />
                  </td>
                  <td>
                    <?php echo esc_html($item['datetaken']); ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <p>
            <input type="submit" class="button button-primary" value="<?php esc_attr_e('Save Order', 'heritagepress'); ?>">
          </p>
        </form>
        <script>
          // TODO: Implement drag-and-drop reordering with jQuery UI Sortable if desired
        </script>
      <?php endif; ?>
    <?php endif; ?>
  </div>
<?php
}

// Helper: Get media thumbnail URL (customize as needed)
function heritagepress_get_media_thumb_url($item)
{
  // Adjust path logic as needed for your setup
  $upload_dir = wp_upload_dir();
  $baseurl = $upload_dir['baseurl'];
  if (!empty($item['thumbpath'])) {
    return $baseurl . '/heritagepress_media/' . ltrim($item['thumbpath'], '/');
  }
  return '';
}
