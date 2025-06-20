<?php
// HeritagePress: Send mail to users handler, 
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_post_heritagepress_sendmailusers', 'heritagepress_handle_sendmailusers');
function heritagepress_handle_sendmailusers()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to send mail to users.', 'heritagepress'));
  }
  check_admin_referer('heritagepress_sendmailusers');
  global $wpdb;
  $users_table = $wpdb->prefix . 'HeritagePress_users';

  $tree = isset($_POST['tree']) ? sanitize_text_field($_POST['tree']) : '';
  $branch = isset($_POST['branch']) ? sanitize_text_field($_POST['branch']) : '';
  $sendtoadmins = !empty($_POST['sendtoadmins']);
  $subject = isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : '';
  $body = isset($_POST['messagetext']) ? wp_kses_post($_POST['messagetext']) : '';

  $where = ["allow_living != '-1'", "email != ''", "(no_email IS NULL OR no_email != '1')"];
  if ($tree) {
    $recipstr = $wpdb->prepare('gedcom = %s', $tree);
    if ($branch) {
      $recipstr = '(' . $recipstr . $wpdb->prepare(' AND branch = %s', $branch) . ')';
    }
    $where[] = $sendtoadmins ? "($recipstr OR gedcom = '')" : $recipstr;
  }
  $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

  $recipients = $wpdb->get_results("SELECT realname, email FROM $users_table $where_sql");
  if (!$recipients) {
    $message = __('No users found to send mail to.', 'heritagepress');
    wp_redirect(admin_url('admin.php?page=heritagepress-mailusers&message=' . urlencode($message)));
    exit;
  }

  $site_name = get_bloginfo('name');
  $admin_email = get_bloginfo('admin_email');
  $count = 0;
  foreach ($recipients as $row) {
    $to = $row->email;
    $headers = ['From: ' . $site_name . ' <' . $admin_email . '>'];
    $sent = wp_mail($to, $subject, $body, $headers);
    if ($sent) $count++;
  }

  $message = sprintf(__('Mail sent to %d users.', 'heritagepress'), $count);
  wp_redirect(admin_url('admin.php?page=heritagepress-mailusers&message=' . urlencode($message)));
  exit;
}
