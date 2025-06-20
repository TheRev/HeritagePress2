<?php
// HeritagePress: 
if (!defined('ABSPATH')) exit; // Exit if accessed directly

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

function heritagepress_render_newuser_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  $nonce = wp_create_nonce('heritagepress_newuser');
?>
  <div class="wrap">
    <h1><?php _e('Add New User', 'heritagepress'); ?></h1>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
      <input type="hidden" name="action" value="heritagepress_add_newuser">
      <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
      <!-- TODO: Add form fields -->
      <p class="submit">
        <input type="submit" class="button-primary" value="<?php esc_attr_e('Save and Continue', 'heritagepress'); ?>">
        <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-users')); ?>" class="button"> <?php _e('Cancel', 'heritagepress'); ?> </a>
      </p>
    </form>
  </div>
<?php
}

add_action('admin_post_heritagepress_add_newuser', 'heritagepress_handle_add_newuser');
function heritagepress_handle_add_newuser()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  check_admin_referer('heritagepress_newuser');
  // TODO: Sanitize and save the user data to the database
  wp_redirect(admin_url('admin.php?page=heritagepress-users&message=added'));
  exit;
}
