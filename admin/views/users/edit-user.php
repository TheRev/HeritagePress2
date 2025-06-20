<?php

/**
 * Edit User Form
 * @package HeritagePress
 */
if (!defined('ABSPATH')) exit;
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$user = get_userdata($user_id);
if (!$user) {
  echo '<div class="notice notice-error"><p>' . __('User not found.', 'heritagepress') . '</p></div>';
  return;
}
$genealogy_permissions = get_user_meta($user_id, 'hp_genealogy_permissions', true);
?>
<div class="wrap">
  <h1><?php _e('Edit User', 'heritagepress'); ?></h1>
  <form method="post">
    <?php wp_nonce_field('heritagepress_edit_user', '_wpnonce'); ?>
    <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
    <table class="form-table">
      <tr>
        <th><label for="user_login"><?php _e('Username', 'heritagepress'); ?></label></th>
        <td><input type="text" name="user_login" id="user_login" value="<?php echo esc_attr($user->user_login); ?>" class="regular-text" readonly></td>
      </tr>
      <tr>
        <th><label for="user_email"><?php _e('Email', 'heritagepress'); ?></label></th>
        <td><input type="email" name="user_email" id="user_email" value="<?php echo esc_attr($user->user_email); ?>" class="regular-text"></td>
      </tr>
      <tr>
        <th><label for="hp_genealogy_permissions"><?php _e('Genealogy Permissions', 'heritagepress'); ?></label></th>
        <td><input type="text" name="hp_genealogy_permissions" id="hp_genealogy_permissions" value="<?php echo esc_attr($genealogy_permissions); ?>" class="regular-text"></td>
      </tr>
    </table>
    <p class="submit">
      <button type="submit" class="button button-primary"><?php _e('Update User', 'heritagepress'); ?></button>
      <a href="<?php echo admin_url('admin.php?page=heritagepress-users'); ?>" class="button button-secondary"><?php _e('Cancel', 'heritagepress'); ?></a>
    </p>
  </form>
</div>
