<?php
// HeritagePress: Ported from TNG admin_neweventtype.php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', 'heritagepress_add_neweventtype_page');
function heritagepress_add_neweventtype_page()
{
  add_submenu_page(
    'heritagepress',
    __('Add New Event Type', 'heritagepress'),
    __('Add New Event Type', 'heritagepress'),
    'manage_options',
    'heritagepress-neweventtype',
    'heritagepress_render_neweventtype_page'
  );
}

function heritagepress_render_neweventtype_page()
{
  global $wpdb;
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  $nonce = wp_create_nonce('heritagepress_neweventtype');
  // Fetch languages for display fields
  $languages_table = $wpdb->prefix . 'tng_languages';
  $languages = $wpdb->get_results("SELECT languageID, display, folder FROM $languages_table ORDER BY display", ARRAY_A);
?>
  <div class="wrap">
    <h1><?php _e('Add New Event Type', 'heritagepress'); ?></h1>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" name="form1" id="form1" onsubmit="return heritagepress_validate_neweventtype_form();">
      <input type="hidden" name="action" value="heritagepress_add_neweventtype">
      <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
      <table class="form-table">
        <tr>
          <th><label for="type"><?php _e('Associate With', 'heritagepress'); ?></label></th>
          <td>
            <select name="type" id="type">
              <option value="I"><?php _e('Individual', 'heritagepress'); ?></option>
              <option value="F"><?php _e('Family', 'heritagepress'); ?></option>
              <option value="S"><?php _e('Source', 'heritagepress'); ?></option>
              <option value="R"><?php _e('Repository', 'heritagepress'); ?></option>
            </select>
          </td>
        </tr>
        <tr>
          <th><label for="tag1"><?php _e('Select Tag', 'heritagepress'); ?></label></th>
          <td><select name="tag1" id="tag1"></select></td>
        </tr>
        <tr>
          <th><?php _e('Or Enter', 'heritagepress'); ?>:</th>
          <td><input type="text" name="tag2" id="tag2" size="10"> (<?php _e('If both data', 'heritagepress'); ?>)</td>
        </tr>
        <tr id="tdesc">
          <th><label for="description"><?php _e('Type Description', 'heritagepress'); ?>*</label></th>
          <td><input type="text" name="description" id="description" size="40"></td>
        </tr>
        <tr id="displaytr">
          <th><label for="defdisplay"><?php _e('Display', 'heritagepress'); ?></label></th>
          <td><input type="text" name="defdisplay" id="defdisplay" size="40"></td>
        </tr>
        <?php if (!empty($languages)) : ?>
          <tr>
            <td colspan="2">
              <hr><b><?php _e('Other Languages', 'heritagepress'); ?></b><br>
            </td>
          </tr>
          <?php foreach ($languages as $langrow): ?>
            <tr>
              <th><?php echo esc_html($langrow['display']); ?></th>
              <td><input type="text" name="display<?php echo esc_attr($langrow['languageID']); ?>" size="40"></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        <tr>
          <th><label for="ordernum"><?php _e('Display Order', 'heritagepress'); ?></label></th>
          <td><input type="text" name="ordernum" id="ordernum" size="4" value="0"></td>
        </tr>
        <tr>
          <th><?php _e('Event Data', 'heritagepress'); ?></th>
          <td><label><input type="radio" name="keep" value="1" checked> <?php _e('Accept', 'heritagepress'); ?></label> <label><input type="radio" name="keep" value="0"> <?php _e('Ignore', 'heritagepress'); ?></label></td>
        </tr>
        <tr>
          <th><?php _e('Collapse Event', 'heritagepress'); ?></th>
          <td><label><input type="radio" name="collapse" value="1"> <?php _e('Yes', 'heritagepress'); ?></label> <label><input type="radio" name="collapse" value="0" checked> <?php _e('No', 'heritagepress'); ?></label></td>
        </tr>
        <tr>
          <th><?php _e('LDS Event', 'heritagepress'); ?></th>
          <td><label><input type="radio" name="ldsevent" value="1"> <?php _e('Yes', 'heritagepress'); ?></label> <label><input type="radio" name="ldsevent" value="0" checked> <?php _e('No', 'heritagepress'); ?></label></td>
        </tr>
      </table>
      <p class="submit">
        <input type="submit" class="button-primary" value="<?php esc_attr_e('Save and Return', 'heritagepress'); ?>">
        <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-eventtypes')); ?>" class="button"> <?php _e('Cancel', 'heritagepress'); ?> </a>
      </p>
    </form>
    <p class="description" id="typereq" style="display:none">*<?php _e('Type description required.', 'heritagepress'); ?></p>
  </div>
  <script type="text/javascript">
    // TODO: Populate tag1 options and handle JS validation as in original
    function heritagepress_validate_neweventtype_form() {
      var tag1 = document.getElementById('tag1').value;
      var tag2 = document.getElementById('tag2').value;
      var description = document.getElementById('description').value;
      var defdisplay = document.getElementById('defdisplay').value;
      if (!tag1 && !tag2) {
        alert('<?php _e('Please select or enter a tag.', 'heritagepress'); ?>');
        return false;
      }
      if ((tag2 === 'EVEN' || (!tag2 && tag1 === 'EVEN')) && !description) {
        alert('<?php _e('Please enter a type description.', 'heritagepress'); ?>');
        return false;
      }
      if (!defdisplay) {
        alert('<?php _e('Please enter a display value.', 'heritagepress'); ?>');
        return false;
      }
      return true;
    }
  </script>
<?php
}

add_action('admin_post_heritagepress_add_neweventtype', 'heritagepress_handle_add_neweventtype');
function heritagepress_handle_add_neweventtype()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  check_admin_referer('heritagepress_neweventtype');
  // TODO: Sanitize and save the event type data to the database
  wp_redirect(admin_url('admin.php?page=heritagepress-eventtypes&message=added'));
  exit;
}
