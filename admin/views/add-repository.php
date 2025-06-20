<?php

/**
 * Add New Repository (Admin View)
 * WordPress admin page for adding a new repository (source repository)
 *
 * @package HeritagePress
 */
if (!defined('ABSPATH')) {
  exit;
}
?>
<div class="wrap">
  <h1><?php _e('Add New Repository', 'heritagepress'); ?></h1>
  <form id="hp-add-repository-form" method="post">
    <?php wp_nonce_field('hp_add_repository', 'hp_add_repository_nonce'); ?>
    <table class="form-table">
      <tr>
        <th scope="row"><label for="repo_name"><?php _e('Repository Name', 'heritagepress'); ?></label></th>
        <td><input type="text" name="repo_name" id="repo_name" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="repo_address"><?php _e('Address', 'heritagepress'); ?></label></th>
        <td><input type="text" name="repo_address" id="repo_address" class="regular-text"></td>
      </tr>
      <!-- More fields as needed for phone, email, website, etc. -->
    </table>
    <p class="submit">
      <input type="submit" class="button-primary" value="<?php esc_attr_e('Save Repository', 'heritagepress'); ?>">
      <a href="#" class="button cancel-add-repository"><?php _e('Cancel', 'heritagepress'); ?></a>
    </p>
  </form>
</div>
