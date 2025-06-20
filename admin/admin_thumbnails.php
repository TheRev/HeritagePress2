<?php
// HeritagePress: Thumbnails admin page (WordPress-native, ported from TNG admin_thumbnails.php)
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', function () {
  add_menu_page(
    __('Thumbnails', 'heritagepress'),
    __('Thumbnails', 'heritagepress'),
    'manage_options',
    'heritagepress-thumbnails',
    'heritagepress_admin_thumbnails_page',
    'dashicons-format-image',
    61
  );
});

function heritagepress_admin_thumbnails_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  global $wpdb;
  $trees_table = $wpdb->prefix . 'tng_trees';
  $message = '';
  // Handle form submissions (stubs)
  if (!empty($_POST['generate_thumbs']) && check_admin_referer('heritagepress_thumbnails_action')) {
    // TODO: Implement thumbnail generation logic
    $message = __('Thumbnails generated (not yet implemented).', 'heritagepress');
  }
  if (!empty($_POST['assign_defaults']) && check_admin_referer('heritagepress_thumbnails_action')) {
    // TODO: Implement default photo assignment logic
    $message = __('Default photos assigned (not yet implemented).', 'heritagepress');
  }
  echo '<div class="wrap">';
  echo '<h1>' . esc_html__('Thumbnails', 'heritagepress') . '</h1>';
  if ($message) {
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
  }
  // Generate Thumbnails section
  echo '<h2>' . esc_html__('Generate Thumbnails', 'heritagepress') . '</h2>';
  echo '<form method="post">';
  wp_nonce_field('heritagepress_thumbnails_action');
  echo '<label><input type="checkbox" name="regenerate" value="1"> ' . esc_html__('Regenerate existing thumbnails', 'heritagepress') . '</label><br>';
  echo '<label><input type="checkbox" name="repath" value="1"> ' . esc_html__('Repath thumbnails', 'heritagepress') . '</label><br><br>';
  echo '<input type="submit" class="button button-primary" name="generate_thumbs" value="' . esc_attr__('Generate Thumbnails', 'heritagepress') . '">';
  echo '</form>';
  // Assign Default Photos section
  echo '<h2>' . esc_html__('Assign Default Photos', 'heritagepress') . '</h2>';
  echo '<form method="post">';
  wp_nonce_field('heritagepress_thumbnails_action');
  echo '<label><input type="checkbox" name="overwritedefs" value="1"> ' . esc_html__('Overwrite existing defaults', 'heritagepress') . '</label><br><br>';
  echo esc_html__('Tree:', 'heritagepress') . ' <select name="tree">';
  $trees = $wpdb->get_results("SELECT gedcom, treename FROM $trees_table ORDER BY treename");
  foreach ($trees as $tree) {
    echo '<option value="' . esc_attr($tree->gedcom) . '">' . esc_html($tree->treename) . '</option>';
  }
  echo '</select><br><br>';
  echo '<input type="submit" class="button button-primary" name="assign_defaults" value="' . esc_attr__('Assign Defaults', 'heritagepress') . '">';
  echo '</form>';
  echo '</div>';
}
