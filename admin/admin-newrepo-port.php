<?php
// HeritagePress: Ported from TNG admin_newrepo.php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', 'heritagepress_add_newrepo_page');
function heritagepress_add_newrepo_page()
{
  add_submenu_page(
    'heritagepress',
    __('Add New Repository', 'heritagepress'),
    __('Add New Repository', 'heritagepress'),
    'manage_options',
    'heritagepress-newrepo',
    'heritagepress_render_newrepo_page'
  );
}

function heritagepress_render_newrepo_page()
{
  global $wpdb;
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  $nonce = wp_create_nonce('heritagepress_newrepo');
  // Fetch trees
  $trees_table = $wpdb->prefix . 'tng_trees';
  $trees = $wpdb->get_results("SELECT gedcom, treename FROM $trees_table ORDER BY treename", ARRAY_A);
?>
  <div class="wrap">
    <h1><?php _e('Add New Repository', 'heritagepress'); ?></h1>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" name="form1" id="form1" onsubmit="return heritagepress_validate_newrepo_form();">
      <input type="hidden" name="action" value="heritagepress_add_newrepo">
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
          <th><label for="repoID"><?php _e('Repository ID', 'heritagepress'); ?></label></th>
          <td><input type="text" name="repoID" id="repoID" size="10" onblur="this.value=this.value.toUpperCase()"></td>
        </tr>
        <tr>
          <th><label for="reponame"><?php _e('Name', 'heritagepress'); ?></label></th>
          <td><input type="text" name="reponame" id="reponame" size="40"></td>
        </tr>
        <tr>
          <th><label for="address1"><?php _e('Address 1', 'heritagepress'); ?></label></th>
          <td><input type="text" name="address1" id="address1" size="50"></td>
        </tr>
        <tr>
          <th><label for="address2"><?php _e('Address 2', 'heritagepress'); ?></label></th>
          <td><input type="text" name="address2" id="address2" size="50"></td>
        </tr>
        <tr>
          <th><label for="city"><?php _e('City', 'heritagepress'); ?></label></th>
          <td><input type="text" name="city" id="city" size="50"></td>
        </tr>
        <tr>
          <th><label for="state"><?php _e('State/Province', 'heritagepress'); ?></label></th>
          <td><input type="text" name="state" id="state" size="50"></td>
        </tr>
        <tr>
          <th><label for="zip"><?php _e('ZIP', 'heritagepress'); ?></label></th>
          <td><input type="text" name="zip" id="zip" size="20"></td>
        </tr>
        <tr>
          <th><label for="country"><?php _e('Country', 'heritagepress'); ?></label></th>
          <td><input type="text" name="country" id="country" size="50"></td>
        </tr>
        <tr>
          <th><label for="phone"><?php _e('Phone', 'heritagepress'); ?></label></th>
          <td><input type="text" name="phone" id="phone" size="30"></td>
        </tr>
        <tr>
          <th><label for="email"><?php _e('Email', 'heritagepress'); ?></label></th>
          <td><input type="text" name="email" id="email" size="50"></td>
        </tr>
        <tr>
          <th><label for="www"><?php _e('Website', 'heritagepress'); ?></label></th>
          <td><input type="text" name="www" id="www" size="50"></td>
        </tr>
      </table>
      <p class="submit">
        <input type="submit" class="button-primary" value="<?php esc_attr_e('Save and Continue', 'heritagepress'); ?>">
        <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-repositories')); ?>" class="button"> <?php _e('Cancel', 'heritagepress'); ?> </a>
      </p>
    </form>
  </div>
  <script type="text/javascript">
    function heritagepress_validate_newrepo_form() {
      var repoID = document.getElementById('repoID').value.trim();
      var reponame = document.getElementById('reponame').value.trim();
      if (!repoID) {
        alert('<?php _e('Please enter a repository ID.', 'heritagepress'); ?>');
        return false;
      }
      if (!reponame) {
        alert('<?php _e('Please enter a repository name.', 'heritagepress'); ?>');
        return false;
      }
      return true;
    }
  </script>
<?php
}

add_action('admin_post_heritagepress_add_newrepo', 'heritagepress_handle_add_newrepo');
function heritagepress_handle_add_newrepo()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  check_admin_referer('heritagepress_newrepo');
  // TODO: Sanitize and save the repository data to the database
  wp_redirect(admin_url('admin.php?page=heritagepress-repositories&message=added'));
  exit;
}
