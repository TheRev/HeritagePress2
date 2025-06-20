<?php
// HeritagePress: 
if (!defined('ABSPATH')) exit; // Exit if accessed directly

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

function heritagepress_render_newtree_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  $nonce = wp_create_nonce('heritagepress_newtree');
?>
  <div class="wrap">
    <h1><?php _e('Add New Tree', 'heritagepress'); ?></h1>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
      <input type="hidden" name="action" value="heritagepress_add_newtree">
      <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
      <!-- TODO: Add form fields -->
      <p class="submit">
        <input type="submit" class="button-primary" value="<?php esc_attr_e('Save and Continue', 'heritagepress'); ?>">
        <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-trees')); ?>" class="button"> <?php _e('Cancel', 'heritagepress'); ?> </a>
      </p>
    </form>
  </div>
<?php
}

add_action('admin_post_heritagepress_add_newtree', 'heritagepress_handle_add_newtree');
function heritagepress_handle_add_newtree()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  check_admin_referer('heritagepress_newtree');
  // TODO: Sanitize and save the tree data to the database
  wp_redirect(admin_url('admin.php?page=heritagepress-trees&message=added'));
  exit;
}
