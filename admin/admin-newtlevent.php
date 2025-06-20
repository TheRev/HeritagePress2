<?php
// HeritagePress: 
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', 'heritagepress_add_newtlevent_page');
function heritagepress_add_newtlevent_page()
{
  add_submenu_page(
    'heritagepress',
    __('Add New Timeline Event', 'heritagepress'),
    __('Add New Timeline Event', 'heritagepress'),
    'manage_options',
    'heritagepress-newtlevent',
    'heritagepress_render_newtlevent_page'
  );
}

function heritagepress_render_newtlevent_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  $nonce = wp_create_nonce('heritagepress_newtlevent');
?>
  <div class="wrap">
    <h1><?php _e('Add New Timeline Event', 'heritagepress'); ?></h1>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
      <input type="hidden" name="action" value="heritagepress_add_newtlevent">
      <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
      <!-- TODO: Add form fields -->
      <p class="submit">
        <input type="submit" class="button-primary" value="<?php esc_attr_e('Save and Continue', 'heritagepress'); ?>">
        <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-timeline-events')); ?>" class="button"> <?php _e('Cancel', 'heritagepress'); ?> </a>
      </p>
    </form>
  </div>
<?php
}

add_action('admin_post_heritagepress_add_newtlevent', 'heritagepress_handle_add_newtlevent');
function heritagepress_handle_add_newtlevent()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  check_admin_referer('heritagepress_newtlevent');
  // TODO: Sanitize and save the timeline event data to the database
  wp_redirect(admin_url('admin.php?page=heritagepress-timeline-events&message=added'));
  exit;
}
