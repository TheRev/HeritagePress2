<?php
// HeritagePress: Table Creation admin page (WordPress-native, )
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', function () {
  add_submenu_page(
    null, // Hidden from menu, accessible by link
    __('Create Tables', 'heritagepress'),
    __('Create Tables', 'heritagepress'),
    'manage_options',
    'heritagepress-tablecreate',
    'heritagepress_admin_tablecreate_page'
  );
});

function heritagepress_admin_tablecreate_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  global $wpdb;
  $message = '';
  $error = '';
  if (!empty($_POST['create_tables']) && check_admin_referer('heritagepress_tablecreate_action')) {
    // TODO: Define your table creation SQL here or include from a file
    $table_sql = [
      // Example:
      // $wpdb->prefix . 'HeritagePress_trees' => "CREATE TABLE ...",
    ];
    foreach ($table_sql as $table => $sql) {
      $result = $wpdb->query($sql);
      if ($result === false) {
        $error .= sprintf(__('Failed to create table: %s', 'heritagepress'), esc_html($table)) . '<br>';
      }
    }
    if (!$error) {
      $message = __('All tables created successfully.', 'heritagepress');
    }
  }
  echo '<div class="wrap">';
  echo '<h1>' . esc_html__('Create Tables', 'heritagepress') . '</h1>';
  if ($message) {
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
  }
  if ($error) {
    echo '<div class="notice notice-error is-dismissible"><p>' . $error . '</p></div>';
  }
  echo '<form method="post">';
  wp_nonce_field('heritagepress_tablecreate_action');
  echo '<p>' . esc_html__('This will (re)create all required HeritagePress tables. Existing data may be lost.', 'heritagepress') . '</p>';
  echo '<input type="submit" class="button button-primary" name="create_tables" value="' . esc_attr__('Create Tables', 'heritagepress') . '" onclick="return confirm(\'' . esc_js(__('Are you sure you want to (re)create all tables? This may delete existing data.', 'heritagepress')) . '\');">';
  echo '</form>';
  echo '<p><a href="' . esc_url(admin_url('admin.php?page=heritagepress-setup')) . '" class="button">' . esc_html__('Back to Setup', 'heritagepress') . '</a></p>';
  echo '</div>';
}
