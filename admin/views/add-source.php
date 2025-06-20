<?php

/**
 * Add New Source (Admin View)
 * WordPress admin page for adding a new source (citation, repository, etc.)
 *
 * @package HeritagePress
 */
if (!defined('ABSPATH')) {
  exit;
}
?>
<div class="wrap">
  <h1><?php _e('Add New Source', 'heritagepress'); ?></h1>
  <form id="hp-add-source-form" method="post">
    <?php wp_nonce_field('hp_add_source', 'hp_add_source_nonce'); ?>
    <table class="form-table">
      <tr>
        <th scope="row"><label for="tree"><?php _e('Tree', 'heritagepress'); ?></label></th>
        <td><input type="text" name="tree" id="tree" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="source_id"><?php _e('Source ID', 'heritagepress'); ?></label></th>
        <td><input type="text" name="source_id" id="source_id" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="source_title"><?php _e('Title', 'heritagepress'); ?></label></th>
        <td><input type="text" name="source_title" id="source_title" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="source_details"><?php _e('Details', 'heritagepress'); ?></label></th>
        <td><textarea name="source_details" id="source_details" rows="4" class="large-text"></textarea></td>
      </tr>
      <!-- More fields as needed for repository, author, publication, etc. -->
    </table>
    <p class="submit">
      <input type="submit" class="button-primary" value="<?php esc_attr_e('Save Source', 'heritagepress'); ?>">
      <a href="#" class="button cancel-add-source"><?php _e('Cancel', 'heritagepress'); ?></a>
    </p>
  </form>
</div>
