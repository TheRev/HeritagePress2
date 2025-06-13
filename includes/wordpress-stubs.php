<?php

/**
 * WordPress stubs for development - provides IntelliSense and removes lint errors
 * This file is only for development and is not included in the production plugin
 */

if (!defined('WPINC')) {
  die;
}

// WordPress Constants
if (!defined('ABSPATH')) {
  define('ABSPATH', '/path/to/wordpress/');
}

if (!defined('WPINC')) {
  define('WPINC', 'wp-includes');
}

if (!defined('WP_CONTENT_DIR')) {
  define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
}

if (!defined('WP_PLUGIN_DIR')) {
  define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
}

if (!defined('WP_DEBUG')) {
  define('WP_DEBUG', false);
}

// WordPress Global Variables
global $wpdb;
if (!isset($wpdb)) {
  $wpdb = new stdClass();
  $wpdb->prefix = 'wp_';
  $wpdb->charset = 'utf8mb4';
  $wpdb->collate = 'utf8mb4_unicode_ci';

  // Add missing WPDB methods to eliminate lint errors
  $wpdb->query = function ($query) {
    return false;
  };
  $wpdb->get_var = function ($query, $col = 0, $row = 0) {
    return null;
  };
  $wpdb->prepare = function ($query, ...$args) {
    return $query;
  };
  $wpdb->replace = function ($table, $data, $format = null) {
    return false;
  };
  $wpdb->get_charset_collate = function () {
    return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
  };
}

// WordPress Functions Stubs
if (!function_exists('add_action')) {
  function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {}
}

if (!function_exists('add_filter')) {
  function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {}
}

if (!function_exists('do_action')) {
  function do_action($hook_name, ...$args) {}
}

if (!function_exists('apply_filters')) {
  function apply_filters($hook_name, $value, ...$args)
  {
    return $value;
  }
}

if (!function_exists('register_activation_hook')) {
  function register_activation_hook($file, $callback) {}
}

if (!function_exists('register_deactivation_hook')) {
  function register_deactivation_hook($file, $callback) {}
}

if (!function_exists('plugin_dir_path')) {
  function plugin_dir_path($file)
  {
    return dirname($file) . '/';
  }
}

if (!function_exists('plugin_dir_url')) {
  function plugin_dir_url($file)
  {
    return 'http://localhost/wp-content/plugins/' . basename(dirname($file)) . '/';
  }
}

if (!function_exists('plugin_basename')) {
  function plugin_basename($file)
  {
    return basename(dirname($file)) . '/' . basename($file);
  }
}

if (!function_exists('wp_die')) {
  function wp_die($message = '', $title = '', $args = array())
  {
    die($message);
  }
}

if (!function_exists('sanitize_text_field')) {
  function sanitize_text_field($str)
  {
    return filter_var($str, FILTER_SANITIZE_STRING);
  }
}

if (!function_exists('esc_html')) {
  function esc_html($text)
  {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
  }
}

if (!function_exists('esc_attr')) {
  function esc_attr($text)
  {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
  }
}

if (!function_exists('esc_url')) {
  function esc_url($url)
  {
    return filter_var($url, FILTER_SANITIZE_URL);
  }
}

if (!function_exists('current_user_can')) {
  function current_user_can($capability)
  {
    return true; // Stub for development
  }
}

if (!function_exists('wp_nonce_field')) {
  function wp_nonce_field($action = -1, $name = "_wpnonce", $referer = true, $echo = true)
  {
    return '<input type="hidden" name="' . $name . '" value="stub_nonce" />';
  }
}

if (!function_exists('wp_verify_nonce')) {
  function wp_verify_nonce($nonce, $action = -1)
  {
    return true; // Stub for development
  }
}

if (!function_exists('get_option')) {
  function get_option($option, $default = false)
  {
    return $default;
  }
}

if (!function_exists('update_option')) {
  function update_option($option, $value, $autoload = null)
  {
    return true;
  }
}

if (!function_exists('delete_option')) {
  function delete_option($option)
  {
    return true;
  }
}

