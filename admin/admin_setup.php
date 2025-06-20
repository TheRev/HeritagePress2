<?php
// HeritagePress: Setup admin dashboard (WordPress-native, ported from TNG admin_setup.php)
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', function () {
  add_menu_page(
    __('Setup', 'heritagepress'),
    __('Setup', 'heritagepress'),
    'manage_options',
    'heritagepress-setup',
    'heritagepress_admin_setup_page',
    'dashicons-admin-tools',
    55
  );
});

function heritagepress_admin_setup_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  $sub = isset($_GET['sub']) ? sanitize_key($_GET['sub']) : 'configuration';
  $message = isset($_GET['message']) ? sanitize_text_field(wp_unslash($_GET['message'])) : '';

  echo '<div class="wrap">';
  echo '<h1>' . esc_html__('Setup', 'heritagepress') . '</h1>';
  if ($message) {
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
  }

  // Tabs
  $tabs = [
    'configuration' => __('Configuration', 'heritagepress'),
    'diagnostics' => __('Diagnostics', 'heritagepress'),
    'tablecreation' => __('Table Creation', 'heritagepress'),
  ];
  echo '<h2 class="nav-tab-wrapper">';
  foreach ($tabs as $key => $label) {
    $active = ($sub === $key) ? ' nav-tab-active' : '';
    echo '<a href="' . esc_url(admin_url('admin.php?page=heritagepress-setup&sub=' . $key)) . '" class="nav-tab' . $active . '">' . esc_html($label) . '</a>';
  }
  echo '</h2>';

  if ($sub === 'configuration') {
    echo '<p class="description">' . esc_html__('Enter or update your system configuration settings. Click a section below to edit.', 'heritagepress') . '</p>';
    echo '<table class="form-table"><tr>';
    echo '<td><p><a href="' . esc_url(admin_url('admin.php?page=heritagepress-genconfig')) . '"><b>' . esc_html__('General Settings', 'heritagepress') . '</b></a></p>';
    echo '<p><a href="' . esc_url(admin_url('admin.php?page=heritagepress-pedconfig')) . '"><b>' . esc_html__('Pedigree Settings', 'heritagepress') . '</b></a></p>';
    echo '</td><td style="width:50px"></td>';
    echo '<td><p><a href="' . esc_url(admin_url('admin.php?page=heritagepress-logconfig')) . '"><b>' . esc_html__('Log Settings', 'heritagepress') . '</b></a></p>';
    echo '<p><a href="' . esc_url(admin_url('admin.php?page=heritagepress-importconfig')) . '"><b>' . esc_html__('Import Settings', 'heritagepress') . '</b></a></p>';
    echo '</td><td style="width:50px"></td>';
    echo '<td><p><a href="' . esc_url(admin_url('admin.php?page=heritagepress-mapconfig')) . '"><b>' . esc_html__('Map Settings', 'heritagepress') . '</b></a></p>';
    echo '<p><a href="' . esc_url(admin_url('admin.php?page=heritagepress-templateconfig')) . '"><b>' . esc_html__('Template Settings', 'heritagepress') . '</b></a></p>';
    echo '</td></tr></table>';
    echo '<br><p class="description"><em>' . esc_html__('You may also define custom variables in your configuration files.', 'heritagepress') . '</em></p>';
  } elseif ($sub === 'tablecreation') {
    echo '<p class="description">' . esc_html__('Create the required database tables for HeritagePress. This action is potentially destructive. Proceed with caution.', 'heritagepress') . '</p>';
    echo '<form method="post">';
    wp_nonce_field('heritagepress_tablecreate_action');
    echo '<label>' . esc_html__('Collation:', 'heritagepress') . ' <input type="text" name="collation" value="utf8_general_ci"> ' . esc_html__('(e.g., utf8_general_ci)', 'heritagepress') . '</label><br><br>';
    echo '<input type="submit" class="button button-primary" name="create_tables" value="' . esc_attr__('Create Tables', 'heritagepress') . '" onclick="return confirm(\'' . esc_js(__('Are you sure you want to (re)create all tables? This may delete existing data.', 'heritagepress')) . '\');">';
    echo '</form>';
    // Handle table creation POST
    if (!empty($_POST['create_tables']) && check_admin_referer('heritagepress_tablecreate_action')) {
      $collation = isset($_POST['collation']) ? sanitize_text_field($_POST['collation']) : 'utf8_general_ci';
      // TODO: Implement table creation logic here
      echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__('Table creation is not yet implemented.', 'heritagepress') . '</p></div>';
    }
    echo '<br><p class="description"><em>' . esc_html__('Warning: This will overwrite existing tables if they exist.', 'heritagepress') . '</em></p>';
  } elseif ($sub === 'diagnostics') {
    echo '<p class="description">' . esc_html__('Diagnostics and system information will be shown here (to be implemented).', 'heritagepress') . '</p>';
  }
  echo '</div>';
}
