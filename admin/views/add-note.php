<?php

/**
 * Add New Note (Admin View)
 * WordPress admin page for adding a new note (attached to person, family, etc.)
 *
 * @package HeritagePress
 */
if (!defined('ABSPATH')) {
  exit;
}
?>
<div class="wrap">
  <h1><?php _e('Add New Note', 'heritagepress'); ?></h1>
  <form id="hp-add-note-form" method="post">
    <?php wp_nonce_field('hp_add_note', 'hp_add_note_nonce'); ?>
    <table class="form-table">
      <tr>
        <th scope="row"><label for="note_text"><?php _e('Note Text', 'heritagepress'); ?></label></th>
        <td><textarea name="note_text" id="note_text" rows="4" class="large-text"></textarea></td>
      </tr>
      <tr>
        <th scope="row"><label for="linked_to"><?php _e('Linked To (ID)', 'heritagepress'); ?></label></th>
        <td><input type="text" name="linked_to" id="linked_to" class="regular-text"></td>
      </tr>
      <!-- More fields as needed -->
    </table>
    <p class="submit">
      <input type="submit" class="button-primary" value="<?php esc_attr_e('Save Note', 'heritagepress'); ?>">
      <a href="#" class="button cancel-add-note"><?php _e('Cancel', 'heritagepress'); ?></a>
    </p>
  </form>
</div>
