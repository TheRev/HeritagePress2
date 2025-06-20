<?php

/**
 * Add New Person (Admin View)
 * WordPress admin page for adding a new person (individual)
 *
 * @package HeritagePress
 */
if (!defined('ABSPATH')) {
  exit;
}
?>
<div class="wrap">
  <h1><?php _e('Add New Person', 'heritagepress'); ?></h1>
  <form id="hp-add-person-form" method="post">
    <?php wp_nonce_field('hp_add_person', 'hp_add_person_nonce'); ?>
    <table class="form-table">
      <tr>
        <th scope="row"><label for="person_id"><?php _e('Person ID', 'heritagepress'); ?></label></th>
        <td><input type="text" name="person_id" id="person_id" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="first_name"><?php _e('First Name', 'heritagepress'); ?></label></th>
        <td><input type="text" name="first_name" id="first_name" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="last_name"><?php _e('Last Name', 'heritagepress'); ?></label></th>
        <td><input type="text" name="last_name" id="last_name" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="gender"><?php _e('Gender', 'heritagepress'); ?></label></th>
        <td>
          <select name="gender" id="gender">
            <option value="M"><?php _e('Male', 'heritagepress'); ?></option>
            <option value="F"><?php _e('Female', 'heritagepress'); ?></option>
            <option value="U"><?php _e('Unknown', 'heritagepress'); ?></option>
          </select>
        </td>
      </tr>
      <!-- More fields as needed for birth, death, living, private, branch, tree, etc. -->
    </table>
    <p class="submit">
      <input type="submit" class="button-primary" value="<?php esc_attr_e('Save Person', 'heritagepress'); ?>">
      <a href="#" class="button cancel-add-person"><?php _e('Cancel', 'heritagepress'); ?></a>
    </p>
  </form>
</div>
