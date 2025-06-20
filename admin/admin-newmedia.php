<?php
// HeritagePress: 
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', 'heritagepress_add_newmedia_page');
function heritagepress_add_newmedia_page()
{
  add_submenu_page(
    'heritagepress',
    __('Add New Media', 'heritagepress'),
    __('Add New Media', 'heritagepress'),
    'manage_options',
    'heritagepress-newmedia',
    'heritagepress_render_newmedia_page'
  );
}

function heritagepress_render_newmedia_page()
{
  global $wpdb;
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  $nonce = wp_create_nonce('heritagepress_newmedia');
  // Fetch trees
  $trees_table = $wpdb->prefix . 'HeritagePress_trees';
  $trees = $wpdb->get_results("SELECT gedcom, treename FROM $trees_table ORDER BY treename", ARRAY_A);
  // TODO: Fetch media types and cemetery options as needed
  $media_types = array(); // Populate as needed
  $cemeteries = array(); // Populate as needed
?>
  <div class="wrap">
    <h1><?php _e('Add New Media', 'heritagepress'); ?></h1>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data" name="form1" id="form1" onsubmit="return heritagepress_validate_newmedia_form();">
      <input type="hidden" name="action" value="heritagepress_add_newmedia">
      <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
      <table class="form-table">
        <tr>
          <th><label for="mediatypeID"><?php _e('Media Type', 'heritagepress'); ?></label></th>
          <td>
            <select name="mediatypeID" id="mediatypeID">
              <option value="photo">Photo</option>
              <option value="document">Document</option>
              <option value="headstone">Headstone</option>
              <option value="histories">History</option>
              <option value="videos">Video</option>
              <option value="recordings">Audio</option>
            </select>
          </td>
        </tr>
        <tr>
          <th><label for="mediafile"><?php _e('Media File', 'heritagepress'); ?></label></th>
          <td><input type="file" name="mediafile" id="mediafile"></td>
        </tr>
        <tr>
          <th><label for="description"><?php _e('Title/Description', 'heritagepress'); ?></label></th>
          <td><input type="text" name="description" id="description" size="70"></td>
        </tr>
        <tr>
          <th><label for="notes"><?php _e('Notes', 'heritagepress'); ?></label></th>
          <td><textarea name="notes" id="notes" rows="5" cols="70"></textarea></td>
        </tr>
        <tr>
          <th><label for="tree"><?php _e('Tree', 'heritagepress'); ?></label></th>
          <td>
            <select name="tree" id="tree">
              <option value=""><?php _e('All Trees', 'heritagepress'); ?></option>
              <?php foreach ($trees as $row): ?>
                <option value="<?php echo esc_attr($row['gedcom']); ?>"><?php echo esc_html($row['treename']); ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <!-- TODO: Add more fields as needed (cemetery, plot, status, etc.) -->
      </table>
      <p class="submit">
        <input type="submit" class="button-primary" value="<?php esc_attr_e('Save and Continue', 'heritagepress'); ?>">
        <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-media')); ?>" class="button"> <?php _e('Cancel', 'heritagepress'); ?> </a>
      </p>
    </form>
  </div>
  <script type="text/javascript">
    function heritagepress_validate_newmedia_form() {
      var description = document.getElementById('description').value.trim();
      if (!description) {
        alert('<?php _e('Please enter a title/description.', 'heritagepress'); ?>');
        return false;
      }
      return true;
    }
  </script>
<?php
}

add_action('admin_post_heritagepress_add_newmedia', 'heritagepress_handle_add_newmedia');
function heritagepress_handle_add_newmedia()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  check_admin_referer('heritagepress_newmedia');
  // TODO: Handle file upload and save media data to the database
  wp_redirect(admin_url('admin.php?page=heritagepress-media&message=added'));
  exit;
}
