<?php
// HeritagePress: Ported from TNG admin_newplace.php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', 'heritagepress_add_newplace_page');
function heritagepress_add_newplace_page()
{
  add_submenu_page(
    'heritagepress',
    __('Add New Place', 'heritagepress'),
    __('Add New Place', 'heritagepress'),
    'manage_options',
    'heritagepress-newplace',
    'heritagepress_render_newplace_page'
  );
}

function heritagepress_render_newplace_page()
{
  global $wpdb;
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  $nonce = wp_create_nonce('heritagepress_newplace');
  // Fetch trees if needed
  $trees_table = $wpdb->prefix . 'tng_trees';
  $trees = $wpdb->get_results("SELECT gedcom, treename FROM $trees_table ORDER BY treename", ARRAY_A);
?>
  <div class="wrap">
    <h1><?php _e('Add New Place', 'heritagepress'); ?></h1>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" name="form1" id="form1" onsubmit="return heritagepress_validate_newplace_form();">
      <input type="hidden" name="action" value="heritagepress_add_newplace">
      <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
      <table class="form-table">
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
        <tr>
          <th><label for="place"><?php _e('Place', 'heritagepress'); ?></label></th>
          <td><input type="text" name="place" id="place" size="70"></td>
        </tr>
        <tr>
          <th><label for="latitude"><?php _e('Latitude', 'heritagepress'); ?></label></th>
          <td><input type="text" name="latitude" id="latitude" size="20"></td>
        </tr>
        <tr>
          <th><label for="longitude"><?php _e('Longitude', 'heritagepress'); ?></label></th>
          <td><input type="text" name="longitude" id="longitude" size="20"></td>
        </tr>
        <tr>
          <th><label for="zoom"><?php _e('Zoom', 'heritagepress'); ?></label></th>
          <td><input type="text" name="zoom" id="zoom" size="20"></td>
        </tr>
        <tr>
          <th><label for="placelevel"><?php _e('Place Level', 'heritagepress'); ?></label></th>
          <td>
            <select name="placelevel" id="placelevel">
              <option value=""></option>
              <option value="-1"><?php _e('Do not geocode', 'heritagepress'); ?></option>
              <option value="1">1</option>
              <option value="2">2</option>
              <option value="3">3</option>
              <option value="4">4</option>
              <option value="5">5</option>
              <option value="6">6</option>
            </select>
          </td>
        </tr>
        <tr>
          <th><label for="notes"><?php _e('Notes', 'heritagepress'); ?></label></th>
          <td><textarea name="notes" id="notes" cols="90" rows="5"></textarea></td>
        </tr>
        <!-- TODO: Integrate map functionality if needed -->
      </table>
      <p class="submit">
        <input type="submit" class="button-primary" value="<?php esc_attr_e('Save and Return', 'heritagepress'); ?>">
        <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-places')); ?>" class="button"> <?php _e('Cancel', 'heritagepress'); ?> </a>
      </p>
    </form>
  </div>
  <script type="text/javascript">
    function heritagepress_validate_newplace_form() {
      var place = document.getElementById('place').value.trim();
      if (!place) {
        alert('<?php _e('Please enter a place.', 'heritagepress'); ?>');
        return false;
      }
      return true;
    }
  </script>
<?php
}

add_action('admin_post_heritagepress_add_newplace', 'heritagepress_handle_add_newplace');
function heritagepress_handle_add_newplace()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  check_admin_referer('heritagepress_newplace');
  // TODO: Sanitize and save the place data to the database
  wp_redirect(admin_url('admin.php?page=heritagepress-places&message=added'));
  exit;
}
