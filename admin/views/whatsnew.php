<?php
// Admin view for editing the "What's New" message
if (!current_user_can('manage_options')) {
  wp_die(__('You do not have sufficient permissions to access this page.'));
}
$message = get_option(HP_WhatsNew_Controller::OPTION_KEY, '');
?>
<div class="wrap">
  <h1><?php esc_html_e("What's New Message", 'heritagepress'); ?></h1>
  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('hp_save_whatsnew'); ?>
    <input type="hidden" name="action" value="hp_save_whatsnew">
    <textarea name="hp_whatsnew_message" rows="10" style="width:100%" class="large-text"><?php echo esc_textarea($message); ?></textarea>
    <p><input type="submit" class="button-primary" value="<?php esc_attr_e('Save Message', 'heritagepress'); ?>"></p>
  </form>
</div>
