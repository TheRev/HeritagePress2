<?php

/**
 * Mail Users Controller
 * Provides an admin tool to email users by group (tree, branch, admin).
 */
if (!defined('ABSPATH')) exit;
class HP_Mail_Users_Controller
{
  public function __construct()
  {
    add_action('admin_menu', array($this, 'register_page'));
    add_action('admin_post_hp_send_mail_users', array($this, 'handle_send_mail'));
  }
  public function register_page()
  {
    add_submenu_page(
      'users.php',
      __('Email Users', 'heritagepress'),
      __('Email Users', 'heritagepress'),
      'manage_options',
      'hp_mail_users',
      array($this, 'render_page')
    );
  }
  public function render_page()
  {
    include dirname(__FILE__) . '/../views/mail-users.php';
  }
  public function handle_send_mail()
  {
    if (!current_user_can('manage_options') || !check_admin_referer('hp_mail_users')) {
      wp_die(__('Security check failed.', 'heritagepress'));
    }
    $subject = sanitize_text_field($_POST['subject'] ?? '');
    $message = wp_kses_post($_POST['messagetext'] ?? '');
    $tree = sanitize_text_field($_POST['gedcom'] ?? '');
    $branch = sanitize_text_field($_POST['branch'] ?? '');
    $sendtoadmins = !empty($_POST['sendtoadmins']);
    $users = get_users(['role__in' => ['administrator', 'genealogy_manager']]);
    $sent = 0;
    foreach ($users as $user) {
      // TODO: Filter by tree/branch meta if available
      if ($sendtoadmins && !in_array('administrator', $user->roles)) continue;
      if (empty($user->user_email)) continue;
      $headers = ['Content-Type: text/html; charset=UTF-8'];
      if (wp_mail($user->user_email, $subject, $message, $headers)) {
        $sent++;
      }
    }
    wp_redirect(add_query_arg(['page' => 'hp_mail_users', 'sent' => $sent], admin_url('users.php')));
    exit;
  }
}
new HP_Mail_Users_Controller();
