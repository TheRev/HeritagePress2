<?php

/**
 * Add New Media (Admin View)
 * WordPress admin page for adding new media (photo, document, etc.)
 *
 * @package HeritagePress
 */
if (!defined('ABSPATH')) {
  exit;
}
?>
<div class="wrap">
  <h1><?php _e('Add New Media', 'heritagepress'); ?></h1>
  <form id="hp-add-media-form" method="post" enctype="multipart/form-data">
    <?php wp_nonce_field('hp_add_media', 'hp_add_media_nonce'); ?>
    <table class="form-table">
      <tr>
        <th scope="row"><label for="media_file"><?php _e('Media File', 'heritagepress'); ?></label></th>
        <td><input type="file" name="media_file" id="media_file"></td>
      </tr>
      <tr>
        <th scope="row"><label for="media_type"><?php _e('Media Type', 'heritagepress'); ?></label></th>
        <td><input type="text" name="media_type" id="media_type" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="media_title"><?php _e('Title', 'heritagepress'); ?></label></th>
        <td><input type="text" name="media_title" id="media_title" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="media_description"><?php _e('Description', 'heritagepress'); ?></label></th>
        <td><textarea name="media_description" id="media_description" rows="4" class="large-text"></textarea></td>
      </tr>
      <!-- More fields as needed for linked person/family, status, etc. -->
    </table>
    <p class="submit">
      <input type="submit" class="button-primary" value="<?php esc_attr_e('Save Media', 'heritagepress'); ?>">
      <a href="#" class="button cancel-add-media"><?php _e('Cancel', 'heritagepress'); ?></a>
    </p>
  </form>
</div>
