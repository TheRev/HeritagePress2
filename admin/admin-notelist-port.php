<?php
// HeritagePress: Ported from TNG admin_notelist.php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

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

function heritagepress_render_notelist_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
?>
  <div class="wrap">
    <h1><?php _e('Note List', 'heritagepress'); ?></h1>
    <!-- TODO: Render note list table -->
  </div>
<?php
}
