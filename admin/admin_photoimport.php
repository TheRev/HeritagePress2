<?php
// HeritagePress: Photo Import admin page, 
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', 'heritagepress_add_photoimport_page');
function heritagepress_add_photoimport_page()
{
  add_submenu_page(
    'heritagepress',
    __('Photo Import', 'heritagepress'),
    __('Photo Import', 'heritagepress'),
    'manage_options',
    'heritagepress-photoimport',
    'heritagepress_render_photoimport_page'
  );
}

function heritagepress_render_photoimport_page()
{
  global $wpdb;
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  $message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
  $nonce = wp_create_nonce('heritagepress_photoimport');

  // Fetch trees
  $trees_table = $wpdb->prefix . 'HeritagePress_trees';
  $trees = $wpdb->get_results("SELECT gedcom, treename FROM $trees_table ORDER BY treename", ARRAY_A);

  // Fetch media types
  $mediatypes_table = $wpdb->prefix . 'HeritagePress_mediatypes';
  $mediatypes = $wpdb->get_results("SELECT ID, display FROM $mediatypes_table ORDER BY display", ARRAY_A);
?>
  <div class="wrap">
    <h1><?php _e('Photo Import', 'heritagepress'); ?></h1>
    <?php if ($message): ?>
      <div class="notice notice-success is-dismissible">
        <p><?php echo esc_html($message); ?></p>
      </div>
    <?php endif; ?>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <input type="hidden" name="action" value="heritagepress_photoimport">
      <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
      <table class="form-table">
        <tr>
          <th><?php _e('Tree', 'heritagepress'); ?></th>
          <td>
            <select name="tree">
              <option value=""><?php _e('All Trees', 'heritagepress'); ?></option>
              <?php foreach ($trees as $row): ?>
                <option value="<?php echo esc_attr($row['gedcom']); ?>"><?php echo esc_html($row['treename']); ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <tr>
          <th><?php _e('Media Type', 'heritagepress'); ?></th>
          <td>
            <select name="mediatypeID">
              <?php foreach ($mediatypes as $type): ?>
                <option value="<?php echo esc_attr($type['ID']); ?>"><?php echo esc_html($type['display']); ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <tr>
          <th><?php _e('Import Options', 'heritagepress'); ?></th>
          <td>
            <label><input type="checkbox" name="overwrite" value="1"> <?php _e('Overwrite existing files', 'heritagepress'); ?></label><br>
            <label><input type="checkbox" name="skipduplicates" value="1"> <?php _e('Skip duplicates', 'heritagepress'); ?></label>
          </td>
        </tr>
      </table>
      <p class="submit"><input type="submit" class="button-primary" value="<?php esc_attr_e('Import Photos', 'heritagepress'); ?>"></p>
    </form>
  </div>
<?php
}

// Handle import action (full logic)
add_action('admin_post_heritagepress_photoimport', 'heritagepress_handle_photoimport');
function heritagepress_handle_photoimport()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to import photos.', 'heritagepress'));
  }
  check_admin_referer('heritagepress_photoimport');
  global $wpdb;
  $tree = isset($_POST['tree']) ? sanitize_text_field($_POST['tree']) : '';
  $mediatypeID = isset($_POST['mediatypeID']) ? sanitize_text_field($_POST['mediatypeID']) : '';
  $overwrite = isset($_POST['overwrite']) ? true : false;
  $skipduplicates = isset($_POST['skipduplicates']) ? true : false;
  $totalImadapted = 0;
  $media_table = $wpdb->prefix . 'HeritagePress_media';

  // Set up media directory (customize as needed)
  $upload_dir = wp_upload_dir();
  $media_dir = $upload_dir['basedir'] . '/heritagepress_media';
  if (!is_dir($media_dir)) {
    wp_mkdir_p($media_dir);
  }

  // Scan for files (non-recursive for now)
  $files = scandir($media_dir);
  foreach ($files as $filename) {
    if ($filename === '.' || $filename === '..') continue;
    $filepath = $media_dir . '/' . $filename;
    if (is_file($filepath)) {
      $fileparts = pathinfo($filename);
      $form = strtoupper($fileparts['extension']);
      $now = current_time('mysql');
      $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $media_table WHERE path = %s AND mediatypeID = %s AND gedcom = %s", $filename, $mediatypeID, $tree));
      if ($exists && $skipduplicates) continue;
      if ($exists && !$overwrite) continue;
      if ($exists && $overwrite) {
        $wpdb->delete($media_table, ['path' => $filename, 'mediatypeID' => $mediatypeID, 'gedcom' => $tree]);
      }
      $wpdb->insert($media_table, [
        'mediatypeID' => $mediatypeID,
        'mediakey' => $filename,
        'gedcom' => $tree,
        'path' => $filename,
        'thumbpath' => '',
        'description' => '',
        'notes' => '',
        'width' => '',
        'height' => '',
        'datetaken' => '',
        'placetaken' => '',
        'owner' => '',
        'changedate' => $now,
        'form' => $form,
        'alwayson' => 0,
        'map' => '',
        'abspath' => '',
        'status' => '',
        'cemeteryID' => '',
        'showmap' => 0,
        'linktocem' => 0,
        'latitude' => '',
        'longitude' => '',
        'zoom' => 0,
        'bodytext' => '',
        'usenl' => 0,
        'newwindow' => 0,
        'usecollfolder' => 1
      ]);
      $totalImadapted++;
    }
  }
  $msg = sprintf(_n('%d photo imadapted.', '%d photos imadapted.', $totalImadapted, 'heritagepress'), $totalImadapted);
  wp_redirect(admin_url('admin.php?page=heritagepress-photoimport&message=' . urlencode($msg)));
  exit;
}
