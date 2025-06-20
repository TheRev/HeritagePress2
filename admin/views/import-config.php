<?php
if (!current_user_can('manage_options')) {
  wp_die(__('You do not have sufficient permissions to access this page.'));
}
$updated = isset($_GET['updated']);
$gedpath = get_option('hp_import_gedpath', '');
$saveconfig = get_option('hp_import_saveconfig', false);
$rrnum = get_option('hp_import_rrnum', 0);
?>
<div class="wrap">
  <h1><?php esc_html_e('Import Config', 'heritagepress'); ?></h1>
  <?php if ($updated): ?>
    <div class="notice notice-success">
      <p><?php esc_html_e('Settings saved.', 'heritagepress'); ?></p>
    </div>
  <?php endif; ?>
  <form method="post" action="<?php echo esc_attr(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('hp_import_config'); ?>
    <input type="hidden" name="action" value="hp_save_import_config">
    <table class="form-table">
      <tr>
        <th scope="row"><label for="gedpath"><?php esc_html_e('GEDCOM Path', 'heritagepress'); ?></label></th>
        <td><input name="gedpath" type="text" id="gedpath" value="<?php echo esc_attr($gedpath); ?>" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="saveconfig"><?php esc_html_e('Save in Config', 'heritagepress'); ?></label></th>
        <td><input name="saveconfig" type="checkbox" id="saveconfig" value="1" <?php checked($saveconfig, true); ?>></td>
      </tr>
      <tr>
        <th scope="row"><label for="rrnum"><?php esc_html_e('Record Number', 'heritagepress'); ?></label></th>
        <td><input name="rrnum" type="number" id="rrnum" value="<?php echo esc_attr($rrnum); ?>" class="small-text"></td>
      </tr>
    </table>
    <?php submit_button(__('Save Settings', 'heritagepress')); ?>
  </form>
</div>
