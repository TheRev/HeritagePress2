<?php

/**
 * Log Config Controller
 * Provides an admin tool to configure log settings.
 */
if (!defined('ABSPATH')) exit;
class HP_Log_Config_Controller
{
  public function __construct()
  {
    add_action('admin_menu', array($this, 'register_page'));
    add_action('admin_post_hp_save_log_config', array($this, 'handle_save'));
  }
  public function register_page()
  {
    add_submenu_page(
      'hp_tools',
      __('Log Config', 'heritagepress'),
      __('Log Config', 'heritagepress'),
      'manage_options',
      'hp_log_config',
      array($this, 'render_page')
    );
  }
  public function render_page()
  {
    include dirname(__FILE__) . '/../views/log-config.php';
  }
  public function handle_save()
  {
    if (!current_user_can('manage_options') || !check_admin_referer('hp_log_config')) {
      wp_die(__('Security check failed.', 'heritagepress'));
    }
    $logname = sanitize_text_field($_POST['logname'] ?? '');
    $adminlogfile = sanitize_text_field($_POST['adminlogfile'] ?? '');
    $logsaveconfig = !empty($_POST['logsaveconfig']);
    $maxloglines = intval($_POST['maxloglines'] ?? 0);
    $adminmaxloglines = intval($_POST['adminmaxloglines'] ?? 0);
    $badhosts = sanitize_text_field($_POST['badhosts'] ?? '');
    $exusers = sanitize_text_field($_POST['exusers'] ?? '');
    $addr_exclude = sanitize_text_field($_POST['addr_exclude'] ?? '');
    $msg_exclude = sanitize_text_field($_POST['msg_exclude'] ?? '');
    update_option('hp_log_logname', $logname);
    update_option('hp_log_adminlogfile', $adminlogfile);
    update_option('hp_log_logsaveconfig', $logsaveconfig);
    update_option('hp_log_maxloglines', $maxloglines);
    update_option('hp_log_adminmaxloglines', $adminmaxloglines);
    update_option('hp_log_badhosts', $badhosts);
    update_option('hp_log_exusers', $exusers);
    update_option('hp_log_addr_exclude', $addr_exclude);
    update_option('hp_log_msg_exclude', $msg_exclude);
    wp_redirect(add_query_arg(['page' => 'hp_log_config', 'updated' => 1], admin_url('admin.php')));
    exit;
  }
}
new HP_Log_Config_Controller();
