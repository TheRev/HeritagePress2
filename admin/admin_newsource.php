<?php
// HeritagePress: Ported from TNG admin_newsource.php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Register admin menu page
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

// Render the Add New Source admin page
function heritagepress_render_newsource_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  $nonce = wp_create_nonce('heritagepress_newsource');
  $message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
?>
  <div class="wrap">
    <h1><?php _e('Add New Source', 'heritagepress'); ?></h1>
    <?php if ($message): ?>
      <div class="notice notice-success is-dismissible">
        <p><?php echo esc_html($message); ?></p>
      </div>
    <?php endif; ?>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
      <input type="hidden" name="action" value="heritagepress_add_newsource">
      <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
      <table class="form-table">
        <tr>
          <th><label for="source_name"><?php _e('Source Name', 'heritagepress'); ?></label></th>
          <td><input type="text" name="source_name" id="source_name" value="" class="regular-text" required></td>
        </tr>
        <!-- TODO: Add more fields as needed -->
      </table>
      <p class="submit">
        <input type="submit" class="button-primary" value="<?php esc_attr_e('Save and Continue', 'heritagepress'); ?>">
        <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-sources')); ?>" class="button"> <?php _e('Cancel', 'heritagepress'); ?> </a>
      </p>
    </form>
  </div>
<?php
}

// Handle form submission for Add New Source
add_action('admin_post_heritagepress_add_newsource', 'heritagepress_handle_add_newsource');
function heritagepress_handle_add_newsource()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  check_admin_referer('heritagepress_newsource');

  // TODO: Add validation and sanitization for all fields
  $source_name = isset($_POST['source_name']) ? sanitize_text_field($_POST['source_name']) : '';
  if (empty($source_name)) {
    wp_redirect(admin_url('admin.php?page=heritagepress-newsource&message=' . urlencode(__('Source Name is required.', 'heritagepress'))));
    exit;
  }

  // TODO: Save the source data to the database

  wp_redirect(admin_url('admin.php?page=heritagepress-newsource&message=' . urlencode(__('Source added successfully.', 'heritagepress'))));
  exit;
}
