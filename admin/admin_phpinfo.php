<?php
// HeritagePress: PHP Info admin page, 
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', 'heritagepress_add_phpinfo_page');
function heritagepress_add_phpinfo_page()
{
  add_submenu_page(
    'heritagepress',
    __('PHP Info', 'heritagepress'),
    __('PHP Info', 'heritagepress'),
    'manage_options',
    'heritagepress-phpinfo',
    'heritagepress_render_phpinfo_page'
  );
}

function heritagepress_render_phpinfo_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  echo '<div class="wrap"><h1>' . esc_html(__('PHP Info', 'heritagepress')) . '</h1>';
  ob_start();
  phpinfo();
  $phpinfo = ob_get_clean();
  // Remove DOCTYPE, html, head, body tags for admin display
  $phpinfo = preg_replace('/<!DOCTYPE.*?<body>/is', '', $phpinfo);
  $phpinfo = preg_replace('/<\/body>.*?<\/html>/is', '', $phpinfo);
  echo '<div class="phpinfo-output">' . $phpinfo . '</div></div>';
}
