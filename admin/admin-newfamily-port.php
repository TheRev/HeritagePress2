<?php
// HeritagePress: Ported from TNG admin_newfamily.php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', 'heritagepress_add_newfamily_page');
function heritagepress_add_newfamily_page()
{
  add_submenu_page(
    'heritagepress',
    __('Add New Family', 'heritagepress'),
    __('Add New Family', 'heritagepress'),
    'manage_options',
    'heritagepress-newfamily',
    'heritagepress_render_newfamily_page'
  );
}

function heritagepress_render_newfamily_page()
{
  global $wpdb;
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  $nonce = wp_create_nonce('heritagepress_newfamily');
  // Fetch trees
  $trees_table = $wpdb->prefix . 'tng_trees';
  $branches_table = $wpdb->prefix . 'tng_branches';
  $assignedtree = '';
  $assignedbranch = '';
  $wherestr = '';
  $firsttree = '';
  if ($assignedtree) {
    $wherestr = $wpdb->prepare('WHERE gedcom = %s', $assignedtree);
    $firsttree = $assignedtree;
  } else {
    $firsttree = isset($_COOKIE['tng_tree']) ? sanitize_text_field($_COOKIE['tng_tree']) : '';
  }
  $trees = $wpdb->get_results("SELECT gedcom, treename FROM $trees_table $wherestr ORDER BY treename", ARRAY_A);
  $branches = $wpdb->get_results($wpdb->prepare("SELECT branch, description FROM $branches_table WHERE gedcom = %s ORDER BY description", $firsttree), ARRAY_A);
?>
  <div class="wrap">
    <h1><?php _e('Add New Family', 'heritagepress'); ?></h1>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" name="form1" id="form1" onsubmit="return heritagepress_validate_newfamily_form();">
      <input type="hidden" name="action" value="heritagepress_add_newfamily">
      <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
      <table class="form-table">
        <tr>
          <th><label for="tree1"><?php _e('Tree', 'heritagepress'); ?></label></th>
          <td>
            <select name="tree1" id="tree1">
              <?php foreach ($trees as $row): ?>
                <option value="<?php echo esc_attr($row['gedcom']); ?>" <?php selected($firsttree, $row['gedcom']); ?>><?php echo esc_html($row['treename']); ?></option>
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
          <th><label for="familyID"><?php _e('Family ID', 'heritagepress'); ?></label></th>
          <td>
            <input type="text" name="familyID" id="familyID" size="10" onblur="this.value=this.value.toUpperCase()">
            <!-- TODO: Add generate/check ID buttons with JS if needed -->
          </td>
        </tr>
      </table>
      <!-- TODO: Port spouse, events, and other family fields as needed -->
      <p class="submit">
        <input type="submit" class="button-primary" name="save" value="<?php esc_attr_e('Save and Continue', 'heritagepress'); ?>">
        <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-families')); ?>" class="button"> <?php _e('Cancel', 'heritagepress'); ?> </a>
      </p>
    </form>
  </div>
  <script type="text/javascript">
    function heritagepress_validate_newfamily_form() {
      var familyID = document.getElementById('familyID').value.trim();
      if (!familyID) {
        alert('<?php _e('Please enter a family ID.', 'heritagepress'); ?>');
        return false;
      }
      return true;
    }
  </script>
<?php
}

add_action('admin_post_heritagepress_add_newfamily', 'heritagepress_handle_add_newfamily');
function heritagepress_handle_add_newfamily()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  check_admin_referer('heritagepress_newfamily');
  // TODO: Sanitize and save the family data to the database
  wp_redirect(admin_url('admin.php?page=heritagepress-families&message=added'));
  exit;
}
