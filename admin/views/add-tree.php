<?php

/**
 * Add New Tree (Admin View)
 * WordPress admin page for adding a new tree (genealogy tree)
 *
 * @package HeritagePress
 */
if (!defined('ABSPATH')) {
  exit;
}
?>
<div class="wrap">
  <h1><?php _e('Add New Tree', 'heritagepress'); ?></h1>
  <form id="hp-add-tree-form" method="post">
    <?php wp_nonce_field('hp_add_tree', 'hp_add_tree_nonce'); ?>
    <table class="form-table">
      <tr>
        <th scope="row"><label for="tree_name"><?php _e('Tree Name', 'heritagepress'); ?></label></th>
        <td><input type="text" name="tree_name" id="tree_name" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="tree_description"><?php _e('Description', 'heritagepress'); ?></label></th>
        <td><textarea name="tree_description" id="tree_description" rows="4" class="large-text"></textarea></td>
      </tr>
      <!-- More fields as needed -->
    </table>
    <p class="submit">
      <input type="submit" class="button-primary" value="<?php esc_attr_e('Save Tree', 'heritagepress'); ?>">
      <a href="#" class="button cancel-add-tree"><?php _e('Cancel', 'heritagepress'); ?></a>
    </p>
  </form>
</div>
