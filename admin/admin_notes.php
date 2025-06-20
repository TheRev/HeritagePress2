<?php
// HeritagePress: 
if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Register admin menu page
add_action('admin_menu', 'heritagepress_add_notes_page');
function heritagepress_add_notes_page()
{
  add_submenu_page(
    'heritagepress',
    __('Notes', 'heritagepress'),
    __('Notes', 'heritagepress'),
    'manage_options',
    'heritagepress-notes',
    'heritagepress_render_notes_page'
  );
}

// Render the Notes admin page
function heritagepress_render_notes_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  $message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
?>
  <div class="wrap">
    <h1><?php _e('Notes', 'heritagepress'); ?></h1>
    <?php if ($message): ?>
      <div class="notice notice-success is-dismissible">
        <p><?php echo esc_html($message); ?></p>
      </div>
    <?php endif; ?>
    <!-- TODO: Render notes management UI here. Use WP_List_Table or custom markup. -->
  </div>
<?php
}
