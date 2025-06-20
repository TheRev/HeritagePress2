<?php
// HeritagePress: Ported from TNG admin_newtree.php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Register admin menu page
add_action('admin_menu', 'heritagepress_add_newtree_page');
function heritagepress_add_newtree_page()
{
  add_submenu_page(
    'heritagepress',
    __('Add New Tree', 'heritagepress'),
    __('Add New Tree', 'heritagepress'),
    'manage_options',
    'heritagepress-newtree',
    'heritagepress_render_newtree_page'
  );
}

// Render the Add New Tree admin page
function heritagepress_render_newtree_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  $nonce = wp_create_nonce('heritagepress_newtree');
  $message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
?>
  <div class="wrap">
    <h1><?php _e('Add New Tree', 'heritagepress'); ?></h1>
    <?php if ($message): ?>
      <div class="notice notice-success is-dismissible">
        <p><?php echo esc_html($message); ?></p>
      </div>
    <?php endif; ?>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
      <input type="hidden" name="action" value="heritagepress_add_newtree">
      <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
      <table class="form-table">
        <tr>
          <th><label for="tree_name"><?php _e('Tree Name', 'heritagepress'); ?></label></th>
          <td><input type="text" name="tree_name" id="tree_name" value="" class="regular-text" required></td>
        </tr>
        <!-- TODO: Add more fields as needed -->
      </table>
      <p class="submit">
        <input type="submit" class="button-primary" value="<?php esc_attr_e('Save and Continue', 'heritagepress'); ?>">
        <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-trees')); ?>" class="button"> <?php _e('Cancel', 'heritagepress'); ?> </a>
      </p>
    </form>
  </div>
<?php
}

// Handle form submission for Add New Tree
add_action('admin_post_heritagepress_add_newtree', 'heritagepress_handle_add_newtree');
function heritagepress_handle_add_newtree()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  check_admin_referer('heritagepress_newtree');

  // TODO: Add validation and sanitization for all fields
  $tree_name = isset($_POST['tree_name']) ? sanitize_text_field($_POST['tree_name']) : '';
  if (empty($tree_name)) {
    wp_redirect(admin_url('admin.php?page=heritagepress-newtree&message=' . urlencode(__('Tree Name is required.', 'heritagepress'))));
    exit;
  }

  // TODO: Save the tree data to the database

  wp_redirect(admin_url('admin.php?page=heritagepress-newtree&message=' . urlencode(__('Tree added successfully.', 'heritagepress'))));
  exit;
}
