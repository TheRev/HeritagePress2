<?php
// HeritagePress: Ported from TNG admin_optimize.php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Register admin menu page
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

// Render the Optimize Tables admin page
function heritagepress_render_optimize_page()
{
  global $wpdb;
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  $nonce = wp_create_nonce('heritagepress_optimize');
  $message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
  // Get all HeritagePress tables
  $tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}tng_%'");
?>
  <div class="wrap">
    <h1><?php _e('Optimize Database Tables', 'heritagepress'); ?></h1>
    <?php if ($message): ?>
      <div class="notice notice-success is-dismissible">
        <p><?php echo esc_html($message); ?></p>
      </div>
    <?php endif; ?>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
      <input type="hidden" name="action" value="heritagepress_optimize_tables">
      <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
      <table class="form-table">
        <tr>
          <th scope="row"><?php _e('Select Table', 'heritagepress'); ?></th>
          <td>
            <select name="optimize_table">
              <option value="all"><?php _e('All Tables', 'heritagepress'); ?></option>
              <?php foreach ($tables as $table): ?>
                <option value="<?php echo esc_attr($table); ?>"><?php echo esc_html($table); ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
      </table>
      <p class="submit">
        <input type="submit" class="button-primary" value="<?php esc_attr_e('Optimize', 'heritagepress'); ?>">
      </p>
    </form>
  </div>
<?php
}

// Handle form submission for Optimize Tables
add_action('admin_post_heritagepress_optimize_tables', 'heritagepress_handle_optimize_tables');
function heritagepress_handle_optimize_tables()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  check_admin_referer('heritagepress_optimize');
  global $wpdb;
  $selected_table = isset($_POST['optimize_table']) ? sanitize_text_field($_POST['optimize_table']) : 'all';
  $message = '';
  if ($selected_table === 'all') {
    $tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}tng_%'");
    foreach ($tables as $table) {
      $wpdb->query("OPTIMIZE TABLE $table");
    }
    $message = __('All tables optimized successfully.', 'heritagepress');
  } else {
    $wpdb->query("OPTIMIZE TABLE $selected_table");
    $message = sprintf(__('Table %s optimized successfully.', 'heritagepress'), esc_html($selected_table));
  }
  wp_redirect(admin_url('admin.php?page=heritagepress-optimize&message=' . urlencode($message)));
  exit;
}
