<?php

/**
 * Add New Family (Admin View)
 * WordPress admin page for adding a new family (spousal/parental relationship)
 *
 * @package HeritagePress
 */
if (!defined('ABSPATH')) {
  exit;
}
?>
<div class="wrap">
  <h1><?php _e('Add New Family', 'heritagepress'); ?></h1>
  <form id="hp-add-family-form" method="post">
    <?php wp_nonce_field('hp_add_family', 'hp_add_family_nonce'); ?>
    <table class="form-table">
      <tr>
        <th scope="row"><label for="husband_id"><?php _e('Husband ID', 'heritagepress'); ?></label></th>
        <td><input type="text" name="husband_id" id="husband_id" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="wife_id"><?php _e('Wife ID', 'heritagepress'); ?></label></th>
        <td><input type="text" name="wife_id" id="wife_id" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="marriage_date"><?php _e('Marriage Date', 'heritagepress'); ?></label></th>
        <td><input type="text" name="marriage_date" id="marriage_date" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="marriage_place"><?php _e('Marriage Place', 'heritagepress'); ?></label></th>
        <td><input type="text" name="marriage_place" id="marriage_place" class="regular-text"></td>
      </tr>
      <!-- More fields as needed for children, divorce, etc. -->
    </table>
    <p class="submit">
      <input type="submit" class="button-primary" value="<?php esc_attr_e('Save Family', 'heritagepress'); ?>">
      <a href="#" class="button cancel-add-family"><?php _e('Cancel', 'heritagepress'); ?></a>
    </p>
  </form>
</div>
