<?php
// HeritagePress: Template Configuration admin page (WordPress-native, )
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', function () {
  add_menu_page(
    __('Template Configuration', 'heritagepress'),
    __('Template Configuration', 'heritagepress'),
    'manage_options',
    'heritagepress-templateconfig',
    'heritagepress_admin_templateconfig_page',
    'dashicons-admin-appearance',
    59
  );
});

function heritagepress_admin_templateconfig_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  global $wpdb;
  $templates_table = $wpdb->prefix . 'HeritagePress_templates';
  $languages_table = $wpdb->prefix . 'HeritagePress_languages';
  $trees_table = $wpdb->prefix . 'HeritagePress_trees';
  $message = '';
  if (!empty($_POST['save_template_settings']) && check_admin_referer('heritagepress_templateconfig_action')) {
    // TODO: Save template settings logic here
    $message = __('Template settings saved (not yet implemented).', 'heritagepress');
  }
  echo '<div class="wrap">';
  echo '<h1>' . esc_html__('Template Configuration', 'heritagepress') . '</h1>';
  if ($message) {
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
  }
  // List available templates
  $templates_dir = WP_CONTENT_DIR . '/plugins/heritagepress/templates/';
  $templates = [];
  if (is_dir($templates_dir) && ($handle = opendir($templates_dir))) {
    while (false !== ($entry = readdir($handle))) {
      if ($entry !== '.' && $entry !== '..' && is_dir($templates_dir . $entry)) {
        $templates[] = $entry;
      }
    }
    closedir($handle);
  }
  echo '<form method="post">';
  wp_nonce_field('heritagepress_templateconfig_action');
  echo '<table class="form-table">';
  echo '<tr><th>' . esc_html__('Available Templates', 'heritagepress') . '</th><td>';
  if ($templates) {
    echo '<select name="template">';
    foreach ($templates as $template) {
      echo '<option value="' . esc_attr($template) . '">' . esc_html($template) . '</option>';
    }
    echo '</select>';
  } else {
    echo esc_html__('No templates found.', 'heritagepress');
  }
  echo '</td></tr>';
  // TODO: Add per-language and per-tree settings UI
  echo '</table>';
  echo '<p><input type="submit" class="button button-primary" name="save_template_settings" value="' . esc_attr__('Save Settings', 'heritagepress') . '"></p>';
  echo '</form>';
  echo '</div>';
}
