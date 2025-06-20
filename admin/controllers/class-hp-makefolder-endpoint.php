<?php

/**
 * Make Folder AJAX Endpoint
 * Allows secure creation or renaming of folders for GEDCOM, backups, or media.
 */
if (!defined('ABSPATH')) exit;
class HP_MakeFolder_Endpoint
{
  public function __construct()
  {
    add_action('wp_ajax_hp_make_folder', array($this, 'ajax_make_folder'));
  }
  public function ajax_make_folder()
  {
    check_ajax_referer('hp_make_folder');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => __('Insufficient permissions.', 'heritagepress')]);
    }
    $type = sanitize_text_field($_POST['type'] ?? '');
    $oldfolder = sanitize_text_field($_POST['oldfolder'] ?? '');
    $newfolder = sanitize_text_field($_POST['newfolder'] ?? '');
    $parent = WP_CONTENT_DIR . '/uploads/';
    if ($type === 'gedcom') {
      $parent .= 'gedcom/';
    } elseif ($type === 'backups') {
      $parent .= 'backups/';
    } elseif ($type === 'media') {
      $parent .= 'media/';
    }
    if (!file_exists($parent)) {
      wp_mkdir_p($parent);
    }
    $oldpath = $parent . $oldfolder;
    $newpath = $parent . $newfolder;
    if (file_exists($newpath)) {
      wp_send_json_error(['message' => __('Folder already exists.', 'heritagepress')]);
    } elseif (!empty($oldfolder) && file_exists($oldpath) && @rename($oldpath, $newpath)) {
      wp_send_json_success(['message' => __('Folder renamed successfully.', 'heritagepress')]);
    } elseif (@mkdir($newpath, 0755)) {
      wp_send_json_success(['message' => __('Folder created successfully.', 'heritagepress')]);
    } else {
      wp_send_json_error(['message' => __('Could not create folder. Please create it manually.', 'heritagepress')]);
    }
  }
}
new HP_MakeFolder_Endpoint();
