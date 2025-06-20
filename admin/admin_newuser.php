<?php
// HeritagePress: 
if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Register admin menu page
add_action('admin_menu', 'heritagepress_add_newuser_page');
function heritagepress_add_newuser_page()
{
  add_submenu_page(
    'heritagepress',
    __('Add New User', 'heritagepress'),
    __('Add New User', 'heritagepress'),
    'manage_options',
    'heritagepress-newuser',
    'heritagepress_render_newuser_page'
  );
}

// Render the Add New User admin page
function heritagepress_render_newuser_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  $nonce = wp_create_nonce('heritagepress_newuser');
  $message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
?>
  <div class="wrap">
    <h1><?php _e('Add New User', 'heritagepress'); ?></h1>
    <?php if ($message): ?>
      <div class="notice notice-success is-dismissible">
        <p><?php echo esc_html($message); ?></p>
      </div>
    <?php endif; ?>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
      <input type="hidden" name="action" value="heritagepress_add_newuser">
      <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
      <table class="form-table">
        <tr>
          <th><label for="username"><?php _e('Username', 'heritagepress'); ?></label></th>
          <td><input type="text" name="username" id="username" value="" class="regular-text" required></td>
        </tr>
        <!-- TODO: Add more fields as needed -->
      </table>
      <p class="submit">
        <input type="submit" class="button-primary" value="<?php esc_attr_e('Save and Continue', 'heritagepress'); ?>">
        <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-users')); ?>" class="button"> <?php _e('Cancel', 'heritagepress'); ?> </a>
      </p>
    </form>
  </div>
<?php
}

// Handle form submission for Add New User
add_action('admin_post_heritagepress_add_newuser', 'heritagepress_handle_add_newuser');
function heritagepress_handle_add_newuser()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  check_admin_referer('heritagepress_newuser');

  // TODO: Add validation and sanitization for all fields
  $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
  if (empty($username)) {
    wp_redirect(admin_url('admin.php?page=heritagepress-newuser&message=' . urlencode(__('Username is required.', 'heritagepress'))));
    exit;
  }

  // TODO: Save the user data to the database

  wp_redirect(admin_url('admin.php?page=heritagepress-newuser&message=' . urlencode(__('User added successfully.', 'heritagepress'))));
  exit;
}
