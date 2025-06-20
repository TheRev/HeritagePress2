<?php
// HeritagePress: 
if (!defined('ABSPATH')) exit; // Exit if accessed directly

// This file is intended for use as a modal or AJAX-loaded form in the admin area.
// Usage: include or load via AJAX in a WordPress admin context.

function heritagepress_render_newperson2_modal($tree = '', $familyID = '', $type = '', $needped = '')
{
  global $wpdb;
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  $nonce = wp_create_nonce('heritagepress_newperson2');
  // Fetch tree name
  $trees_table = $wpdb->prefix . 'HeritagePress_trees';
  $branches_table = $wpdb->prefix . 'HeritagePress_branches';
  $people_table = $wpdb->prefix . 'HeritagePress_people';
  $treerow = $wpdb->get_row($wpdb->prepare("SELECT treename FROM $trees_table WHERE gedcom = %s", $tree), ARRAY_A);
  $branches = $wpdb->get_results($wpdb->prepare("SELECT branch, description FROM $branches_table WHERE gedcom = %s ORDER BY description", $tree), ARRAY_A);
?>
  <div class="wrap">
    <h2><?php _e('Add New Person', 'heritagepress'); ?></h2>
    <form method="post" name="npform" id="npform" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <input type="hidden" name="action" value="heritagepress_add_newperson2">
      <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
      <input type="hidden" name="tree" value="<?php echo esc_attr($tree); ?>">
      <input type="hidden" name="familyID" value="<?php echo esc_attr($familyID); ?>">
      <input type="hidden" name="type" value="<?php echo esc_attr($type); ?>">
      <input type="hidden" name="needped" value="<?php echo esc_attr($needped); ?>">
      <table class="form-table">
        <tr>
          <th><label for="personID"><?php _e('Person ID', 'heritagepress'); ?></label></th>
          <td><input type="text" name="personID" id="personID" size="10" onblur="this.value=this.value.toUpperCase()"></td>
        </tr>
        <tr>
          <th><label for="firstname"><?php _e('First/Given Names', 'heritagepress'); ?></label></th>
          <td><input type="text" name="firstname" id="firstname" size="30"></td>
        </tr>
        <tr>
          <th><label for="lastname"><?php _e('Last/Surname', 'heritagepress'); ?></label></th>
          <td><input type="text" name="lastname" id="lastname" size="30"></td>
        </tr>
        <tr>
          <th><label for="sex"><?php _e('Sex', 'heritagepress'); ?></label></th>
          <td>
            <select name="sex" id="sex" onchange="heritagepress_on_gender_change(this);">
              <option value="U"><?php _e('Unknown', 'heritagepress'); ?></option>
              <option value="M"><?php _e('Male', 'heritagepress'); ?></option>
              <option value="F"><?php _e('Female', 'heritagepress'); ?></option>
              <option value="O"><?php _e('Other', 'heritagepress'); ?></option>
            </select>
            <input type="text" name="other_gender" id="other_gender" style="display:none;" />
          </td>
        </tr>
        <tr>
          <th><label for="living"><?php _e('Living', 'heritagepress'); ?></label></th>
          <td><input type="checkbox" name="living" id="living" value="1" checked></td>
        </tr>
        <tr>
          <th><label for="private"><?php _e('Private', 'heritagepress'); ?></label></th>
          <td><input type="checkbox" name="private" id="private" value="1"></td>
        </tr>
        <tr>
          <th><label for="branch2"><?php _e('Branch', 'heritagepress'); ?></label></th>
          <td>
            <select name="branch[]" id="branch2" multiple size="4">
              <option value=""><?php _e('No Branch', 'heritagepress'); ?></option>
              <?php foreach ($branches as $branch): ?>
                <option value="<?php echo esc_attr($branch['branch']); ?>"><?php echo esc_html($branch['description']); ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
      </table>
      <!-- TODO: Port event fields and relationship selectors as needed -->
      <p class="submit">
        <input type="submit" class="button-primary" value="<?php esc_attr_e('Save', 'heritagepress'); ?>">
      </p>
    </form>
  </div>
  <script type="text/javascript">
    function heritagepress_on_gender_change(gender) {
      if (gender.value == 'M' || gender.value == 'F' || gender.value == 'U') {
        document.getElementById('other_gender').style.display = 'none';
      } else {
        document.getElementById('other_gender').style.display = '';
      }
    }
  </script>
<?php
}

add_action('admin_post_heritagepress_add_newperson2', 'heritagepress_handle_add_newperson2');
function heritagepress_handle_add_newperson2()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  check_admin_referer('heritagepress_newperson2');
  // TODO: Sanitize and save the person data to the database
  wp_redirect(admin_url('admin.php?page=heritagepress-people&message=added'));
  exit;
}
