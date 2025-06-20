<?php
// HeritagePress: Ported from TNG admin_newsource.php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', 'heritagepress_add_newsource_page');
function heritagepress_add_newsource_page()
{
  add_submenu_page(
    'heritagepress',
    __('Add New Source', 'heritagepress'),
    __('Add New Source', 'heritagepress'),
    'manage_options',
    'heritagepress-newsource',
    'heritagepress_render_newsource_page'
  );
}

function heritagepress_render_newsource_page()
{
  global $wpdb;
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  $nonce = wp_create_nonce('heritagepress_newsource');
  // Fetch trees
  $trees_table = $wpdb->prefix . 'tng_trees';
  $assignedtree = '';
  $wherestr = '';
  $firsttree = '';
  if ($assignedtree) {
    $wherestr = $wpdb->prepare('WHERE gedcom = %s', $assignedtree);
    $firsttree = $assignedtree;
  } else {
    $firsttree = isset($_COOKIE['tng_tree']) ? sanitize_text_field($_COOKIE['tng_tree']) : '';
  }
  $trees = $wpdb->get_results("SELECT gedcom, treename FROM $trees_table $wherestr ORDER BY treename", ARRAY_A);
?>
  <div class="wrap">
    <h1><?php _e('Add New Source', 'heritagepress'); ?></h1>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" name="form1" id="form1" onsubmit="return heritagepress_validate_newsource_form();">
      <input type="hidden" name="action" value="heritagepress_add_newsource">
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
          <th><label for="sourceID"><?php _e('Source ID', 'heritagepress'); ?></label></th>
          <td>
            <input type="text" name="sourceID" id="sourceID" size="10" onblur="this.value=this.value.toUpperCase()">
            <!-- TODO: Add generate/check ID buttons with JS if needed -->
          </td>
        </tr>
      </table>
      <!-- TODO: Port micro_newsource.php fields here if needed -->
      <p class="submit">
        <input type="submit" class="button-primary" name="save" value="<?php esc_attr_e('Save and Continue', 'heritagepress'); ?>">
        <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-sources')); ?>" class="button"> <?php _e('Cancel', 'heritagepress'); ?> </a>
      </p>
    </form>
  </div>
  <script type="text/javascript">
    function heritagepress_validate_newsource_form() {
      var sourceID = document.getElementById('sourceID').value.trim();
      if (!sourceID) {
        alert('<?php _e('Please enter a source ID.', 'heritagepress'); ?>');
        return false;
      }
      return true;
    }
  </script>
<?php
}

add_action('admin_post_heritagepress_add_newsource', 'heritagepress_handle_add_newsource');
function heritagepress_handle_add_newsource()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  check_admin_referer('heritagepress_newsource');
  // TODO: Sanitize and save the source data to the database
  wp_redirect(admin_url('admin.php?page=heritagepress-sources&message=added'));
  exit;
}
