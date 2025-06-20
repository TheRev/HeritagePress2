<?php
// HeritagePress: Ported from TNG admin_notes.php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

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

function heritagepress_render_notes_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
?>
  <div class="wrap">
    <h1><?php _e('Notes', 'heritagepress'); ?></h1>
    <!-- TODO: Render notes management UI -->
  </div>
<?php
}
