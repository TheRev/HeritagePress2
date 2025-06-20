<?php
if (!current_user_can('manage_options')) {
  wp_die(__('You do not have sufficient permissions to access this page.'));
}
$sent = isset($_GET['sent']) ? intval($_GET['sent']) : false;
$ajax_nonce = wp_create_nonce('hp_mail_users');
?>
<div class="wrap">
  <h1><?php esc_html_e('Email Users', 'heritagepress'); ?></h1>
  <?php if ($sent !== false): ?>
    <div class="notice notice-success">
      <p><?php printf(esc_html__('Sent %d emails.', 'heritagepress'), $sent); ?></p>
    </div>
  <?php endif; ?>
  <form method="post" action="<?php echo esc_attr(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('hp_mail_users'); ?>
    <input type="hidden" name="action" value="hp_send_mail_users">
    <table class="form-table">
      <tr>
        <th scope="row"><label for="subject"><?php esc_html_e('Subject', 'heritagepress'); ?></label></th>
        <td><input name="subject" type="text" id="subject" value="" class="regular-text" maxlength="50" required></td>
      </tr>
      <tr>
        <th scope="row"><label for="messagetext"><?php esc_html_e('Message', 'heritagepress'); ?></label></th>
        <td><textarea name="messagetext" id="messagetext" class="large-text" rows="10" required></textarea></td>
      </tr>
      <tr>
        <th scope="row"><label for="gedcom"><?php esc_html_e('Tree (optional)', 'heritagepress'); ?></label></th>
        <td><input name="gedcom" type="text" id="gedcom" value="" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="branch"><?php esc_html_e('Branch (optional)', 'heritagepress'); ?></label></th>
        <td><input name="branch" type="text" id="branch" value="" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="sendtoadmins"><?php esc_html_e('Send only to administrators', 'heritagepress'); ?></label></th>
        <td><input name="sendtoadmins" type="checkbox" id="sendtoadmins" value="1"></td>
      </tr>
    </table>
    <?php submit_button(__('Send', 'heritagepress')); ?>
  </form>
</div>