if (!function_exists('admin_url')) {
  function admin_url($path = '', $scheme = 'admin')
  {
    return 'http://localhost/wp-admin/' . $path;
  }
}

if (!function_exists('wp_redirect')) {
  function wp_redirect($location, $status = 302)
  {
    header("Location: $location", true, $status);
  }
}

if (!function_exists('is_admin')) {
  function is_admin()
  {
    return true; // Stub for development
  }
}

if (!function_exists('wp_doing_ajax')) {
  function wp_doing_ajax()
  {
    return false; // Stub for development
  }
}

if (!function_exists('wp_enqueue_script')) {
  function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false) {}
}

if (!function_exists('wp_enqueue_style')) {
  function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all') {}
}

if (!function_exists('wp_localize_script')) {
  function wp_localize_script($handle, $object_name, $l10n) {}
}

if (!function_exists('load_plugin_textdomain')) {
  function load_plugin_textdomain($domain, $deprecated = false, $plugin_rel_path = false)
  {
    return true;
  }
}

if (!function_exists('__')) {
  function __($text, $domain = 'default')
  {
    return $text;
  }
}

if (!function_exists('_e')) {
  function _e($text, $domain = 'default')
  {
    echo $text;
  }
}

if (!function_exists('_n')) {
  function _n($single, $plural, $number, $domain = 'default')
  {
    return ($number == 1) ? $single : $plural;
  }
}

if (!function_exists('wp_kses_post')) {
  function wp_kses_post($data)
  {
    return strip_tags($data);
  }
}

if (!function_exists('wp_unslash')) {
  function wp_unslash($value)
  {
    return is_string($value) ? stripslashes($value) : $value;
  }
}

if (!function_exists('flush_rewrite_rules')) {
  function flush_rewrite_rules($hard = true) {}
}

if (!function_exists('get_role')) {
  function get_role($role)
  {
    return new stdClass();
  }
}

if (!function_exists('wp_upload_dir')) {
  function wp_upload_dir($time = null, $create_dir = true, $refresh_cache = false)
  {
    return array(
      'path' => '/path/to/uploads',
      'url' => 'http://localhost/wp-content/uploads',
      'subdir' => '',
      'basedir' => '/path/to/uploads',
      'baseurl' => 'http://localhost/wp-content/uploads',
      'error' => false
    );
  }
}

if (!function_exists('wp_insert_post')) {
  function wp_insert_post($postarr, $wp_error = false)
  {
    return 1; // Stub post ID
  }
}

if (!function_exists('wp_update_post')) {
  function wp_update_post($postarr, $wp_error = false)
  {
    return 1; // Stub post ID
  }
}

if (!function_exists('wp_delete_post')) {
  function wp_delete_post($postid = 0, $force_delete = false)
  {
    return true;
  }
}

if (!function_exists('get_post')) {
  function get_post($post = null, $output = OBJECT, $filter = 'raw')
  {
    return new stdClass();
  }
}

if (!function_exists('wp_insert_user')) {
  function wp_insert_user($userdata)
  {
    return 1; // Stub user ID
  }
}

if (!function_exists('wp_update_user')) {
  function wp_update_user($userdata)
  {
    return 1; // Stub user ID
  }
}

if (!function_exists('wp_delete_user')) {
  function wp_delete_user($id, $reassign = null)
  {
    return true;
  }
}

if (!function_exists('get_user_by')) {
  function get_user_by($field, $value)
  {
    return new stdClass();
  }
}

if (!function_exists('wp_get_current_user')) {
  function wp_get_current_user()
  {
    return new stdClass();
  }
}

if (!function_exists('dbDelta')) {
  function dbDelta($queries = '', $execute = true)
  {
    return array();
  }
}

// Define constants that might be used
if (!defined('OBJECT')) {
  define('OBJECT', 'OBJECT');
}

if (!defined('ARRAY_A')) {
  define('ARRAY_A', 'ARRAY_A');
}

if (!defined('ARRAY_N')) {
  define('ARRAY_N', 'ARRAY_N');
}
