<?php

/**
 * Add New Place (Admin View)
 * WordPress admin page for adding a new place (location)
 *
 * @package HeritagePress
 */
if (!defined('ABSPATH')) {
  exit;
}
?>
<div class="wrap">
  <h1><?php _e('Add New Place', 'heritagepress'); ?></h1>
  <form id="hp-add-place-form" method="post">
    <?php wp_nonce_field('hp_add_place', 'hp_add_place_nonce'); ?>
    <table class="form-table">
      <tr>
        <th scope="row"><label for="place_name"><?php _e('Place Name', 'heritagepress'); ?></label></th>
        <td><input type="text" name="place_name" id="place_name" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="latitude"><?php _e('Latitude', 'heritagepress'); ?></label></th>
        <td><input type="text" name="latitude" id="latitude" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="longitude"><?php _e('Longitude', 'heritagepress'); ?></label></th>
        <td><input type="text" name="longitude" id="longitude" class="regular-text"></td>
      </tr>
      <!-- More fields as needed for geocode, notes, etc. -->
    </table>
    <p class="submit">
      <input type="submit" class="button-primary" value="<?php esc_attr_e('Save Place', 'heritagepress'); ?>">
      <a href="#" class="button cancel-add-place"><?php _e('Cancel', 'heritagepress'); ?></a>
    </p>
  </form>
</div>
