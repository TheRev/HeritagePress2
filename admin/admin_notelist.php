<?php
// HeritagePress: 
if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Register admin menu page
add_action('admin_menu', 'heritagepress_add_notelist_page');
function heritagepress_add_notelist_page()
{
  add_submenu_page(
    'heritagepress',
    __('Note List', 'heritagepress'),
    __('Note List', 'heritagepress'),
    'manage_options',
    'heritagepress-notelist',
    'heritagepress_render_notelist_page'
  );
}

// Render the Note List admin page
function heritagepress_render_notelist_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  $message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
?>
  <div class="wrap">
    <h1><?php _e('Note List', 'heritagepress'); ?></h1>
    <?php if ($message): ?>
      <div class="notice notice-success is-dismissible">
        <p><?php echo esc_html($message); ?></p>
      </div>
    <?php endif; ?>
    <!-- TODO: Render note list table here. Use WP_List_Table or custom markup. -->
  </div>
<?php
}
