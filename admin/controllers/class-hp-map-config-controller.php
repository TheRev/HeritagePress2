<?php

/**
 * Map Config Controller
 * Provides an admin tool to configure map settings.
 */
if (!defined('ABSPATH')) exit;
class HP_Map_Config_Controller
{
  public function __construct()
  {
    add_action('admin_menu', array($this, 'register_page'));
    add_action('admin_post_hp_save_map_config', array($this, 'handle_save'));
  }
  public function register_page()
  {
    add_submenu_page(
      'hp_tools',
      __('Map Config', 'heritagepress'),
      __('Map Config', 'heritagepress'),
      'manage_options',
      'hp_map_config',
      array($this, 'render_page')
    );
  }
  public function render_page()
  {
    include dirname(__FILE__) . '/../views/map-config.php';
  }
  public function handle_save()
  {
    if (!current_user_can('manage_options') || !check_admin_referer('hp_map_config')) {
      wp_die(__('Security check failed.', 'heritagepress'));
    }
    $provider = sanitize_text_field($_POST['provider'] ?? '');
    $apikey = sanitize_text_field($_POST['apikey'] ?? '');
    $default_lat = sanitize_text_field($_POST['default_lat'] ?? '');
    $default_lng = sanitize_text_field($_POST['default_lng'] ?? '');
    $map_width = sanitize_text_field($_POST['map_width'] ?? '');
    $map_height = sanitize_text_field($_POST['map_height'] ?? '');
    update_option('hp_map_provider', $provider);
    update_option('hp_map_apikey', $apikey);
    update_option('hp_map_default_lat', $default_lat);
    update_option('hp_map_default_lng', $default_lng);
    update_option('hp_map_width', $map_width);
    update_option('hp_map_height', $map_height);
    wp_redirect(add_query_arg(['page' => 'hp_map_config', 'updated' => 1], admin_url('admin.php')));
    exit;
  }
}
new HP_Map_Config_Controller();
