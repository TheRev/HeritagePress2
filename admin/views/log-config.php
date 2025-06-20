<?php
if (!current_user_can('manage_options')) {
  wp_die(__('You do not have sufficient permissions to access this page.'));
}
$updated = isset($_GET['updated']);
$logname = get_option('hp_log_logname', '');
$adminlogfile = get_option('hp_log_adminlogfile', '');
$logsaveconfig = get_option('hp_log_logsaveconfig', false);
$maxloglines = get_option('hp_log_maxloglines', 0);
$adminmaxloglines = get_option('hp_log_adminmaxloglines', 0);
$badhosts = get_option('hp_log_badhosts', '');
$exusers = get_option('hp_log_exusers', '');
$addr_exclude = get_option('hp_log_addr_exclude', '');
$msg_exclude = get_option('hp_log_msg_exclude', '');
?>
<div class="wrap">
  <h1><?php esc_html_e('Log Config', 'heritagepress'); ?></h1>
  <?php if ($updated): ?>
    <div class="notice notice-success">
      <p><?php esc_html_e('Settings saved.', 'heritagepress'); ?></p>
    </div>
  <?php endif; ?>
  <form method="post" action="<?php echo esc_attr(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('hp_log_config'); ?>
    <input type="hidden" name="action" value="hp_save_log_config">
    <table class="form-table">
      <tr>
        <th scope="row"><label for="logname"><?php esc_html_e('Log Filename (Public)', 'heritagepress'); ?></label></th>
        <td><input name="logname" type="text" id="logname" value="<?php echo esc_attr($logname); ?>" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="adminlogfile"><?php esc_html_e('Log Filename (Admin)', 'heritagepress'); ?></label></th>
        <td><input name="adminlogfile" type="text" id="adminlogfile" value="<?php echo esc_attr($adminlogfile); ?>" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="logsaveconfig"><?php esc_html_e('Save in Config', 'heritagepress'); ?></label></th>
        <td><input name="logsaveconfig" type="checkbox" id="logsaveconfig" value="1" <?php checked($logsaveconfig, true); ?>></td>
      </tr>
      <tr>
        <th scope="row"><label for="maxloglines"><?php esc_html_e('Max Log Lines (Public)', 'heritagepress'); ?></label></th>
        <td><input name="maxloglines" type="number" id="maxloglines" value="<?php echo esc_attr($maxloglines); ?>" class="small-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="adminmaxloglines"><?php esc_html_e('Max Log Lines (Admin)', 'heritagepress'); ?></label></th>
        <td><input name="adminmaxloglines" type="number" id="adminmaxloglines" value="<?php echo esc_attr($adminmaxloglines); ?>" class="small-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="badhosts"><?php esc_html_e('Bad Hosts', 'heritagepress'); ?></label></th>
        <td><input name="badhosts" type="text" id="badhosts" value="<?php echo esc_attr($badhosts); ?>" class="large-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="exusers"><?php esc_html_e('Excluded Users', 'heritagepress'); ?></label></th>
        <td><input name="exusers" type="text" id="exusers" value="<?php echo esc_attr($exusers); ?>" class="large-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="addr_exclude"><?php esc_html_e('Block Email (Address Contains)', 'heritagepress'); ?></label></th>
        <td><input name="addr_exclude" type="text" id="addr_exclude" value="<?php echo esc_attr($addr_exclude); ?>" class="large-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="msg_exclude"><?php esc_html_e('Block Email (Message Contains)', 'heritagepress'); ?></label></th>
        <td><input name="msg_exclude" type="text" id="msg_exclude" value="<?php echo esc_attr($msg_exclude); ?>" class="large-text"></td>
      </tr>
    </table>
    <?php submit_button(__('Save Settings', 'heritagepress')); ?>
  </form>
</div>
