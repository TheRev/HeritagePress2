<?php

/**
 * HeritagePress System Diagnostics Handler
 * Responds to AJAX requests for system diagnostics (admin utilities)
 */
if (!defined('ABSPATH')) exit;

add_action('wp_ajax_hp_system_check', 'heritagepress_handle_system_check');

function heritagepress_handle_system_check()
{
  check_ajax_referer('hp_system_check');
  if (!current_user_can('manage_options')) {
    wp_send_json_error(['message' => __('Insufficient permissions.', 'heritagepress')]);
  }

  global $wpdb;
  $report = '';

  // PHP Version
  $phpver = phpversion();
  $php_ok = version_compare($phpver, '7.0', '>=');
  $report .= '<h4>PHP Version</h4>';
  $report .= '<p>' . esc_html($phpver) . ' ' . ($php_ok ? '✅' : '❌') . '</p>';

  // MySQL Version
  $mysqlver = $wpdb->db_version();
  $mysql_ok = version_compare($mysqlver, '5.7', '>=');
  $report .= '<h4>MySQL Version</h4>';
  $report .= '<p>' . esc_html($mysqlver) . ' ' . ($mysql_ok ? '✅' : '❌') . '</p>';

  // File Uploads
  $file_uploads = ini_get('file_uploads');
  $report .= '<h4>File Uploads</h4>';
  $report .= '<p>' . ($file_uploads ? 'Enabled ✅' : 'Disabled ❌') . '</p>';

  // Max Upload Size
  $max_upload = ini_get('upload_max_filesize');
  $report .= '<h4>Max Upload Size</h4>';
  $report .= '<p>' . esc_html($max_upload) . '</p>';

  // WP Debug
  $wp_debug = defined('WP_DEBUG') && WP_DEBUG;
  $report .= '<h4>WordPress Debug Mode</h4>';
  $report .= '<p>' . ($wp_debug ? 'Enabled ⚠️' : 'Disabled ✅') . '</p>';

  // HeritagePress Version
  if (defined('HERITAGEPRESS_VERSION')) {
    $report .= '<h4>HeritagePress Version</h4>';
    $report .= '<p>' . esc_html(HERITAGEPRESS_VERSION) . '</p>';
  }

  // Table Checks
  $tables = $wpdb->get_col('SHOW TABLES');
  $hp_tables = array_filter($tables, function ($t) use ($wpdb) {
    return strpos($t, $wpdb->prefix . 'hp_') === 0;
  });
  $report .= '<h4>Database Tables</h4>';
  $report .= '<ul>';
  foreach ($hp_tables as $table) {
    $count = $wpdb->get_var("SELECT COUNT(*) FROM `$table`");
    $report .= '<li>' . esc_html($table) . ': ' . intval($count) . ' records</li>';
  }
  $report .= '</ul>';

  // File Permissions (uploads dir)
  $uploads = wp_get_upload_dir();
  $writable = is_writable($uploads['basedir']);
  $report .= '<h4>Uploads Directory</h4>';
  $report .= '<p>' . esc_html($uploads['basedir']) . ' ' . ($writable ? 'Writable ✅' : 'Not Writable ❌') . '</p>';

  // Server Info
  $report .= '<h4>Server Info</h4>';
  $report .= '<ul>';
  $report .= '<li>OS: ' . esc_html(PHP_OS) . '</li>';
  $report .= '<li>Web Server: ' . esc_html($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . '</li>';
  $report .= '<li>PHP SAPI: ' . esc_html(php_sapi_name()) . '</li>';
  $report .= '</ul>';

  wp_send_json_success(['report' => $report]);
}
