<?php
// HeritagePress GEDCOM Import Admin Page
if (!defined('ABSPATH')) exit;
?>
<div class="wrap">
  <h1><?php esc_html_e('Import GEDCOM', 'heritagepress'); ?></h1>
  <form method="post" enctype="multipart/form-data">
    <?php wp_nonce_field('heritagepress_gedcom_import', 'heritagepress_gedcom_import_nonce'); ?>
    <table class="form-table">
      <tr>
        <th scope="row"><label for="gedcom_file"><?php esc_html_e('GEDCOM File', 'heritagepress'); ?></label></th>
        <td><input type="file" name="gedcom_file" id="gedcom_file" required /></td>
      </tr>
      <tr>
        <th scope="row"><label for="tree_id"><?php esc_html_e('Destination Tree', 'heritagepress'); ?></label></th>
        <td><input type="text" name="tree_id" id="tree_id" required /></td>
      </tr>
      <!-- Add more import options here as needed -->
    </table>
    <p class="submit">
      <input type="submit" name="import_gedcom" id="import_gedcom" class="button button-primary" value="<?php esc_attr_e('Import', 'heritagepress'); ?>" />
    </p>
  </form>
</div>
