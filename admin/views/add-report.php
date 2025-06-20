<?php

/**
 * Add New Report (Admin View)
 * WordPress admin page for adding a new report (custom report)
 *
 * @package HeritagePress
 */
if (!defined('ABSPATH')) {
  exit;
}
?>
<div class="wrap">
  <h1><?php _e('Add New Report', 'heritagepress'); ?></h1>
  <form id="hp-add-report-form" method="post">
    <?php wp_nonce_field('hp_add_report', 'hp_add_report_nonce'); ?>
    <table class="form-table">
      <tr>
        <th scope="row"><label for="report_name"><?php _e('Report Name', 'heritagepress'); ?></label></th>
        <td><input type="text" name="report_name" id="report_name" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="report_description"><?php _e('Description', 'heritagepress'); ?></label></th>
        <td><textarea name="report_description" id="report_description" rows="4" class="large-text"></textarea></td>
      </tr>
      <tr>
        <th scope="row"><label for="report_sql"><?php _e('SQL Query', 'heritagepress'); ?></label></th>
        <td><textarea name="report_sql" id="report_sql" rows="4" class="large-text"></textarea></td>
      </tr>
      <!-- More fields as needed for report type, tree, etc. -->
    </table>
    <p class="submit">
      <input type="submit" class="button-primary" value="<?php esc_attr_e('Save Report', 'heritagepress'); ?>">
      <a href="#" class="button cancel-add-report"><?php _e('Cancel', 'heritagepress'); ?></a>
    </p>
  </form>
</div>
