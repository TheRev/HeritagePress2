<?php

/**
 * Add New Event (Admin View)
 * WordPress admin page for adding a new event (person/family custom event)
 *
 * @package HeritagePress
 */
if (!defined('ABSPATH')) {
  exit;
}
?>
<div class="wrap">
  <h1><?php _e('Add New Event', 'heritagepress'); ?></h1>
  <form id="hp-add-event-form" method="post">
    <?php wp_nonce_field('hp_add_event', 'hp_add_event_nonce'); ?>
    <table class="form-table">
      <tr>
        <th scope="row"><label for="eventtypeID"><?php _e('Event Type', 'heritagepress'); ?></label></th>
        <td>
          <select name="eventtypeID" id="eventtypeID"></select>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="eventdate"><?php _e('Event Date', 'heritagepress'); ?></label></th>
        <td><input type="text" name="eventdate" id="eventdate" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="eventplace"><?php _e('Event Place', 'heritagepress'); ?></label></th>
        <td><input type="text" name="eventplace" id="eventplace" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="info"><?php _e('Details', 'heritagepress'); ?></label></th>
        <td><textarea name="info" id="info" rows="4" class="large-text"></textarea></td>
      </tr>
      <tr>
        <th scope="row"><label for="dupIDs"><?php _e('Duplicate For (IDs)', 'heritagepress'); ?></label></th>
        <td><input type="text" name="dupIDs" id="dupIDs" class="regular-text"></td>
      </tr>
      <!-- More fields can be added here as needed -->
    </table>
    <p class="submit">
      <input type="submit" class="button-primary" value="<?php esc_attr_e('Save Event', 'heritagepress'); ?>">
      <a href="#" class="button cancel-add-event"><?php _e('Cancel', 'heritagepress'); ?></a>
    </p>
  </form>
</div>
