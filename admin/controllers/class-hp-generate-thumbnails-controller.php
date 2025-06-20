<?php

/**
 * Generate Thumbnails Controller
 * Provides an admin tool to generate missing or outdated media thumbnails.
 */
if (!defined('ABSPATH')) exit;
class HP_Generate_Thumbnails_Controller
{
  public function __construct()
  {
    add_action('admin_menu', array($this, 'register_page'));
    add_action('wp_ajax_hp_generate_thumbnails', array($this, 'ajax_generate_thumbnails'));
  }
  public function register_page()
  {
    add_submenu_page(
      'hp_media',
      __('Generate Thumbnails', 'heritagepress'),
      __('Generate Thumbnails', 'heritagepress'),
      'manage_options',
      'hp_generate_thumbnails',
      array($this, 'render_page')
    );
  }
  public function render_page()
  {
    include dirname(__FILE__) . '/../views/generate-thumbnails.php';
  }
  public function ajax_generate_thumbnails()
  {
    check_ajax_referer('hp_generate_thumbnails');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => __('Insufficient permissions.', 'heritagepress')]);
    }
    require_once plugin_dir_path(__FILE__) . '/class-hp-media-controller.php';
    global $wpdb;
    $media_items = $wpdb->get_results("SELECT mediaID, path, thumbpath FROM {$wpdb->prefix}hp_media WHERE path != ''");
    $created = 0;
    $skipped = 0;
    $errors = 0;
    $conflicts = 0;
    $conflict_list = [];
    $media_controller = new HP_Media_Controller();
    foreach ($media_items as $item) {
      $needs_thumb = empty($item->thumbpath) || !file_exists(wp_upload_dir()['basedir'] . '/' . $item->thumbpath);
      if ($needs_thumb) {
        $result = $media_controller->create_thumbnail($item->mediaID, $item->path);
        if ($result) {
          $created++;
        } else {
          $errors++;
          $conflict_list[] = $item->path;
        }
      } else {
        $skipped++;
      }
    }
    $summary = [
      'created' => $created,
      'skipped' => $skipped,
      'errors' => $errors,
      'conflicts' => $conflict_list
    ];
    wp_send_json_success(['message' => __('Thumbnails generated: ', 'heritagepress') . $created . '<br>Skipped: ' . $skipped . '<br>Errors: ' . $errors . ($errors ? '<br>Conflicts:<br>' . implode('<br>', $conflict_list) : '')]);
  }
}
new HP_Generate_Thumbnails_Controller();
