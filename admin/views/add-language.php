<?php

/**
 * Add New Language (Admin View)
 * WordPress admin page for adding a new language
 *
 * @package HeritagePress
 */
if (!defined('ABSPATH')) {
  exit;
}
?>
<div class="wrap">
  <h1><?php _e('Add New Language', 'heritagepress'); ?></h1>
  <form id="hp-add-language-form" method="post">
    <?php wp_nonce_field('hp_add_language', 'hp_add_language_nonce'); ?>
    <table class="form-table">
      <tr>
        <th scope="row"><label for="language_name"><?php _e('Language Name', 'heritagepress'); ?></label></th>
        <td><input type="text" name="language_name" id="language_name" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="language_folder"><?php _e('Language Folder', 'heritagepress'); ?></label></th>
        <td><input type="text" name="language_folder" id="language_folder" class="regular-text"></td>
      </tr>
      <!-- More fields as needed for language code, charset, etc. -->
    </table>
    <p class="submit">
      <input type="submit" class="button-primary" value="<?php esc_attr_e('Save Language', 'heritagepress'); ?>">
      <a href="#" class="button cancel-add-language"><?php _e('Cancel', 'heritagepress'); ?></a>
    </p>
  </form>
</div>
