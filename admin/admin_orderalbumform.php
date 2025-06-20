<?php
// HeritagePress: Ported from TNG admin_orderalbumform.php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Register admin menu page
add_action('admin_menu', 'heritagepress_add_orderalbumform_page');
function heritagepress_add_orderalbumform_page()
{
  add_submenu_page(
    'heritagepress',
    __('Order Album Form', 'heritagepress'),
    __('Order Album Form', 'heritagepress'),
    'manage_options',
    'heritagepress-orderalbumform',
    'heritagepress_render_orderalbumform_page'
  );
}

// Render the Order Album Form admin page
function heritagepress_render_orderalbumform_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  $message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
?>
  <div class="wrap">
    <h1><?php _e('Order Album Form', 'heritagepress'); ?></h1>
    <?php if ($message): ?>
      <div class="notice notice-success is-dismissible">
        <p><?php echo esc_html($message); ?></p>
      </div>
    <?php endif; ?>
    <!-- TODO: Render order album form UI here. Use WP_List_Table or custom markup. -->
  </div>
<?php
}
