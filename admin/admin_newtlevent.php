<?php
// HeritagePress: Ported from TNG admin_newtlevent.php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Register admin menu page
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

// Render the Add New Timeline Event admin page
function heritagepress_render_newtlevent_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  $nonce = wp_create_nonce('heritagepress_newtlevent');
  $message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
?>
  <div class="wrap">
    <h1><?php _e('Add New Timeline Event', 'heritagepress'); ?></h1>
    <?php if ($message): ?>
      <div class="notice notice-success is-dismissible">
        <p><?php echo esc_html($message); ?></p>
      </div>
    <?php endif; ?>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
      <input type="hidden" name="action" value="heritagepress_add_newtlevent">
      <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
      <table class="form-table">
        <tr>
          <th><label for="event_name"><?php _e('Event Name', 'heritagepress'); ?></label></th>
          <td><input type="text" name="event_name" id="event_name" value="" class="regular-text" required></td>
        </tr>
        <!-- TODO: Add more fields as needed -->
      </table>
      <p class="submit">
        <input type="submit" class="button-primary" value="<?php esc_attr_e('Save and Continue', 'heritagepress'); ?>">
        <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-timeline-events')); ?>" class="button"> <?php _e('Cancel', 'heritagepress'); ?> </a>
      </p>
    </form>
  </div>
<?php
}

// Handle form submission for Add New Timeline Event
add_action('admin_post_heritagepress_add_newtlevent', 'heritagepress_handle_add_newtlevent');
function heritagepress_handle_add_newtlevent()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  check_admin_referer('heritagepress_newtlevent');

  // TODO: Add validation and sanitization for all fields
  $event_name = isset($_POST['event_name']) ? sanitize_text_field($_POST['event_name']) : '';
  if (empty($event_name)) {
    wp_redirect(admin_url('admin.php?page=heritagepress-newtlevent&message=' . urlencode(__('Event Name is required.', 'heritagepress'))));
    exit;
  }

  // TODO: Save the timeline event data to the database

  wp_redirect(admin_url('admin.php?page=heritagepress-newtlevent&message=' . urlencode(__('Timeline event added successfully.', 'heritagepress'))));
  exit;
}
