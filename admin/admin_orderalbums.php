<?php
// HeritagePress: Ported from TNG admin_orderalbums.php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Register admin menu page
add_action('admin_menu', 'heritagepress_add_orderalbums_page');
function heritagepress_add_orderalbums_page()
{
  add_submenu_page(
    'heritagepress',
    __('Order Albums', 'heritagepress'),
    __('Order Albums', 'heritagepress'),
    'manage_options',
    'heritagepress-orderalbums',
    'heritagepress_render_orderalbums_page'
  );
}

// Render the Order Albums admin page
function heritagepress_render_orderalbums_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  $message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
?>
  <div class="wrap">
    <h1><?php _e('Order Albums', 'heritagepress'); ?></h1>
    <?php if ($message): ?>
      <div class="notice notice-success is-dismissible">
        <p><?php echo esc_html($message); ?></p>
      </div>
    <?php endif; ?>
    <!-- TODO: Render order albums UI here. Use WP_List_Table or custom markup. -->
  </div>
<?php
}
