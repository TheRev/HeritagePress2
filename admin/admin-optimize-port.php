<?php
// HeritagePress: Ported from TNG admin_optimize.php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', 'heritagepress_add_optimize_page');
function heritagepress_add_optimize_page()
{
  add_submenu_page(
    'heritagepress',
    __('Optimize Tables', 'heritagepress'),
    __('Optimize Tables', 'heritagepress'),
    'manage_options',
    'heritagepress-optimize',
    'heritagepress_render_optimize_page'
  );
}

function heritagepress_render_optimize_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  $nonce = wp_create_nonce('heritagepress_optimize');
?>
  <div class="wrap">
    <h1><?php _e('Optimize Database Tables', 'heritagepress'); ?></h1>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
      <input type="hidden" name="action" value="heritagepress_optimize_tables">
      <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
      <p><?php _e('Click the button below to optimize all HeritagePress database tables.', 'heritagepress'); ?></p>
      <p class="submit">
        <input type="submit" class="button-primary" value="<?php esc_attr_e('Optimize Tables', 'heritagepress'); ?>">
      </p>
    </form>
  </div>
<?php
}

add_action('admin_post_heritagepress_optimize_tables', 'heritagepress_handle_optimize_tables');
function heritagepress_handle_optimize_tables()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  check_admin_referer('heritagepress_optimize');
  // TODO: Optimize HeritagePress tables
  wp_redirect(admin_url('admin.php?page=heritagepress-optimize&message=optimized'));
  exit;
}
