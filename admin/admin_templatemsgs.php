<?php
// HeritagePress: Template Messages admin page (WordPress-native, ported from TNG admin_templatemsgs.php)
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', function () {
  add_menu_page(
    __('Template Messages', 'heritagepress'),
    __('Template Messages', 'heritagepress'),
    'manage_options',
    'heritagepress-templatemsgs',
    'heritagepress_admin_templatemsgs_page',
    'dashicons-format-status',
    60
  );
});

function heritagepress_admin_templatemsgs_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  global $wpdb;
  $templates_table = $wpdb->prefix . 'tng_templates';
  $templatenum = isset($_GET['template']) ? sanitize_text_field($_GET['template']) : '';
  $message = '';
  // Handle add message (stub)
  if (!empty($_POST['addmsg']) && check_admin_referer('heritagepress_templatemsgs_action')) {
    // TODO: Insert new template message
    $message = __('Message added (not yet implemented).', 'heritagepress');
  }
  // Fetch messages for the selected template
  $where = $templatenum ? $wpdb->prepare('WHERE template = %s', $templatenum) : '';
  $msgs = $wpdb->get_results("SELECT * FROM $templates_table $where ORDER BY template, ordernum");
  echo '<div class="wrap">';
  echo '<h1>' . esc_html__('Template Messages', 'heritagepress') . '</h1>';
  if ($message) {
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
  }
  // Add message form
  echo '<form method="post">';
  wp_nonce_field('heritagepress_templatemsgs_action');
  echo '<table class="form-table">';
  echo '<tr><th>' . esc_html__('Key', 'heritagepress') . '</th><td><input type="text" name="keyname" class="regular-text"></td></tr>';
  echo '<tr><th>' . esc_html__('Value', 'heritagepress') . '</th><td><textarea name="msgvalue" rows="3" cols="50"></textarea></td></tr>';
  echo '<tr><th>' . esc_html__('Language', 'heritagepress') . '</th><td><input type="text" name="tlanguage" class="regular-text"></td></tr>';
  echo '</table>';
  echo '<p><input type="submit" class="button button-primary" name="addmsg" value="' . esc_attr__('Add Message', 'heritagepress') . '"></p>';
  echo '</form>';
  // List messages
  echo '<table class="wp-list-table widefat fixed striped">';
  echo '<thead><tr>';
  echo '<th>' . esc_html__('Actions', 'heritagepress') . '</th>';
  echo '<th>' . esc_html__('Key', 'heritagepress') . '</th>';
  echo '<th>' . esc_html__('Value', 'heritagepress') . '</th>';
  echo '<th>' . esc_html__('Language', 'heritagepress') . '</th>';
  echo '</tr></thead><tbody>';
  if ($msgs) {
    foreach ($msgs as $msg) {
      echo '<tr>';
      echo '<td>';
      echo '<a href="#" class="button button-small edit-msg" data-id="' . esc_attr($msg->id) . '">' . esc_html__('Edit', 'heritagepress') . '</a> ';
      echo '<a href="#" class="button button-small delete-msg" data-id="' . esc_attr($msg->id) . '">' . esc_html__('Delete', 'heritagepress') . '</a>';
      echo '</td>';
      echo '<td>' . esc_html($msg->keyname) . '</td>';
      echo '<td>' . esc_html($msg->value) . '</td>';
      echo '<td>' . esc_html($msg->language) . '</td>';
      echo '</tr>';
    }
  } else {
    echo '<tr><td colspan="4">' . esc_html__('No messages found.', 'heritagepress') . '</td></tr>';
  }
  echo '</tbody></table>';
  // JS for delete confirmation (stub)
  echo '<script>jQuery(document).on("click",".delete-msg",function(e){e.preventDefault();if(confirm("' . esc_js(__('Are you sure you want to delete this message?', 'heritagepress')) . '")){/* TODO: Delete handler */}});</script>';
  echo '</div>';
}
