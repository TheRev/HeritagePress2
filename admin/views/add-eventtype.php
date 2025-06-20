<?php

/**
 * Add New Event Type (Admin View)
 * WordPress admin page for adding a new event type
 *
 * @package HeritagePress
 */
if (!defined('ABSPATH')) {
  exit;
}
?>
<div class="wrap">
  <h1><?php _e('Add New Event Type', 'heritagepress'); ?></h1>
  <form id="hp-add-eventtype-form" method="post">
    <?php wp_nonce_field('hp_add_event_type', 'hp_add_event_type_nonce'); ?>
    <table class="form-table">
      <tr>
        <th scope="row"><label for="display_name"><?php _e('Display Name', 'heritagepress'); ?></label></th>
        <td><input type="text" name="display_name" id="display_name" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="tag"><?php _e('Tag', 'heritagepress'); ?></label></th>
        <td><input type="text" name="tag" id="tag" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="type"><?php _e('Type', 'heritagepress'); ?></label></th>
        <td><input type="text" name="type" id="type" class="regular-text"></td>
      </tr>
      <!-- More fields as needed -->
    </table>
    <p class="submit">
      <input type="submit" class="button-primary" value="<?php esc_attr_e('Save Event Type', 'heritagepress'); ?>">
      <a href="#" class="button cancel-add-eventtype"><?php _e('Cancel', 'heritagepress'); ?></a>
    </p>
  </form>
</div>
