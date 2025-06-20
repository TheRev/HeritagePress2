<?php
// HeritagePress: Ported from TNG admin_newperson.php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', 'heritagepress_add_newperson_page');
function heritagepress_add_newperson_page()
{
  add_submenu_page(
    'heritagepress',
    __('Add New Person', 'heritagepress'),
    __('Add New Person', 'heritagepress'),
    'manage_options',
    'heritagepress-newperson',
    'heritagepress_render_newperson_page'
  );
}

function heritagepress_render_newperson_page()
{
  global $wpdb;
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  $nonce = wp_create_nonce('heritagepress_newperson');
  // Fetch trees and branches
  $trees_table = $wpdb->prefix . 'tng_trees';
  $branches_table = $wpdb->prefix . 'tng_branches';
  $trees = $wpdb->get_results("SELECT gedcom, treename FROM $trees_table ORDER BY treename", ARRAY_A);
  $branches = $wpdb->get_results("SELECT branch, description FROM $branches_table ORDER BY description", ARRAY_A);
?>
  <div class="wrap">
    <h1><?php _e('Add New Person', 'heritagepress'); ?></h1>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" name="form1" id="form1" onsubmit="return heritagepress_validate_newperson_form();">
      <input type="hidden" name="action" value="heritagepress_add_newperson">
      <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
      <table class="form-table">
        <tr>
          <th><label for="tree1"><?php _e('Tree', 'heritagepress'); ?></label></th>
          <td>
            <select name="tree1" id="tree1">
              <?php foreach ($trees as $row): ?>
                <option value="<?php echo esc_attr($row['gedcom']); ?>"><?php echo esc_html($row['treename']); ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <tr>
          <th><label for="branch"><?php _e('Branch', 'heritagepress'); ?></label></th>
          <td>
            <select name="branch" id="branch">
              <option value=""><?php _e('No Branch', 'heritagepress'); ?></option>
              <?php foreach ($branches as $branch): ?>
                <option value="<?php echo esc_attr($branch['branch']); ?>"><?php echo esc_html($branch['description']); ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
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
      </table>
      <!-- TODO: Port additional name fields, events, and other person fields as needed -->
      <p class="submit">
        <input type="submit" class="button-primary" value="<?php esc_attr_e('Save and Continue', 'heritagepress'); ?>">
        <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-people')); ?>" class="button"> <?php _e('Cancel', 'heritagepress'); ?> </a>
      </p>
    </form>
  </div>
  <script type="text/javascript">
    function heritagepress_validate_newperson_form() {
      var personID = document.getElementById('personID').value.trim();
      if (!personID) {
        alert('<?php _e('Please enter a person ID.', 'heritagepress'); ?>');
        return false;
      }
      return true;
    }

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

add_action('admin_post_heritagepress_add_newperson', 'heritagepress_handle_add_newperson');
function heritagepress_handle_add_newperson()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  check_admin_referer('heritagepress_newperson');
  // TODO: Sanitize and save the person data to the database
  wp_redirect(admin_url('admin.php?page=heritagepress-people&message=added'));
  exit;
}
