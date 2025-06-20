<?php

/**
 * Merge People View for HeritagePress
 *
 * Provides the admin UI for merging two individuals (people).
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
  exit;
}
?>
<div class="wrap">
  <h1><?php esc_html_e('Merge People', 'heritagepress'); ?></h1>
  <form id="merge-people-form" method="post">
    <?php wp_nonce_field('hp_merge_people', 'hp_merge_people_nonce'); ?>
    <table class="form-table">
      <tr>
        <th scope="row">
          <label for="person_id_1"><?php esc_html_e('Person ID 1', 'heritagepress'); ?></label>
        </th>
        <td>
          <input type="text" id="person_id_1" name="person_id_1" class="regular-text" required>
        </td>
      </tr>
      <tr>
        <th scope="row">
          <label for="person_id_2"><?php esc_html_e('Person ID 2', 'heritagepress'); ?></label>
        </th>
        <td>
          <input type="text" id="person_id_2" name="person_id_2" class="regular-text" required>
        </td>
      </tr>
    </table>
    <p class="submit">
      <input type="submit" class="button button-primary" value="<?php esc_attr_e('Compare & Merge', 'heritagepress'); ?>">
    </p>
  </form>
  <div id="merge-people-results"></div>
</div>
