<?php

/**
 * Add User Form
 * @package HeritagePress
 */
if (!defined('ABSPATH')) exit;
?>
<div class="wrap">
  <h1><?php _e('Add New User', 'heritagepress'); ?></h1>
  <form method="post">
    <?php wp_nonce_field('heritagepress_add_user', '_wpnonce'); ?>
    <table class="form-table">
      <tr>
        <th><label for="user_login"><?php _e('Username', 'heritagepress'); ?></label></th>
        <td><input type="text" name="user_login" id="user_login" class="regular-text" required></td>
      </tr>
      <tr>
        <th><label for="user_email"><?php _e('Email', 'heritagepress'); ?></label></th>
        <td><input type="email" name="user_email" id="user_email" class="regular-text" required></td>
      </tr>
      <tr>
        <th><label for="user_pass"><?php _e('Password', 'heritagepress'); ?></label></th>
        <td><input type="password" name="user_pass" id="user_pass" class="regular-text" required></td>
      </tr>
      <tr>
        <th><label for="hp_genealogy_permissions"><?php _e('Genealogy Permissions', 'heritagepress'); ?></label></th>
        <td><input type="text" name="hp_genealogy_permissions" id="hp_genealogy_permissions" class="regular-text"></td>
      </tr>
    </table>
    <p class="submit">
      <button type="submit" class="button button-primary"><?php _e('Add User', 'heritagepress'); ?></button>
      <a href="<?php echo admin_url('admin.php?page=heritagepress-users'); ?>" class="button button-secondary"><?php _e('Cancel', 'heritagepress'); ?></a>
    </p>
  </form>
</div>
