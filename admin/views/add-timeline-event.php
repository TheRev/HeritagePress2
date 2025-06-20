<?php

/**
 * Add New Timeline Event (Admin View)
 * WordPress admin page for adding a new timeline event
 *
 * @package HeritagePress
 */
if (!defined('ABSPATH')) {
  exit;
}
?>
<div class="wrap">
  <h1><?php _e('Add New Timeline Event', 'heritagepress'); ?></h1>
  <form id="hp-add-timeline-event-form" method="post">
    <?php wp_nonce_field('hp_add_timeline_event', 'hp_add_timeline_event_nonce'); ?>
    <table class="form-table">
      <tr>
        <th scope="row"><label for="event_title"><?php _e('Event Title', 'heritagepress'); ?></label></th>
        <td><input type="text" name="event_title" id="event_title" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="event_year"><?php _e('Year', 'heritagepress'); ?></label></th>
        <td><input type="text" name="event_year" id="event_year" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="event_description"><?php _e('Description', 'heritagepress'); ?></label></th>
        <td><textarea name="event_description" id="event_description" rows="4" class="large-text"></textarea></td>
      </tr>
      <!-- More fields as needed -->
    </table>
    <p class="submit">
      <input type="submit" class="button-primary" value="<?php esc_attr_e('Save Timeline Event', 'heritagepress'); ?>">
      <a href="#" class="button cancel-add-timeline-event"><?php _e('Cancel', 'heritagepress'); ?></a>
    </p>
  </form>
</div>
