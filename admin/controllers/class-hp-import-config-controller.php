<?php

/**
 * Import Config Controller
 * Provides an admin tool to configure GEDCOM import settings.
 */
if (!defined('ABSPATH')) exit;
class HP_Import_Config_Controller
{
  public function __construct()
  {
    add_action('admin_menu', array($this, 'register_page'));
    add_action('admin_post_hp_save_import_config', array($this, 'handle_save'));
  }
  public function register_page()
  {
    add_submenu_page(
      'hp_tools',
      __('Import Config', 'heritagepress'),
      __('Import Config', 'heritagepress'),
      'manage_options',
      'hp_import_config',
      array($this, 'render_page')
    );
  }
  public function render_page()
  {
    include dirname(__FILE__) . '/../views/import-config.php';
  }
  public function handle_save()
  {
    if (!current_user_can('manage_options') || !check_admin_referer('hp_import_config')) {
      wp_die(__('Security check failed.', 'heritagepress'));
    }
    $gedpath = sanitize_text_field($_POST['gedpath'] ?? '');
    $saveconfig = !empty($_POST['saveconfig']);
    $rrnum = intval($_POST['rrnum'] ?? 0);
    update_option('hp_import_gedpath', $gedpath);
    update_option('hp_import_saveconfig', $saveconfig);
    update_option('hp_import_rrnum', $rrnum);
    wp_redirect(add_query_arg(['page' => 'hp_import_config', 'updated' => 1], admin_url('admin.php')));
    exit;
  }
}
new HP_Import_Config_Controller();
